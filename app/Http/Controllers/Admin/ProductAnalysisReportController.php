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

        // Get total sale amount
        $totalSaleAmount = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('total');

        // Get before stock positions (modified query)
        $beforeStockPositions = DB::table(function ($query) use ($startDate) {
            $query->from('product_stocks')
                ->select('product_id')
                ->selectRaw('
                    FIRST_VALUE(available_quantity) OVER (
                        PARTITION BY product_id
                        ORDER BY created_at DESC, id DESC
                    ) as before_quantity
                ')
                ->selectRaw('
                    FIRST_VALUE(unit_cost) OVER (
                        PARTITION BY product_id
                        ORDER BY created_at DESC, id DESC
                    ) as before_avg_cost
                ')
                ->where('created_at', '<', $startDate);
        }, 'before_stocks')
            ->select('product_id', 'before_quantity', 'before_avg_cost')
            ->distinct()
            ->get()
            ->keyBy('product_id');

        // Get current stock positions
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

        // Add debug logging
        \Log::info('Date Range:', [
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString()
        ]);

        \Log::info('Before Stock Positions:', [
            'count' => $beforeStockPositions->count(),
            'sample' => $beforeStockPositions->take(3)
        ]);

        $products = Product::with(['category', 'unit'])
            ->select('products.*')
            ->addSelect([
                'buy_quantity' => ProductStock::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('type', 'purchase'),

                'total_buy_cost' => ProductStock::selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6))')
                    ->whereColumn('product_id', 'products.id')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('type', 'purchase'),

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
            ->map(function ($product, $index) use ($stockPositions, $beforeStockPositions) {
                $stockPosition = $stockPositions[$product->id] ?? null;
                $beforeStockPosition = $beforeStockPositions[$product->id] ?? null;

                // Log individual product details for debugging
                \Log::info("Processing Product: {$product->id}", [
                    'before_stock_position' => $beforeStockPosition,
                    'current_stock_position' => $stockPosition
                ]);

                $beforeQuantity = $beforeStockPosition ? (float) $beforeStockPosition->before_quantity : 0;
                $beforeAvgCost = $beforeStockPosition ? (float) $beforeStockPosition->before_avg_cost : 0;
                $beforeStockValue = $beforeQuantity * $beforeAvgCost;

                $buyPrice = $product->buy_quantity > 0
                    ? (float) ($product->total_buy_cost / $product->buy_quantity)
                    : 0;

                $salePrice = $product->sale_quantity > 0
                    ? (float) ($product->total_sale_amount / $product->sale_quantity)
                    : 0;

                $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;
                $weightedAvgCost = $stockPosition ? (float) $stockPosition->weighted_avg_cost : 0;

                // Calculate profits
                $profitPerUnit = $salePrice - ($beforeAvgCost > 0 ? $beforeAvgCost : $buyPrice);
                $totalProfit = $product->sale_quantity * $profitPerUnit;

                return [
                    'serial' => $index + 1,
                    'product_name' => $product->name,
                    'product_model' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,

                    // Before Stock Info
                    'before_quantity' => $beforeQuantity,
                    'before_price' => $beforeAvgCost,
                    'before_value' => $beforeStockValue,

                    // Buy Info
                    'buy_quantity' => (float) $product->buy_quantity,
                    'buy_price' => $buyPrice,
                    'total_buy_price' => (float) $product->total_buy_cost,

                    // Sale Info
                    'sale_quantity' => (float) $product->sale_quantity,
                    'sale_price' => $salePrice,
                    'total_sale_price' => (float) $product->total_sale_amount,

                    // Profit Info
                    'profit_per_unit' => $profitPerUnit,
                    'total_profit' => $totalProfit,

                    // Available Info
                    'available_quantity' => $availableQuantity,
                    'available_stock_value' => $availableQuantity * $weightedAvgCost
                ];
            });

        $calculatedTotals = [
            'before_quantity' => (float) $products->sum('before_quantity'),
            'before_value' => (float) $products->sum('before_value'),
            'buy_quantity' => (float) $products->sum('buy_quantity'),
            'total_buy_price' => (float) $products->sum('total_buy_price'),
            'sale_quantity' => (float) $products->sum('sale_quantity'),
            'total_sale_price' => $totalSaleAmount,
            'total_profit' => (float) $products->sum('total_profit'),
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
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

            // Get total sale amount
            $totalSaleAmount = Sale::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->sum('total');

            // Get before stock positions
            $beforeStockPositions = DB::table(function ($query) use ($startDate) {
                $query->from('product_stocks')
                    ->select('product_id')
                    ->selectRaw('
                        FIRST_VALUE(available_quantity) OVER (
                            PARTITION BY product_id
                            ORDER BY created_at DESC, id DESC
                        ) as before_quantity
                    ')
                    ->selectRaw('
                        FIRST_VALUE(unit_cost) OVER (
                            PARTITION BY product_id
                            ORDER BY created_at DESC, id DESC
                        ) as before_avg_cost
                    ')
                    ->where('created_at', '<', $startDate);
            }, 'before_stocks')
                ->select('product_id', 'before_quantity', 'before_avg_cost')
                ->distinct()
                ->get()
                ->keyBy('product_id');

            // Get current stock positions
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
                    'buy_quantity' => ProductStock::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('type', 'purchase'),

                    'total_buy_cost' => ProductStock::selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6))')
                        ->whereColumn('product_id', 'products.id')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('type', 'purchase'),

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
                ->map(function ($product, $index) use ($stockPositions, $beforeStockPositions) {
                    $stockPosition = $stockPositions[$product->id] ?? null;
                    $beforeStockPosition = $beforeStockPositions[$product->id] ?? null;

                    $beforeQuantity = $beforeStockPosition ? (float) $beforeStockPosition->before_quantity : 0;
                    $beforeAvgCost = $beforeStockPosition ? (float) $beforeStockPosition->before_avg_cost : 0;
                    $beforeStockValue = $beforeQuantity * $beforeAvgCost;

                    $buyPrice = $product->buy_quantity > 0
                        ? (float) ($product->total_buy_cost / $product->buy_quantity)
                        : 0;

                    $salePrice = $product->sale_quantity > 0
                        ? (float) ($product->total_sale_amount / $product->sale_quantity)
                        : 0;

                    $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;
                    $weightedAvgCost = $stockPosition ? (float) $stockPosition->weighted_avg_cost : 0;

                    // Calculate profits
                    $profitPerUnit = $salePrice - ($beforeAvgCost > 0 ? $beforeAvgCost : $buyPrice);
                    $totalProfit = $product->sale_quantity * $profitPerUnit;

                    return [
                        'serial' => $index + 1,
                        'product_name' => $product->name,
                        'product_model' => $product->sku,
                        'category' => $product->category->name,
                        'unit' => $product->unit->name,

                        // Before Stock Info
                        'before_quantity' => $beforeQuantity,
                        'before_price' => $beforeAvgCost,
                        'before_value' => $beforeStockValue,

                        // Buy Info
                        'buy_quantity' => (float) $product->buy_quantity,
                        'buy_price' => $buyPrice,
                        'total_buy_price' => (float) $product->total_buy_cost,

                        // Sale Info
                        'sale_quantity' => (float) $product->sale_quantity,
                        'sale_price' => $salePrice,
                        'total_sale_price' => (float) $product->total_sale_amount,

                        // Profit Info
                        'profit_per_unit' => $profitPerUnit,
                        'total_profit' => $totalProfit,

                        // Available Info
                        'available_quantity' => $availableQuantity,
                        'available_stock_value' => $availableQuantity * $weightedAvgCost
                    ];
                });

            $calculatedTotals = [
                'before_quantity' => (float) $products->sum('before_quantity'),
                'before_value' => (float) $products->sum('before_value'),
                'buy_quantity' => (float) $products->sum('buy_quantity'),
                'total_buy_price' => (float) $products->sum('total_buy_price'),
                'sale_quantity' => (float) $products->sum('sale_quantity'),
                'total_sale_price' => $totalSaleAmount,
                'total_profit' => (float) $products->sum('total_profit'),
                'available_quantity' => (float) $products->sum('available_quantity'),
                'available_stock_value' => (float) $products->sum('available_stock_value'),
            ];

            $data = [
                'products' => $products,
                'totals' => $calculatedTotals,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];

            $pdf = Pdf::loadView('pdf.product-analysis', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            $filename = 'product-analysis-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

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
