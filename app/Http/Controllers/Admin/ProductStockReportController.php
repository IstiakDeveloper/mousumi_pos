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
        $reports = $this->generateReports($products, $fromDate, $toDate);

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


    private function getStockMovements($productId, $fromDate, $toDate)
    {
        // Get stock ins with purchase type only
        $stockIns = ProductStock::where('product_id', $productId)
            ->where('type', 'purchase')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get()
            ->map(function ($stock) {
                return [
                    'date' => $stock->created_at,
                    'type' => 'in',
                    'quantity' => $stock->quantity,
                    'available_quantity' => $stock->available_quantity,
                    'unit_cost' => $stock->unit_cost,
                    'total_cost' => $stock->total_cost,
                    'note' => $stock->note
                ];
            });

        // Get stock outs
        $stockOuts = ProductStock::where('product_id', $productId)
            ->where('type', 'sale')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get()
            ->map(function ($stock) {
                return [
                    'date' => $stock->created_at,
                    'type' => 'out',
                    'quantity' => abs($stock->quantity), // Make positive for display
                    'available_quantity' => $stock->available_quantity,
                    'unit_cost' => $stock->unit_cost,
                    'total_cost' => abs($stock->total_cost), // Make positive for display
                    'invoice_no' => "Sale Entry" // You can add sale reference if needed
                ];
            });

        return $stockIns->concat($stockOuts)->sortBy('date');
    }

    private function calculateInitialStock($productId, $fromDate)
    {
        return ProductStock::where('product_id', $productId)
            ->where('created_at', '<', $fromDate)
            ->orderBy('id', 'desc')
            ->value('available_quantity') ?? 0;
    }

    private function generateStockReports($products, $fromDate, $toDate)
    {
        return $products->map(function ($product) use ($fromDate, $toDate) {
            // Get initial stock (available_quantity) before from_date
            $initialStock = $this->calculateInitialStock($product->id, $fromDate);

            // Get all movements between dates
            $movements = $this->getStockMovements($product->id, $fromDate, $toDate);

            $monthlyData = [];
            $currentDate = Carbon::parse($fromDate)->startOfMonth();
            $endDate = Carbon::parse($toDate)->endOfMonth();

            // Calculate total purchase stats for weighted average
            $totalPurchaseQuantity = ProductStock::where('product_id', $product->id)
                ->where('type', 'purchase')
                ->where('created_at', '<=', $toDate)
                ->sum('quantity');

            $totalPurchaseValue = ProductStock::where('product_id', $product->id)
                ->where('type', 'purchase')
                ->where('created_at', '<=', $toDate)
                ->sum(DB::raw('quantity * unit_cost'));

            // Calculate weighted average cost
            $weightedAverageCost = $totalPurchaseQuantity > 0
                ? $totalPurchaseValue / $totalPurchaseQuantity
                : $product->cost_price;

            while ($currentDate->lte($endDate)) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();

                // Get this month's movements
                $monthMovements = $movements->filter(function ($movement) use ($monthStart, $monthEnd) {
                    $date = Carbon::parse($movement['date']);
                    return $date->between($monthStart, $monthEnd);
                });

                // Get month's stock ins (purchases only)
                $monthStockIns = $monthMovements->where('type', 'in')->values();

                // Get month's stock outs (sales only)
                $monthStockOuts = $monthMovements->where('type', 'out')->values();

                // Get end of month available quantity
                $monthEndStock = ProductStock::where('product_id', $product->id)
                    ->where('created_at', '<=', $monthEnd)
                    ->orderBy('id', 'desc')
                    ->value('available_quantity') ?? 0;

                $monthlyData[] = [
                    'month' => $currentDate->format('F Y'),
                    'opening_stock' => $initialStock,
                    'stock_ins' => $monthStockIns,
                    'stock_outs' => $monthStockOuts,
                    'total_in' => $monthStockIns->sum('quantity'),
                    'total_out' => $monthStockOuts->sum('quantity'),
                    'closing_stock' => $monthEndStock,
                    'in_value' => $monthStockIns->sum('total_cost'),
                    'out_value' => $monthStockOuts->sum('total_cost')
                ];

                $initialStock = $monthEndStock;
                $currentDate->addMonth();
            }

            // Get current available quantity
            $currentStock = ProductStock::where('product_id', $product->id)
                ->orderBy('id', 'desc')
                ->value('available_quantity') ?? 0;

            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,
                    'cost_price' => round($weightedAverageCost, 2),
                ],
                'opening_stock' => $initialStock,
                'current_stock' => $currentStock,
                'total_stock_in' => collect($monthlyData)->sum('total_in'),
                'total_stock_out' => collect($monthlyData)->sum('total_out'),
                'total_stock_value' => round($currentStock * $weightedAverageCost, 2),
                'monthly_data' => $monthlyData
            ];
        });
    }

    private function generateReports($products, $fromDate, $toDate)
    {
        return $products->map(function ($product) use ($fromDate, $toDate) {
            // Get all purchases within date range
            $purchases = ProductStock::where('product_id', $product->id)
                ->where('type', 'purchase')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderBy('created_at')
                ->get()
                ->map(function ($stock) {
                    return [
                        'date' => $stock->created_at,
                        'quantity' => round($stock->quantity),
                        'unit_cost' => round($stock->unit_cost, 2),
                        'total_cost' => round($stock->total_cost, 2),
                        'available_quantity' => round($stock->available_quantity),
                        'note' => $stock->note
                    ];
                });

            // Get sales from SaleItem instead of ProductStock
            $sales = SaleItem::with('sale')
                ->where('product_id', $product->id)
                ->whereHas('sale', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('created_at', [$fromDate, $toDate]);
                })
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->sale->created_at,
                        'quantity' => round($item->quantity),
                        'invoice_no' => $item->sale->invoice_no,
                        'unit_price' => round($item->unit_price, 2),
                        'total' => round($item->subtotal, 2),
                        'available_quantity' => round($this->getAvailableQuantityAtTime($item->product_id, $item->sale->created_at))
                    ];
                });

            // Get the stock at start date
            $openingStock = ProductStock::where('product_id', $product->id)
                ->where('created_at', '<', $fromDate)
                ->orderByDesc('id')
                ->first();

            // Get current stock
            $currentStock = ProductStock::where('product_id', $product->id)
                ->orderByDesc('id')
                ->first();

            // Calculate weighted average cost
            $totalPurchaseQty = $purchases->sum('quantity');
            $totalPurchaseCost = $purchases->sum('total_cost');
            $avgCost = $totalPurchaseQty > 0 ? ($totalPurchaseCost / $totalPurchaseQty) : $product->cost_price;

            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name
                ],
                'summary' => [
                    'opening_stock' => $openingStock ? round($openingStock->available_quantity) : 0,
                    'current_stock' => $currentStock ? round($currentStock->available_quantity) : 0,
                    'total_purchased' => round($totalPurchaseQty),
                    'total_sold' => round($sales->sum('quantity')),
                    'avg_cost' => round($avgCost, 2),
                    'stock_value' => round(($currentStock ? $currentStock->available_quantity : 0) * $avgCost, 2),
                    'sales_value' => round($sales->sum('total'), 2)
                ],
                'purchases' => $purchases,
                'sales' => $sales->sortBy('date')->values()
            ];
        });
    }

    // Helper function to get available quantity at a specific time
    private function getAvailableQuantityAtTime($productId, $date)
    {
        return ProductStock::where('product_id', $productId)
            ->where('created_at', '<=', $date)
            ->orderByDesc('id')
            ->first()
                ?->available_quantity ?? 0;
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
        $reports = $this->generateReports($products, $fromDate, $toDate); // Using the updated method

        $data = [
            'reports' => $reports,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address', 'Company Address'),
                'phone' => config('app.phone', 'Company Phone'),
                'email' => config('app.email', 'Company Email'),
            ],
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
