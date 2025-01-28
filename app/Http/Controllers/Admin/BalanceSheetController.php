<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Fund;
use App\Models\BankAccount;
use App\Models\ExtraIncome;
use App\Models\Expense;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $yearStartDate = Carbon::createFromDate($year, 1, 1)->startOfYear();

        // Calculate Funds and Liabilities
        $fundsAndLiabilities = $this->calculateFundsAndLiabilities($startDate, $endDate, $yearStartDate);

        // Calculate Property and Assets
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        return Inertia::render('Admin/Reports/BalanceSheet', [
            'fundsAndLiabilities' => $fundsAndLiabilities,
            'propertyAndAssets' => $propertyAndAssets,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'monthName' => Carbon::createFromDate($year, $month, 1)->format('F')
            ]
        ]);
    }

    private function calculateFundsAndLiabilities($startDate, $endDate, $yearStartDate)
    {
        // Get Funds In for current month
        $fundsIn = Fund::where('type', 'in')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Calculate Net Profit for current month
        $netProfit = $this->calculateMonthlyNetProfit($startDate, $endDate);

        // Get Total Investment/Capital up to end date
        $totalInvestment = Fund::where('type', 'in')
            ->where('date', '<=', $endDate)
            ->sum('amount');

        // Calculate retained earnings from year start to current month
        $retainedEarnings = $this->calculateRetainedEarnings($yearStartDate, $endDate);

        return [
            'current_month' => [
                'funds_in' => $fundsIn,
                'net_profit' => $netProfit,
                'total' => $fundsIn + $netProfit
            ],
            'cumulative' => [
                'total_investment' => $totalInvestment,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalInvestment + $retainedEarnings
            ]
        ];
    }

    private function calculatePropertyAndAssets($startDate, $endDate)
    {
        // Calculate Bank Balances (unchanged)
        $bankBalances = BankAccount::select('id', 'account_name', 'current_balance')
            ->where('status', true)
            ->get()
            ->map(function ($account) {
                return [
                    'account_name' => $account->account_name,
                    'balance' => $account->current_balance
                ];
            });

        $totalBankBalance = $bankBalances->sum('balance');

        // Calculate Customer Due (unchanged)
        $totalDue = Sale::where('payment_status', '!=', 'paid')
            ->where('created_at', '<=', $endDate)
            ->sum('due');

        // Calculate Stock Value using weighted average cost
        $stockValue = DB::table('product_stocks as ps')
            ->select([
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
                ), 0) as total_value')
            ])
            ->setBindings([$endDate, $endDate])
            ->value('total_value');

        // Calculate Fixed Assets (unchanged)
        $fixedAssets = DB::table('expenses as e')
            ->join('expense_categories as ec', 'e.expense_category_id', '=', 'ec.id')
            ->where('ec.name', 'Fixed Asset')
            ->where('e.date', '<=', $endDate)
            ->select([
                'ec.name as category',
                DB::raw('SUM(e.amount) as total_amount')
            ])
            ->groupBy('ec.name')
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_name' => 'Fixed Assets',
                    'amount' => $asset->total_amount
                ];
            });

        $totalFixedAssets = $fixedAssets->sum('amount');

        // Additional stock details for better reporting
        $stockDetails = DB::table('product_stocks as ps')
            ->select([
                DB::raw('COUNT(DISTINCT ps.product_id) as total_products'),
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
                ), 0) as total_quantity')
            ])
            ->setBindings([$endDate])
            ->first();

        return [
            'bank_accounts' => $bankBalances,
            'total_bank_balance' => $totalBankBalance,
            'customer_due' => $totalDue,
            'stock_value' => round($stockValue ?? 0, 2),
            'stock_details' => [
                'total_products' => $stockDetails->total_products,
                'total_quantity' => $stockDetails->total_quantity
            ],
            'fixed_assets' => $fixedAssets,
            'total_fixed_assets' => round($totalFixedAssets, 2),
            'total' => round($totalBankBalance + $totalDue + ($stockValue ?? 0) + $totalFixedAssets, 2)
        ];
    }

    private function calculateMonthlyNetProfit($startDate, $endDate)
    {
        // Calculate sales profit
        $salesProfit = $this->calculateSalesProfit($startDate, $endDate);

        // Add extra income
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Get Fixed Asset expense category ID
        $fixedAssetCategoryId = DB::table('expense_categories')
            ->where('name', 'Fixed Asset')
            ->value('id');

        // Calculate expenses excluding Fixed Assets using category ID
        $expenses = DB::table('expenses')
            ->where('expense_category_id', '!=', $fixedAssetCategoryId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return $salesProfit + $extraIncome - $expenses;
    }

    private function calculateSalesProfit($startDate, $endDate)
    {
        return DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select([
                DB::raw('SUM((si.unit_price * si.quantity) - (COALESCE(p.cost_price, 0) * si.quantity)) as total_profit')
            ])
            ->value('total_profit') ?? 0;
    }

    private function calculateRetainedEarnings($startDate, $endDate)
    {
        // Calculate total sales profit from start date
        $salesProfit = $this->calculateSalesProfit($startDate, $endDate);

        // Add total extra income
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Get Fixed Asset expense category ID
        $fixedAssetCategoryId = DB::table('expense_categories')
            ->where('name', 'Fixed Asset')
            ->value('id');

        // Calculate expenses excluding Fixed Assets using category ID
        $expenses = DB::table('expenses')
            ->where('expense_category_id', '!=', $fixedAssetCategoryId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return $salesProfit + $extraIncome - $expenses;
    }

    public function downloadPdf(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $yearStartDate = Carbon::createFromDate($year, 1, 1)->startOfYear();

        $fundsAndLiabilities = $this->calculateFundsAndLiabilities($startDate, $endDate, $yearStartDate);
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        $pdf = Pdf::loadView('reports.balance-sheet', [
            'fundsAndLiabilities' => $fundsAndLiabilities,
            'propertyAndAssets' => $propertyAndAssets,
            'month' => $months[$month - 1],
            'year' => $year,
            'dateRange' => [
                'start' => $startDate->format('d M Y'),
                'end' => $endDate->format('d M Y')
            ]
        ]);

        return $pdf->download("balance-sheet-{$year}-{$month}.pdf");
    }
}
