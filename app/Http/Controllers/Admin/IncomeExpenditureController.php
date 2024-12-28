<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\ExtraIncome;
use App\Models\Expense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IncomeExpenditureController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Current Month Date Range
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Cumulative Date Range (from January to selected month)
        $yearStartDate = Carbon::createFromDate($year, 1, 1)->startOfYear();

        // Current Month Sales Data & Profit
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product'])
            ->get();
        $monthlyProfit = $this->calculateSalesProfit($salesData);

        // Current Month Extra Income
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->with('bankAccount:id,account_name')
            ->latest('date')
            ->get();
        $monthlyExtraIncome = $extraIncome->sum('amount');

        // Current Month Expenses
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with([
                'expenseCategory:id,name',
                'bankAccount:id,account_name'
            ])
            ->latest('date')
            ->get();
        $monthlyExpenses = $expenses->sum('amount');

        // Calculate Monthly Totals
        $monthlyTotalIncome = $monthlyProfit + $monthlyExtraIncome;

        // Cumulative Calculations (January to selected month)
        $cumulativeSales = Sale::whereBetween('created_at', [$yearStartDate, $endDate])
            ->with(['saleItems.product'])
            ->get();
        $cumulativeProfit = $this->calculateSalesProfit($cumulativeSales);

        // Cumulative Extra Income
        $cumulativeExtraIncome = ExtraIncome::whereBetween('date', [$yearStartDate, $endDate])
            ->sum('amount');

        // Cumulative Expenses
        $cumulativeExpenses = Expense::whereBetween('date', [$yearStartDate, $endDate])
            ->sum('amount');

        // Calculate Cumulative Totals
        $cumulativeTotalIncome = $cumulativeProfit + $cumulativeExtraIncome;

        // Prepare Summary
        $summary = [
            'monthly' => [
                'sales_profit' => $monthlyProfit,
                'extra_income' => $monthlyExtraIncome,
                'total_income' => $monthlyTotalIncome,
                'expenses' => $monthlyExpenses,
                'net_profit' => $monthlyTotalIncome - $monthlyExpenses
            ],
            'cumulative' => [
                'sales_profit' => $cumulativeProfit,
                'extra_income' => $cumulativeExtraIncome,
                'total_income' => $cumulativeTotalIncome,
                'expenses' => $cumulativeExpenses,
                'net_profit' => $cumulativeTotalIncome - $cumulativeExpenses
            ]
        ];

        return Inertia::render('Admin/Reports/IncomeExpenditure', [
            'extraIncome' => $extraIncome,
            'expenses' => $expenses,
            'summary' => $summary,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'monthName' => Carbon::createFromDate($year, $month, 1)->format('F')
            ]
        ]);
    }

    private function calculateSalesProfit($sales)
    {
        return $sales->reduce(function ($profit, $sale) {
            return $profit + $sale->saleItems->reduce(function ($itemProfit, $item) {
                $costPrice = $item->product->cost_price ?? 0;
                return $itemProfit + ($item->unit_price - $costPrice) * $item->quantity;
            }, 0);
        }, 0);
    }

    public function downloadPdf(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Current Month Date Range
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Cumulative Date Range (from January to selected month)
        $yearStartDate = Carbon::createFromDate($year, 1, 1)->startOfYear();

        // Get all required data similar to index method
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product'])
            ->get();
        $monthlyProfit = $this->calculateSalesProfit($salesData);

        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->with('bankAccount:id,account_name')
            ->latest('date')
            ->get();
        $monthlyExtraIncome = $extraIncome->sum('amount');

        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with([
                'expenseCategory:id,name',
                'bankAccount:id,account_name'
            ])
            ->latest('date')
            ->get();
        $monthlyExpenses = $expenses->sum('amount');

        // Calculate Monthly Totals
        $monthlyTotalIncome = $monthlyProfit + $monthlyExtraIncome;

        // Cumulative Calculations
        $cumulativeSales = Sale::whereBetween('created_at', [$yearStartDate, $endDate])
            ->with(['saleItems.product'])
            ->get();
        $cumulativeProfit = $this->calculateSalesProfit($cumulativeSales);

        $cumulativeExtraIncome = ExtraIncome::whereBetween('date', [$yearStartDate, $endDate])
            ->sum('amount');

        $cumulativeExpenses = Expense::whereBetween('date', [$yearStartDate, $endDate])
            ->sum('amount');

        $cumulativeTotalIncome = $cumulativeProfit + $cumulativeExtraIncome;

        // Prepare Summary
        $summary = [
            'monthly' => [
                'sales_profit' => $monthlyProfit,
                'extra_income' => $monthlyExtraIncome,
                'total_income' => $monthlyTotalIncome,
                'expenses' => $monthlyExpenses,
                'net_profit' => $monthlyTotalIncome - $monthlyExpenses
            ],
            'cumulative' => [
                'sales_profit' => $cumulativeProfit,
                'extra_income' => $cumulativeExtraIncome,
                'total_income' => $cumulativeTotalIncome,
                'expenses' => $cumulativeExpenses,
                'net_profit' => $cumulativeTotalIncome - $cumulativeExpenses
            ]
        ];

        $data = [
            'extraIncome' => $extraIncome,
            'expenses' => $expenses,
            'summary' => $summary,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'monthName' => Carbon::createFromDate($year, $month, 1)->format('F')
            ]
        ];

        $pdf = PDF::loadView('reports.income-expenditure', $data);
        $pdf->setPaper('a4');

        return $pdf->download('income-expenditure-report-' . $data['filters']['monthName'] . '-' . $year . '.pdf');
    }
}
