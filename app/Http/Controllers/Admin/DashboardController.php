<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Expense;
use App\Models\BankTransaction;
use App\Models\ExtraIncome;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Parse dates, default to today if not provided
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();
        $endDate = $endDate->endOfDay();

        // Get Sales Data with optimized relationships
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product:id,name,cost_price'])
            ->select('id', 'invoice_no', 'created_at', 'total', 'paid', 'due', 'payment_status', 'customer_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $extraIncomeData = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->select('id', 'amount', 'title', 'date')
            ->orderBy('date', 'desc')
            ->get();

        // Improved Stock Value Calculation - Matching the stock page exactly
        $stockSummaryQuery = DB::table('product_stocks as ps1')
            ->select([
                DB::raw('COUNT(DISTINCT product_id) as total_products'),
                DB::raw('SUM(CASE
                WHEN ps1.id IN (
                    SELECT MAX(ps2.id)
                    FROM product_stocks ps2
                    GROUP BY ps2.product_id
                )
                THEN quantity
                ELSE 0
            END) as total_quantity'),
                DB::raw('SUM(CASE
                WHEN ps1.id IN (
                    SELECT MAX(ps2.id)
                    FROM product_stocks ps2
                    GROUP BY ps2.product_id
                )
                THEN quantity * unit_cost
                ELSE 0
            END) as total_value')
            ])
            ->first();

        // Get individual stock data for the dashboard display
        $stockData = ProductStock::with(['product'])
            ->select(
                'product_stocks.*',
                DB::raw('(SELECT SUM(ps2.quantity)
                     FROM product_stocks ps2
                     WHERE ps2.product_id = product_stocks.product_id) as total_quantity')
            )
            ->whereIn('product_stocks.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->groupBy('product_id');
            })
            ->get()
            ->map(function ($stock) {
                $currentStockValue = $stock->total_quantity > 0 ?
                    $stock->total_quantity * $stock->unit_cost : 0;

                return [
                    'id' => $stock->id,
                    'product' => [
                        'id' => $stock->product->id,
                        'name' => $stock->product->name,
                        'selling_price' => $stock->product->selling_price,
                        'alert_quantity' => $stock->product->alert_quantity
                    ],
                    'quantity' => $stock->total_quantity,
                    'stock_value' => $currentStockValue,
                    'unit_cost' => $stock->unit_cost
                ];
            });

        // Calculate Low Stock Products
        $lowStockProducts = $stockData->filter(function ($stock) {
            return $stock['product']['alert_quantity'] !== null &&
                $stock['quantity'] <= $stock['product']['alert_quantity'] &&
                $stock['quantity'] > 0;
        })->values();

        // Rest of your existing queries...
        $expensesData = Expense::whereBetween('date', [$startDate, $endDate])
            ->with('expenseCategory:id,name')
            ->select('id', 'expense_category_id', 'amount', 'date', 'description')
            ->orderBy('date', 'desc')
            ->get();

        $bankTransactions = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->with('bankAccount:id,account_name,current_balance')
            ->select('id', 'bank_account_id', 'transaction_type', 'amount', 'date', 'description')
            ->orderBy('date', 'desc')
            ->get();

        $dailySalesSummary = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(paid) as total_paid'),
                DB::raw('SUM(due) as total_due')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $bankingSummary = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->select(
                'bank_account_id',
                DB::raw('SUM(CASE WHEN transaction_type = "in" THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type = "out" THEN amount ELSE 0 END) as total_out')
            )
            ->groupBy('bank_account_id')
            ->with('bankAccount:id,account_name,current_balance')
            ->get();

        $salesProfit = $this->calculateTotalProfit($salesData);
        $extraIncomeTotal = $extraIncomeData->sum('amount');

        // Calculate Summary Statistics with corrected stock value
        $summary = [
            'total_sales' => $salesData->sum('total'),
            'total_profit' => $salesProfit + $extraIncomeTotal - $expensesData->sum('amount'),
            'total_expenses' => $expensesData->sum('amount'),
            'cash_received' => $salesData->sum('paid'),
            'stock_value' => round($stockSummaryQuery->total_value, 2), // Using exact same calculation as stock page
            'stock_worth' => $stockData->sum(function ($item) {
                return $item['quantity'] > 0 ?
                    $item['quantity'] * $item['product']['selling_price'] : 0;
            }),
            'total_due' => $salesData->sum('due'),
            'total_transactions' => $salesData->count(),
            'average_sale' => $salesData->count() > 0 ?
                round($salesData->sum('total') / $salesData->count(), 2) : 0,
        ];

        return Inertia::render('Admin/Dashboard/Index', [
            'salesData' => $salesData,
            'stockData' => $stockData,
            'expensesData' => $expensesData,
            'bankTransactions' => $bankTransactions,
            'summary' => $summary,
            'dailySalesSummary' => $dailySalesSummary,
            'bankingSummary' => $bankingSummary,
            'lowStockAlerts' => $lowStockProducts,
            'filters' => [
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString()
            ]
        ]);
    }

    protected function calculateTotalProfit($salesData)
    {
        return $salesData->sum(function ($sale) {
            return $sale->saleItems->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;
                return ($item->selling_price - $costPrice) * $item->quantity;
            });
        });
    }

    private function calculateStockWorth($stockData)
    {
        return $stockData->sum(function ($stock) {
            return $stock->total_quantity * ($stock->product->selling_price ?? 0);
        });
    }
}
