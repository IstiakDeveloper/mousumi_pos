<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\ExtraIncome;
use App\Models\Expense;
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

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get Sales Data with Profit
        $salesData = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with(['saleItems.product'])
            ->get();

        // Calculate Sales Profit
        $salesProfit = $salesData->reduce(function ($profit, $sale) {
            return $profit + $sale->saleItems->reduce(function ($itemProfit, $item) {
                $costPrice = $item->product->cost_price ?? 0;
                return $itemProfit + ($item->unit_price - $costPrice) * $item->quantity;
            }, 0);
        }, 0);

        // Get Extra Income
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->with('bankAccount:id,account_name')
            ->get();

        // Get Expenses
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with([
                'expenseCategory:id,name',
                'bankAccount:id,account_name'
            ])
            ->get();

        // Calculate summaries
        $totalProfit = $salesProfit + $extraIncome->sum('amount');
        $totalExpense = $expenses->sum('amount');

        $summary = [
            'sales_profit' => $salesProfit,
            'extra_income' => $extraIncome->sum('amount'),
            'total_profit' => $totalProfit,
            'total_expense' => $totalExpense,
            'net_profit' => $totalProfit - $totalExpense
        ];

        return Inertia::render('Admin/Reports/IncomeExpenditure', [
            'salesData' => $salesData,
            'extraIncome' => $extraIncome,
            'expenses' => $expenses,
            'summary' => $summary,
            'filters' => [
                'month' => (int)$month,
                'year' => (int)$year
            ]
        ]);
    }
}
