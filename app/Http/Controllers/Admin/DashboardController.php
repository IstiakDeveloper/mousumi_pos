<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Parse dates, default to today if not provided
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();
        $endDate = $endDate->endOfDay();

        // Calculate previous period dates for trend calculations
        $daysDiff = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($daysDiff + 1);
        $previousEndDate = $endDate->copy()->subDays($daysDiff + 1);

        // Get Sales Data
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product:id,name,cost_price,selling_price'])
            ->select('id', 'invoice_no', 'created_at', 'total', 'paid', 'due', 'payment_status', 'customer_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate current and previous period sales profit
        $salesProfit = $salesData->sum(function ($sale) {
            return $sale->saleItems->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;

                return ($item->unit_price - $costPrice) * $item->quantity;
            });
        });

        $previousSalesProfit = Sale::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->with(['saleItems.product:id,cost_price'])
            ->get()
            ->sum(function ($sale) {
                return $sale->saleItems->sum(function ($item) {
                    $costPrice = $item->product->cost_price ?? 0;

                    return ($item->unit_price - $costPrice) * $item->quantity;
                });
            });

        // Get Extra Income for both periods
        $extraIncomeData = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->select('id', 'amount', 'title', 'date')
            ->orderBy('date', 'desc')
            ->get();

        $previousExtraIncome = ExtraIncome::whereBetween('date', [$previousStartDate, $previousEndDate])
            ->sum('amount');

        // Get Expenses for both periods
        $expensesData = Expense::whereBetween('date', [$startDate, $endDate])
            ->with(['expenseCategory:id,name'])
            ->whereHas('expenseCategory', function ($query) {
                $query->where('name', '!=', 'Fixed Asset');
            })
            ->select('id', 'expense_category_id', 'amount', 'date', 'description')
            ->orderBy('date', 'desc')
            ->get();

        $previousExpenses = Expense::whereBetween('date', [$previousStartDate, $previousEndDate])
            ->whereHas('expenseCategory', function ($query) {
                $query->where('name', '!=', 'Fixed Asset');
            })
            ->sum('amount');

        // Calculate current and previous stock values
        $stockValueQuery = DB::table('product_stocks as ps')
            ->select([
                DB::raw('COUNT(DISTINCT ps.product_id) as total_products'),
                // Get total available quantity
                DB::raw('COALESCE(SUM(
                CASE WHEN ps.id IN (
                    SELECT MAX(id)
                    FROM product_stocks
                    WHERE product_id = ps.product_id
                    AND created_at <= ?
                    GROUP BY product_id
                )
                THEN ps.available_quantity
                ELSE 0
                END
            ), 0) as total_quantity'),
                // Calculate total value using weighted average cost
                DB::raw('COALESCE(SUM(
                CASE WHEN ps.id IN (
                    SELECT MAX(id)
                    FROM product_stocks
                    WHERE product_id = ps.product_id
                    AND created_at <= ?
                    GROUP BY product_id
                )
                THEN (
                    ps.available_quantity * (
                        SELECT
                            CASE
                                WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                                THEN SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                                     SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                                ELSE 0
                            END
                        FROM product_stocks ps2
                        WHERE ps2.product_id = ps.product_id
                        AND ps2.created_at <= ?
                    )
                )
                ELSE 0
                END
            ), 0) as total_value'),
            ]);

        $currentStockValue = $stockValueQuery->clone()
            ->setBindings([$endDate, $endDate, $endDate])
            ->first();

        $previousStockValue = $stockValueQuery->clone()
            ->setBindings([$previousEndDate, $previousEndDate, $previousEndDate])
            ->first();

        // Get Bank data - FIXED to calculate proper balances
        $bankAccounts = BankAccount::where('status', true)
            ->select('id', 'account_name', 'opening_balance')
            ->get();

        // Calculate actual bank balances up to end date
        $bankSummary = $bankAccounts->map(function ($account) use ($endDate, $startDate) {
            // Calculate balance at end date
            $currentBalance = $account->opening_balance +
                DB::table('bank_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('date', '<=', $endDate)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('CASE
                        WHEN transaction_type = "in" THEN amount
                        ELSE -amount
                    END'));

            // Get period transactions
            $periodTransactionsIn = DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('transaction_type', 'in')
                ->whereNull('deleted_at')
                ->sum('amount');

            $periodTransactionsOut = DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('transaction_type', 'out')
                ->whereNull('deleted_at')
                ->sum('amount');

            return [
                'id' => $account->id,
                'name' => $account->account_name,
                'balance' => $currentBalance,
                'period_in' => $periodTransactionsIn,
                'period_out' => $periodTransactionsOut,
            ];
        });

        $bankTransactions = BankTransaction::with([
            'bankAccount' => function ($query) {
                $query->select('id', 'account_name')
                    ->where('status', true);
            },
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->select('id', 'bank_account_id', 'transaction_type', 'amount', 'date', 'description')
            ->whereHas('bankAccount', function ($query) {
                $query->where('status', true);
            })
            ->orderBy('date', 'desc')
            ->get();

        // Calculate daily summary
        $dailySummary = DB::table('sales as s')
            ->leftJoin('sale_items as si', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(s.created_at) as date'),
                DB::raw('COUNT(DISTINCT s.id) as total_sales'),
                DB::raw('SUM(s.total) as total_amount'),
                DB::raw('SUM(s.paid) as total_paid'),
                DB::raw('SUM(s.due) as total_due'),
                DB::raw('SUM((si.unit_price - COALESCE(p.cost_price, 0)) * si.quantity) as daily_profit')
            )
            ->groupBy(DB::raw('DATE(s.created_at)'))
            ->orderBy('date')
            ->get();

        // Calculate low stock count
        $lowStockCount = DB::table('product_stocks as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->whereIn('ps.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->groupBy('product_id');
            })
            ->whereRaw('(
                SELECT SUM(quantity)
                FROM product_stocks
                WHERE product_id = p.id
            ) <= p.alert_quantity')
            ->count();

        // Prepare summary statistics
        $extraIncomeTotal = $extraIncomeData->sum('amount');
        $operatingExpenses = $expensesData->sum('amount');
        $currentNetProfit = $salesProfit + $extraIncomeTotal - $operatingExpenses;
        $previousNetProfit = $previousSalesProfit + $previousExtraIncome - $previousExpenses;
        $stockWorth = $this->calculateStockWorth();

        $summary = [
            'sales' => [
                'total_amount' => $salesData->sum('total'),
                'total_paid' => $salesData->sum('paid'),
                'total_due' => $salesData->sum('due'),
                'total_transactions' => $salesData->count(),
                'average_sale' => $salesData->count() > 0 ?
                    round($salesData->sum('total') / $salesData->count(), 2) : 0,
            ],
            'profit' => [
                'gross_profit' => $salesProfit,
                'extra_income' => $extraIncomeTotal,
                'operating_expenses' => $operatingExpenses,
                'net_profit' => $currentNetProfit,
                'previous_period_profit' => $previousNetProfit,
            ],
            'banking' => [
                'total_balance' => $bankSummary->sum('balance'),
                'accounts' => $bankSummary->map(function ($account) {
                    return [
                        'name' => $account['name'],
                        'balance' => $account['balance'],
                    ];
                })->values(),
                'total_transactions_in' => $bankSummary->sum('period_in'),
                'total_transactions_out' => $bankSummary->sum('period_out'),
            ],
            'stock' => [
                'current_value' => round($currentStockValue->total_value ?? 0, 2),
                'previous_value' => round($previousStockValue->total_value ?? 0, 2),
                'total_products' => $currentStockValue->total_products,
                'low_stock_count' => $lowStockCount,
                'potential_value' => round($stockWorth->potential_value ?? 0, 2),
                'potential_profit' => round($stockWorth->potential_profit ?? 0, 2),
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $daysDiff + 1,
            ],
        ];

        return Inertia::render('Admin/Dashboard/Index', [
            'salesData' => $salesData,
            'expensesData' => $expensesData,
            'bankTransactions' => $bankTransactions,
            'extraIncomeData' => $extraIncomeData,
            'summary' => $summary,
            'dailySummary' => $dailySummary,
            'filters' => [
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ],
        ]);
    }

    private function calculateBankingSummary($startDate, $endDate)
    {
        // First get all active bank accounts
        $bankAccounts = BankAccount::where('status', true)
            ->select('id', 'account_name', 'opening_balance')
            ->get();

        return $bankAccounts->map(function ($account) use ($startDate, $endDate) {
            // Calculate previous balance (before start date)
            $previousBalance = $account->opening_balance +
                DB::table('bank_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('date', '<', $startDate)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('CASE
                    WHEN transaction_type = "in" THEN amount
                    ELSE -amount
                END'));

            // Get current period transactions
            $periodTransactionsIn = DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('transaction_type', 'in')
                ->whereNull('deleted_at')
                ->sum('amount');

            $periodTransactionsOut = DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('transaction_type', 'out')
                ->whereNull('deleted_at')
                ->sum('amount');

            // Calculate current balance (up to end date)
            $currentBalance = $account->opening_balance +
                DB::table('bank_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('date', '<=', $endDate)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('CASE
                    WHEN transaction_type = "in" THEN amount
                    ELSE -amount
                END'));

            return [
                'account_name' => $account->account_name,
                'previous_balance' => $previousBalance,
                'current_balance' => $currentBalance,
                'transactions_in' => $periodTransactionsIn,
                'transactions_out' => $periodTransactionsOut,
                'net_change' => $periodTransactionsIn - $periodTransactionsOut,
            ];
        })->values();
    }

    private function calculateDailySummary($startDate, $endDate)
    {
        return DB::table('sales as s')
            ->leftJoin('sale_items as si', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(s.created_at) as date'),
                DB::raw('COUNT(DISTINCT s.id) as total_sales'),
                DB::raw('SUM(s.total) as total_amount'),
                DB::raw('SUM(s.paid) as total_paid'),
                DB::raw('SUM(s.due) as total_due'),
                DB::raw('SUM((si.unit_price - COALESCE(p.cost_price, 0)) * si.quantity) as daily_profit')
            )
            ->groupBy(DB::raw('DATE(s.created_at)'))
            ->orderBy('date')
            ->get();
    }

    private function calculateSalesProfit($salesData)
    {
        return $salesData->sum(function ($sale) {
            return $sale->saleItems->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;

                return ($item->unit_price - $costPrice) * $item->quantity;
            });
        });
    }

    private function getLowStockCount()
    {
        return DB::table('product_stocks as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->whereIn('ps.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->groupBy('product_id');
            })
            ->whereRaw('(
                SELECT SUM(quantity)
                FROM product_stocks
                WHERE product_id = p.id
            ) <= p.alert_quantity')
            ->count();
    }

    private function calculatePreviousPeriodMetrics($startDate, $endDate)
    {
        $daysDiff = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($daysDiff + 1);
        $previousEndDate = $endDate->copy()->subDays($daysDiff + 1);

        // Calculate previous period sales profit
        $previousSalesProfit = $this->calculateSalesProfit(
            Sale::whereBetween('created_at', [$previousStartDate, $previousEndDate])->get()
        );

        // Calculate previous period extra income
        $previousExtraIncome = ExtraIncome::whereBetween('date', [$previousStartDate, $previousEndDate])
            ->sum('amount');

        // Calculate previous period expenses
        $previousExpenses = Expense::whereBetween('date', [$previousStartDate, $previousEndDate])
            ->whereHas('expenseCategory', function ($query) {
                $query->where('name', '!=', 'Fixed Asset');
            })
            ->sum('amount');

        // Calculate previous period stock value
        $previousStockValue = $this->calculateStockValue($previousEndDate);

        return [
            'sales_profit' => $previousSalesProfit,
            'extra_income' => $previousExtraIncome,
            'expenses' => $previousExpenses,
            'net_profit' => $previousSalesProfit + $previousExtraIncome - $previousExpenses,
            'stock_value' => $previousStockValue,
        ];
    }

    private function calculateStockValue()
    {
        return DB::table('products as p')
            ->leftJoin('product_stocks as ps', function ($join) {
                $join->on('p.id', '=', 'ps.product_id')
                    ->whereRaw('ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        GROUP BY product_id
                    )');
            })
            ->select([
                DB::raw('COUNT(DISTINCT p.id) as total_products'),
                DB::raw('SUM(p.cost_price * COALESCE((
                    SELECT SUM(quantity)
                    FROM product_stocks
                    WHERE product_id = p.id
                ), 0)) as total_cost_value'),
                DB::raw('SUM(p.selling_price * COALESCE((
                    SELECT SUM(quantity)
                    FROM product_stocks
                    WHERE product_id = p.id
                ), 0)) as total_selling_value'),
                DB::raw('COUNT(CASE WHEN COALESCE((
                    SELECT SUM(quantity)
                    FROM product_stocks
                    WHERE product_id = p.id
                ), 0) <= p.alert_quantity THEN 1 END) as low_stock_count'),
            ])
            ->where('p.status', true)
            ->first();
    }

    private function calculateStockWorth()
    {
        return DB::table('product_stocks as ps')
            ->select([
                // Calculate potential value using available quantity
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        GROUP BY product_id
                    )
                    THEN (
                        ps.available_quantity * (
                            SELECT p.selling_price
                            FROM products p
                            WHERE p.id = ps.product_id
                        )
                    )
                    ELSE 0
                    END
                ), 0) as potential_value'),
                // Calculate potential profit using weighted average
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        GROUP BY product_id
                    )
                    THEN (
                        ps.available_quantity * (
                            (
                                SELECT p.selling_price
                                FROM products p
                                WHERE p.id = ps.product_id
                            ) - (
                                SELECT
                                    CASE
                                        WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                                        THEN SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                                             SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                                        ELSE 0
                                    END
                                FROM product_stocks ps2
                                WHERE ps2.product_id = ps.product_id
                            )
                        )
                    )
                    ELSE 0
                    END
                ), 0) as potential_profit'),
            ])
            ->first();
    }
}
