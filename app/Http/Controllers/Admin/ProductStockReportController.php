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

        // Query products with their categories
        $query = Product::with(['category', 'unit'])
            ->when($request->product_id, function ($q) use ($request) {
                return $q->where('id', $request->product_id);
            })
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            });

        $products = $query->get();

        $reports = $products->map(function ($product) use ($fromDate, $toDate) {
            // Get previous stock before fromDate
            $previousStock = ProductStock::where('product_id', $product->id)
                ->where('created_at', '<', $fromDate)
                ->sum('quantity');

            // Get sales before fromDate
            $previousSales = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($fromDate) {
                    $q->where('created_at', '<', $fromDate);
                })
                ->sum('quantity');

            $openingStock = $previousStock - $previousSales;

            // Get monthly data
            $monthlyData = collect();
            $currentDate = $fromDate->copy()->startOfMonth();
            $endDate = $toDate->copy()->endOfMonth();
            $runningStock = $openingStock;

            while ($currentDate->lte($endDate)) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();

                // Get stock ins for the month
                $stockIns = ProductStock::where('product_id', $product->id)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->get()
                    ->map(function ($stock) {
                        return [
                            'date' => Carbon::parse($stock->created_at)->format('Y-m-d'),
                            'quantity' => $stock->quantity,
                            'unit_cost' => $stock->unit_cost,
                            'total_cost' => $stock->total_cost,
                            'note' => $stock->note
                        ];
                    });

                // Get stock outs (sales) for the month
                $stockOuts = SaleItem::where('product_id', $product->id)
                    ->whereHas('sale', function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('created_at', [$monthStart, $monthEnd]);
                    })
                    ->with('sale')
                    ->get()
                    ->map(function ($saleItem) {
                        return [
                            'date' => Carbon::parse($saleItem->sale->created_at)->format('Y-m-d'),
                            'quantity' => $saleItem->quantity,
                            'unit_price' => $saleItem->unit_price,
                            'total' => $saleItem->subtotal,
                            'invoice_no' => $saleItem->sale->invoice_no
                        ];
                    });

                $totalStockIn = $stockIns->sum('quantity');
                $totalStockOut = $stockOuts->sum('quantity');
                $runningStock += ($totalStockIn - $totalStockOut);

                $monthlyData->push([
                    'month' => $currentDate->format('F Y'),
                    'opening_stock' => $runningStock - ($totalStockIn - $totalStockOut),
                    'stock_ins' => $stockIns,
                    'stock_outs' => $stockOuts,
                    'total_in' => $totalStockIn,
                    'total_out' => $totalStockOut,
                    'closing_stock' => $runningStock,
                    'in_value' => $stockIns->sum('total_cost'),
                    'out_value' => $stockOuts->sum('total')
                ]);

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
                'opening_stock' => $openingStock,
                'current_stock' => $runningStock,
                'monthly_data' => $monthlyData
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'reports' => $reports
            ]);
        }

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

    public function downloadPdf(Request $request)
    {
        // Get all the data first
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

        // Process data for report
        $reports = $this->processReportData($products, $fromDate, $toDate);

        // Prepare data for PDF
        $data = [
            'reports' => $reports,
            'filters' => [
                'from_date' => $fromDate->format('d M, Y'),
                'to_date' => $toDate->format('d M, Y')
            ]
        ];

        $pdf = PDF::loadView('reports.stock-report-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('stock-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // Add this private method to process report data
    private function processReportData($products, $fromDate, $toDate)
    {
        return $products->map(function ($product) use ($fromDate, $toDate) {
            // Get previous stock before fromDate
            $previousStock = ProductStock::where('product_id', $product->id)
                ->where('created_at', '<', $fromDate)
                ->sum('quantity');

            // Get sales before fromDate
            $previousSales = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', function ($q) use ($fromDate) {
                    $q->where('created_at', '<', $fromDate);
                })
                ->sum('quantity');

            $openingStock = $previousStock - $previousSales;

            // Get monthly data
            $monthlyData = collect();
            $currentDate = $fromDate->copy()->startOfMonth();
            $endDate = $toDate->copy()->endOfMonth();
            $runningStock = $openingStock;

            while ($currentDate->lte($endDate)) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();

                // Get stock ins for the month
                $stockIns = ProductStock::where('product_id', $product->id)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->get()
                    ->map(function ($stock) {
                        return [
                            'date' => Carbon::parse($stock->created_at)->format('Y-m-d'),
                            'quantity' => $stock->quantity,
                            'unit_cost' => $stock->unit_cost,
                            'total_cost' => $stock->total_cost,
                            'note' => $stock->note
                        ];
                    });

                // Get stock outs (sales) for the month
                $stockOuts = SaleItem::where('product_id', $product->id)
                    ->whereHas('sale', function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('created_at', [$monthStart, $monthEnd]);
                    })
                    ->with('sale')
                    ->get()
                    ->map(function ($saleItem) {
                        return [
                            'date' => Carbon::parse($saleItem->sale->created_at)->format('Y-m-d'),
                            'quantity' => $saleItem->quantity,
                            'unit_price' => $saleItem->unit_price,
                            'total' => $saleItem->subtotal,
                            'invoice_no' => $saleItem->sale->invoice_no
                        ];
                    });

                $totalStockIn = $stockIns->sum('quantity');
                $totalStockOut = $stockOuts->sum('quantity');
                $runningStock += ($totalStockIn - $totalStockOut);

                $monthlyData->push([
                    'month' => $currentDate->format('F Y'),
                    'opening_stock' => $runningStock - ($totalStockIn - $totalStockOut),
                    'stock_ins' => $stockIns,
                    'stock_outs' => $stockOuts,
                    'total_in' => $totalStockIn,
                    'total_out' => $totalStockOut,
                    'closing_stock' => $runningStock,
                    'in_value' => $stockIns->sum('total_cost'),
                    'out_value' => $stockOuts->sum('total')
                ]);

                $currentDate->addMonth();
            }

            return [
                'product' => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,
                    'cost_price' => $product->cost_price,
                ],
                'opening_stock' => $openingStock,
                'current_stock' => $runningStock,
                'monthly_data' => $monthlyData
            ];
        });
    }
}
