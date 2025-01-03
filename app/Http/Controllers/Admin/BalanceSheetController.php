<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Fund;
use App\Models\BankAccount;
use App\Models\ProductStock;
use App\Models\ExtraIncome;
use App\Models\Expense;
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
        // Get Funds In
        $fundsIn = Fund::where('type', 'in')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Calculate Net Profit
        $salesProfit = $this->calculateSalesProfit($startDate, $endDate);
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $netProfit = $salesProfit + $extraIncome - $expenses;

        // Get Total Investment/Capital
        $totalInvestment = Fund::where('type', 'in')
            ->where('date', '<=', $endDate)
            ->sum('amount');

        return [
            'current_month' => [
                'funds_in' => $fundsIn,
                'net_profit' => $netProfit,
                'total' => $fundsIn + $netProfit
            ],
            'cumulative' => [
                'total_investment' => $totalInvestment,
                'retained_earnings' => $this->calculateRetainedEarnings($yearStartDate, $endDate),
                'total' => $totalInvestment + $this->calculateRetainedEarnings($yearStartDate, $endDate)
            ]
        ];
    }

    private function calculatePropertyAndAssets($startDate, $endDate)
    {
        // Calculate Bank Balances
        $bankBalances = BankAccount::select('id', 'account_name', 'current_balance')
            ->where('status', true)
            ->get()
            ->map(function ($account) {
                return [
                    'account_name' => $account->account_name,
                    'balance' => $account->current_balance
                ];
            });

        // Calculate Total Bank Balance
        $totalBankBalance = $bankBalances->sum('balance');

        // Calculate Customer Due
        $totalDue = Sale::where('payment_status', '!=', 'paid')
            ->where('created_at', '<=', $endDate)
            ->sum('due');

        // Calculate Stock Value
        $stockValue = ProductStock::select(
            'product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total_cost) as stock_value')  // Changed to use total_cost directly
        )
            ->groupBy('product_id')
            ->get()
            ->sum('stock_value');

        return [
            'bank_accounts' => $bankBalances,
            'total_bank_balance' => $totalBankBalance,
            'customer_due' => $totalDue,
            'stock_value' => $stockValue,
            'total' => $totalBankBalance + $totalDue + $stockValue
        ];
    }

    private function calculateSalesProfit($startDate, $endDate)
    {
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product'])
            ->get();

        return $sales->reduce(function ($profit, $sale) {
            return $profit + $sale->saleItems->reduce(function ($itemProfit, $item) {
                $costPrice = $item->product->cost_price ?? 0;
                return $itemProfit + ($item->unit_price - $costPrice) * $item->quantity;
            }, 0);
        }, 0);
    }

    private function calculateRetainedEarnings($startDate, $endDate)
    {
        $salesProfit = $this->calculateSalesProfit($startDate, $endDate);
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');

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
