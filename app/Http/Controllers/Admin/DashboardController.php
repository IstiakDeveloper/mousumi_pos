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

        // Ensure end date includes the full day
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

        // Calculate Stock Value and Quantity with current stock
        $stockData = Product::select(
            'products.id',
            'products.name',
            'products.selling_price',
            'products.cost_price',
            'products.alert_quantity',
            DB::raw('COALESCE(SUM(product_stocks.quantity), 0) as total_quantity'),
            DB::raw('COALESCE(products.cost_price * SUM(product_stocks.quantity), 0) as stock_value')
        )
            ->leftJoin('product_stocks', 'products.id', '=', 'product_stocks.product_id')
            ->groupBy(
                'products.id',
                'products.name',
                'products.selling_price',
                'products.cost_price',
                'products.alert_quantity'
            )
            ->get();

        $lowStockProducts = $stockData->filter(function ($stock) {
            return $stock && $stock->alert_quantity !== null &&
                $stock->total_quantity <= $stock->alert_quantity;
        })->values();

        // Get Expenses with category
        $expensesData = Expense::whereBetween('date', [$startDate, $endDate])
            ->with('expenseCategory:id,name')
            ->select('id', 'expense_category_id', 'amount', 'date', 'description')
            ->orderBy('date', 'desc')
            ->get();

        // Get Bank Transactions
        $bankTransactions = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->with('bankAccount:id,account_name,current_balance')
            ->select('id', 'bank_account_id', 'transaction_type', 'amount', 'date', 'description')
            ->orderBy('date', 'desc')
            ->get();

        // Get Daily Sales Summary
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

        // Calculate Banking Summary
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

        // Calculate Summary Statistics
        $summary = [
            'total_sales' => $salesData->sum('total'),
            'total_profit' => $salesProfit + $extraIncomeTotal - $expensesData->sum('amount'),
            'total_expenses' => $expensesData->sum('amount'),
            'cash_received' => $salesData->sum('paid'),  // Updated to use paid column
            'stock_value' => $stockData->sum('stock_value'),  // Sum of all (quantity * unit_cost)
            'stock_worth' => $stockData->sum(function ($product) {
                return $product ? ($product->total_quantity * ($product->selling_price ?? 0)) : 0;
            }),
            'total_due' => $salesData->sum('due'),
            'total_transactions' => $salesData->count(),
            'average_sale' => $salesData->count() > 0 ? $salesData->sum('total') / $salesData->count() : 0,
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

    private function calculateTotalProfit($sales)
    {
        $totalProfit = 0;
        foreach ($sales as $sale) {
            foreach ($sale->saleItems as $item) {
                $costPrice = $item->product->cost_price ?? 0;
                $totalProfit += ($item->unit_price - $costPrice) * $item->quantity;
            }
        }
        return $totalProfit;
    }

    private function calculateStockWorth($stockData)
    {
        return $stockData->sum(function ($stock) {
            return $stock->total_quantity * ($stock->product->selling_price ?? 0);
        });
    }
}
