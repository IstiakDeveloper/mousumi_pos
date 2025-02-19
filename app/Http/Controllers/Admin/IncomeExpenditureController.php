<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\ExpenseCategory;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class IncomeExpenditureController extends Controller
{
    public function index(Request $request)
    {
        // Validate and set date range
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Set default to current month if no dates provided
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        // Calculate Income Section
        $incomeData = $this->calculateIncome($startDate, $endDate);

        // Calculate Expenditure Section
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        // Prepare data for Inertia render
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ];

        return Inertia::render('Admin/Reports/IncomeExpenditure', $data);
    }

    private function calculateIncome(Carbon $startDate, Carbon $endDate)
    {
        $productAnalysis = Product::getProductAnalysis($startDate, $endDate);
        $extraIncomeAnalysis = $this->calculateExtraIncome($startDate, $endDate);

        return [
            'sales_profit' => [
                'period' => (float) $productAnalysis['totals']['total_profit'],
                'cumulative' => (float) Product::getCumulativeTotals()['total_profit']['cumulative'],
            ],
            'extra_income' => $extraIncomeAnalysis,
            'total' => [
                'period' => (float) $productAnalysis['totals']['total_profit'] + $extraIncomeAnalysis['total']['period'],
                'cumulative' => (float) Product::getCumulativeTotals()['total_profit']['cumulative'] + $extraIncomeAnalysis['total']['cumulative'],
            ],
        ];
    }

    private function calculateExtraIncome(Carbon $startDate, Carbon $endDate)
    {
        $extraIncomeCategories = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($income) {
                return [
                    'name' => $income->category ? $income->category->name : 'Uncategorized',
                    'period' => (float) $income->total_amount,
                    'cumulative' => (float) ExtraIncome::where('category_id', $income->category_id)
                        ->where('date', '<=', Carbon::now()->endOfDay())
                        ->sum('amount')
                ];
            });

        return [
            'categories' => $extraIncomeCategories,
            'total' => [
                'period' => (float) $extraIncomeCategories->sum('period'),
                'cumulative' => (float) $extraIncomeCategories->sum('cumulative'),
            ],
        ];
    }

    private function calculateExpenditure(Carbon $startDate, Carbon $endDate)
    {
        // Exclude Fixed Assets category
        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();

        // Get all active expense categories
        $expenseCategories = ExpenseCategory::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
            return $query->where('id', '!=', $fixedAssetsCategory->id);
        })
            ->where('status', true)
            ->get();

        // Prepare categories with their expenses
        $categoriesWithExpenses = $expenseCategories->map(function ($category) use ($startDate, $endDate) {
            // Period expenses (current month)
            $periodExpenses = Expense::where('expense_category_id', $category->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            // Cumulative expenses (up to end date)
            $cumulativeExpenses = Expense::where('expense_category_id', $category->id)
                ->where('date', '<=', Carbon::now()->endOfDay())
                ->sum('amount');

            return [
                'name' => $category->name,
                'period' => (float) $periodExpenses,
                'cumulative' => (float) $cumulativeExpenses,
            ];
        });

        // Calculate total expenses
        $totalExpensesPeriod = Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
            return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalExpensesCumulative = Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
            return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
        })
            ->where('date', '<=', Carbon::now()->endOfDay())
            ->sum('amount');

        return [
            'categories' => $categoriesWithExpenses,
            'total' => [
                'period' => (float) $totalExpensesPeriod,
                'cumulative' => (float) $totalExpensesCumulative,
            ],
        ];
    }


    public function downloadPdf(Request $request)
    {
        // Validate and set date range
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Set default to current month if no dates provided
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        // Calculate Income Section
        $incomeData = $this->calculateIncome($startDate, $endDate);

        // Calculate Expenditure Section
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        // Prepare data for PDF - using same structure as index
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.income-expenditure', $data);

        // Generate filename
        $filename = 'income-expenditure-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
