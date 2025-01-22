<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductStockReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $query = Product::with(['category', 'unit'])
            ->when($request->product_id, function ($q) use ($request) {
                return $q->where('id', $request->product_id);
            })
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            });

        $products = $query->get();
        $reports = $this->generateStockReports($products, $fromDate, $toDate);

        return Inertia::render('Admin/Reports/StockReport', [
            'products' => Product::select('id', 'name', 'sku')->get(),
            'categories' => Category::select('id', 'name')->get(),
            'filters' => [
                'product_id' => $request->product_id,
                'category_id' => $request->category_id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'reports' => $reports
        ]);
    }

    private function generateStockReports($products, $fromDate, $toDate)
    {
        return $products->map(function ($product) use ($fromDate, $toDate) {
            // First, get initial stock before from_date
            $initialStock = $this->calculateInitialStock($product->id, $fromDate);

            // Get all movements between dates
            $movements = $this->getStockMovements($product->id, $fromDate, $toDate);

            $runningBalance = $initialStock;
            $monthlyData = [];
            $currentDate = Carbon::parse($fromDate)->startOfMonth();
            $endDate = Carbon::parse($toDate)->endOfMonth();

            while ($currentDate->lte($endDate)) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();

                // Get this month's movements
                $monthMovements = $movements->filter(function ($movement) use ($monthStart, $monthEnd) {
                    $date = Carbon::parse($movement['date']);
                    return $date->between($monthStart, $monthEnd);
                });

                // Calculate month's stock ins
                $monthStockIns = $monthMovements->where('type', 'in')
                    ->values()
                    ->map(function ($movement) {
                        return [
                            'date' => $movement['date'],
                            'quantity' => $movement['total_quantity'], // Using total_quantity
                            'unit_cost' => $movement['unit_cost'],
                            'total_cost' => $movement['total_cost'],
                            'note' => $movement['note']
                        ];
                    });

                // Calculate month's stock outs
                $monthStockOuts = $monthMovements->where('type', 'out')
                    ->values()
                    ->map(function ($movement) {
                        return [
                            'date' => $movement['date'],
                            'quantity' => $movement['quantity'],
                            'unit_price' => $movement['unit_price'],
                            'total' => $movement['total'],
                            'invoice_no' => $movement['invoice_no']
                        ];
                    });

                $totalIn = $monthStockIns->sum('quantity');
                $totalOut = $monthStockOuts->sum('quantity');
                $openingBalance = $runningBalance;
                $runningBalance = $openingBalance + $totalIn - $totalOut;

                $monthlyData[] = [
                    'month' => $currentDate->format('F Y'),
                    'opening_stock' => $openingBalance,
                    'stock_ins' => $monthStockIns,
                    'stock_outs' => $monthStockOuts,
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'closing_stock' => $runningBalance,
                    'in_value' => $monthStockIns->sum('total_cost'),
                    'out_value' => $monthStockOuts->sum('total')
                ];

                $currentDate->addMonth();
            }

            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,
                    'cost_price' => $product->cost_price,
                ],
                'opening_stock' => $initialStock,
                'current_stock' => $runningBalance,
                'total_stock_in' => collect($monthlyData)->sum('total_in'),
                'total_stock_out' => collect($monthlyData)->sum('total_out'),
                'total_stock_value' => $runningBalance * $product->cost_price,
                'monthly_data' => $monthlyData
            ];
        });
    }

    private function calculateInitialStock($productId, $fromDate)
    {
        // Get all stock ins before from_date using total_quantity
        $totalStockIn = ProductStock::where('product_id', $productId)
            ->where('created_at', '<', $fromDate)
            ->sum('total_quantity'); // Using total_quantity instead of quantity

        // Get all stock outs before from_date
        $totalStockOut = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($fromDate) {
                $q->where('created_at', '<', $fromDate);
            })
            ->sum('quantity');

        return $totalStockIn - $totalStockOut;
    }

    private function getStockMovements($productId, $fromDate, $toDate)
    {
        // Get stock ins with total_quantity
        $stockIns = ProductStock::where('product_id', $productId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get()
            ->map(function ($stock) {
                return [
                    'date' => $stock->created_at,
                    'type' => 'in',
                    'quantity' => $stock->quantity,
                    'total_quantity' => $stock->total_quantity, // Added total_quantity
                    'unit_cost' => $stock->unit_cost,
                    'total_cost' => $stock->total_cost,
                    'note' => $stock->note
                ];
            });

        // Get stock outs (no change needed for sales)
        $stockOuts = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->with('sale')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->sale->created_at,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->subtotal,
                    'invoice_no' => $item->sale->invoice_no
                ];
            });

        // Combine and sort all movements by date
        return $stockIns->concat($stockOuts)->sortBy('date');
    }

    public function downloadPdf(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $query = Product::with(['category', 'unit']);
        if ($request->product_id) {
            $query->where('id', $request->product_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();
        $reports = $this->generateStockReports($products, $fromDate, $toDate);

        $data = [
            'reports' => $reports,
            'filters' => [
                'from_date' => $fromDate->format('d M, Y'),
                'to_date' => $toDate->format('d M, Y')
            ]
        ];

        $pdf = PDF::loadView('reports.stock-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('stock-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
