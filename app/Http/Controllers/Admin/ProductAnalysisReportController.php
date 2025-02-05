<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProductAnalysisReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();


        $totalSaleAmount = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('total');

        // Get latest stock positions and weighted average costs
        $stockPositions = DB::table('product_stocks as ps')
            ->select('ps.product_id')
            ->selectRaw('
                CASE
                    WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE product_id = ps.product_id
                        AND created_at <= ?
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                END as available_quantity
            ', [$endDate])
            ->selectRaw('
                CASE
                    WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                    THEN CAST(
                        SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                        SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                        AS DECIMAL(15,6)
                    )
                    ELSE 0
                END as weighted_avg_cost
            ')
            ->join('product_stocks as ps2', function ($join) use ($endDate) {
                $join->on('ps2.product_id', '=', 'ps.product_id')
                    ->where('ps2.created_at', '<=', $endDate);
            })
            ->groupBy('ps.product_id', 'ps.id', 'ps.available_quantity')
            ->having('available_quantity', '>', 0)
            ->get()
            ->keyBy('product_id');

        $products = Product::with(['category', 'unit'])
            ->select('products.*')
            ->addSelect([
                // Buy Information
                'buy_quantity' => ProductStock::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('type', 'purchase'),

                'total_buy_cost' => ProductStock::selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('type', 'purchase'),

                // Sale Information
                'sale_quantity' => SaleItem::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }),

                'total_sale_amount' => SaleItem::selectRaw('CAST(COALESCE(SUM(subtotal), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate])
                            ->whereNull('deleted_at');
                    })
            ])
            ->get()
            ->map(function ($product, $index) use ($stockPositions) {
                // Get stock position for this product
                $stockPosition = $stockPositions[$product->id] ?? null;

                // Calculate buy price (average cost per unit)
                $buyPrice = $product->buy_quantity > 0
                    ? (float) ($product->total_buy_cost / $product->buy_quantity)
                    : 0;

                // Calculate sale price (average sale price per unit)
                $salePrice = $product->sale_quantity > 0
                    ? (float) ($product->total_sale_amount / $product->sale_quantity)
                    : 0;

                // Get available quantity and weighted average cost from stock positions
                $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;
                $weightedAvgCost = $stockPosition ? (float) $stockPosition->weighted_avg_cost : 0;

                return [
                    'serial' => $index + 1,
                    'product_name' => $product->name,
                    'product_model' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,

                    // Buy Info
                    'buy_quantity' => (float) $product->buy_quantity,
                    'buy_price' => $buyPrice,
                    'total_buy_price' => (float) $product->total_buy_cost,

                    // Sale Info
                    'sale_quantity' => (float) $product->sale_quantity,
                    'sale_price' => $salePrice,
                    'total_sale_price' => (float) $product->total_sale_amount,

                    // Available Info - using weighted average cost
                    'available_quantity' => $availableQuantity,
                    'available_stock_value' => $availableQuantity * $weightedAvgCost
                ];
            });

        $calculatedTotals = [
            'buy_quantity' => (float) $products->sum('buy_quantity'),
            'total_buy_price' => (float) $products->sum('total_buy_price'),
            'sale_quantity' => (float) $products->sum('sale_quantity'),
            'total_sale_price' => $totalSaleAmount,
            'available_quantity' => (float) $products->sum('available_quantity'),
            'available_stock_value' => (float) $products->sum('available_stock_value'),
        ];

        return Inertia::render('Admin/Reports/ProductAnalysis', [
            'products' => $products,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'totals' => $calculatedTotals
        ]);
    }

    public function downloadPdf(Request $request)
    {
        try {
            // Validate and set date range
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

            $totalSaleAmount = Sale::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->sum('total');

            // Get latest stock positions and weighted average costs
            $stockPositions = DB::table('product_stocks as ps')
                ->select('ps.product_id')
                ->selectRaw('
                CASE
                    WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE product_id = ps.product_id
                        AND created_at <= ?
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                END as available_quantity
            ', [$endDate])
                ->selectRaw('
                CASE
                    WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                    THEN CAST(
                        SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                        SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                        AS DECIMAL(15,6)
                    )
                    ELSE 0
                END as weighted_avg_cost
            ')
                ->join('product_stocks as ps2', function ($join) use ($endDate) {
                    $join->on('ps2.product_id', '=', 'ps.product_id')
                        ->where('ps2.created_at', '<=', $endDate);
                })
                ->groupBy('ps.product_id', 'ps.id', 'ps.available_quantity')
                ->having('available_quantity', '>', 0)
                ->get()
                ->keyBy('product_id');

            // Get products with analysis data
            $products = Product::with(['category', 'unit'])
                ->select('products.*')
                ->addSelect([
                    // Buy Information
                    'buy_quantity' => ProductStock::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('type', 'purchase'),

                    'total_buy_cost' => ProductStock::selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('type', 'purchase'),

                    // Sale Information
                    'sale_quantity' => SaleItem::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }),

                    'total_sale_amount' => SaleItem::selectRaw('CAST(COALESCE(SUM(subtotal), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('created_at', [$startDate, $endDate])
                                ->whereNull('deleted_at');
                        })
                ])
                ->get()
                ->map(function ($product, $index) use ($stockPositions) {
                    // Get stock position for this product
                    $stockPosition = $stockPositions[$product->id] ?? null;

                    // Calculate buy price (average cost per unit)
                    $buyPrice = $product->buy_quantity > 0
                        ? (float) ($product->total_buy_cost / $product->buy_quantity)
                        : 0;

                    // Calculate sale price (average sale price per unit)
                    $salePrice = $product->sale_quantity > 0
                        ? (float) ($product->total_sale_amount / $product->sale_quantity)
                        : 0;

                    // Get available quantity and weighted average cost from stock positions
                    $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;
                    $weightedAvgCost = $stockPosition ? (float) $stockPosition->weighted_avg_cost : 0;

                    return [
                        'serial' => $index + 1,
                        'product_name' => $product->name,
                        'product_model' => $product->sku,
                        'category' => $product->category->name,
                        'unit' => $product->unit->name,
                        'buy_quantity' => (float) $product->buy_quantity,
                        'buy_price' => $buyPrice,
                        'total_buy_price' => (float) $product->total_buy_cost,
                        'sale_quantity' => (float) $product->sale_quantity,
                        'sale_price' => $salePrice,
                        'total_sale_price' => (float) $product->total_sale_amount,
                        'available_quantity' => $availableQuantity,
                        'available_stock_value' => $availableQuantity * $weightedAvgCost
                    ];
                });

            $calculatedTotals = [
                'buy_quantity' => (float) $products->sum('buy_quantity'),
                'total_buy_price' => (float) $products->sum('total_buy_price'),
                'sale_quantity' => (float) $products->sum('sale_quantity'),
                'total_sale_price' => $totalSaleAmount,
                'available_quantity' => (float) $products->sum('available_quantity'),
                'available_stock_value' => (float) $products->sum('available_stock_value'),
            ];

            // For debugging
            \Log::info('PDF Data:', [
                'products_count' => count($products),
                'totals' => $calculatedTotals
            ]);

            // Prepare data for PDF
            $data = [
                'products' => $products,
                'totals' => $calculatedTotals,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.product-analysis', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            // Generate filename
            $filename = 'product-analysis-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

            // Download PDF
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
