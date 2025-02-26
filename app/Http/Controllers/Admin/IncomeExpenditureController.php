<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtraIncomeCategory;
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
        // Get all category IDs that have ever had a transaction
        $allCategoryIds = ExtraIncome::select('category_id')
            ->distinct()
            ->whereNotNull('category_id')
            ->pluck('category_id');

        // Get current period data
        $currentPeriodData = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->pluck('total_amount', 'category_id')
            ->toArray();

        // Create the results array with all categories
        $extraIncomeCategories = collect();

        foreach ($allCategoryIds as $categoryId) {
            // Get the category name
            $category = ExtraIncomeCategory::find($categoryId);
            $categoryName = $category ? $category->name : 'Uncategorized';

            // Calculate cumulative amount
            $cumulative = ExtraIncome::where('category_id', $categoryId)
                ->where('date', '<=', $endDate)
                ->sum('amount');

            // Get period amount (0 if not present in current period)
            $periodAmount = $currentPeriodData[$categoryId] ?? 0;

            // Add to results only if cumulative amount exists
            if ($cumulative > 0) {
                $extraIncomeCategories->push([
                    'name' => $categoryName,
                    'period' => (float) $periodAmount,
                    'cumulative' => (float) $cumulative
                ]);
            }
        }

        // Handle uncategorized income
        $uncategorizedPeriod = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->whereNull('category_id')
            ->sum('amount');

        $uncategorizedCumulative = ExtraIncome::whereNull('category_id')
            ->where('date', '<=', $endDate)
            ->sum('amount');

        if ($uncategorizedCumulative > 0) {
            $extraIncomeCategories->push([
                'name' => 'Uncategorized',
                'period' => (float) $uncategorizedPeriod,
                'cumulative' => (float) $uncategorizedCumulative
            ]);
        }

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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'selected_month' => 'nullable|integer|min:1|max:12',
            'selected_year' => 'nullable|integer'
        ]);

        // Parse the provided dates
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Extract month and year - use the directly provided values if available
        if ($request->has('selected_month') && $request->has('selected_year')) {
            $month = (int) $request->selected_month;
            $year = (int) $request->selected_year;

            // Get the month name using Carbon
            $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        } else {
            // Fallback to extracting from the date
            $month = $startDate->month;
            $year = $startDate->year;
            $monthName = $startDate->format('F');
        }

        // Double-check that we have the correct month
        // If the start date is the last day of the previous month due to timezone issues,
        // adjust to use the first day of the intended month
        if ($month != $startDate->month) {
            // Adjust start date to be the first day of the intended month
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();

            // If end date also needs adjustment, set it to the last day of the intended month
            if ($month != $endDate->month) {
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
            }
        }

        // Calculate Income Section
        $incomeData = $this->calculateIncome($startDate, $endDate);

        // Calculate Expenditure Section
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        // Prepare data for PDF - using same structure as index, but with added month info
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'year' => $year,
                'month' => $month,
                'month_name' => $monthName
            ]
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.income-expenditure', $data);

        // Generate filename with month and year
        $filename = 'income-expenditure-' . $monthName . '-' . $year . '.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
