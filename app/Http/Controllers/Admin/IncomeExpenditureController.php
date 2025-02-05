<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\ExpenseCategory;
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
        // 1. Sales Revenue
        $salesRevenuePeriod = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $salesRevenueCumulative = Sale::where('created_at', '<=', Carbon::now()->endOfDay())
            ->sum('total');

        // 2. Cost of Goods Sold
        $costOfGoodsSoldPeriod = $this->calculateCostOfGoodsSold($startDate, $endDate);
        $costOfGoodsSoldCumulative = $this->calculateCostOfGoodsSold(null, Carbon::now()->endOfDay());

        // 3. Sales Profit
        $salesProfitPeriod = $salesRevenuePeriod - $costOfGoodsSoldPeriod;
        $salesProfitCumulative = $salesRevenueCumulative - $costOfGoodsSoldCumulative;

        // 4. Extra Income
        $extraIncomePeriod = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $extraIncomeCumulative = ExtraIncome::where('date', '<=', Carbon::now()->endOfDay())
            ->sum('amount');

        // 5. Total Income
        $totalIncomePeriod = $salesProfitPeriod + $extraIncomePeriod;
        $totalIncomeCumulative = $salesProfitCumulative + $extraIncomeCumulative;

        return [
            'sales_profit' => [
                'period' => (float) $salesProfitPeriod,
                'cumulative' => (float) $salesProfitCumulative,
            ],
            'extra_income' => [
                'period' => (float) $extraIncomePeriod,
                'cumulative' => (float) $extraIncomeCumulative,
            ],
            'total' => [
                'period' => (float) $totalIncomePeriod,
                'cumulative' => (float) $totalIncomeCumulative,
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

    private function calculateCostOfGoodsSold(?Carbon $startDate, Carbon $endDate)
    {
        $query = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.created_at', '<=', $endDate);

        // Add start date condition if provided
        if ($startDate) {
            $query->where('s.created_at', '>=', $startDate);
        }

        return $query->select(
            DB::raw('SUM(
                si.quantity * (
                    SELECT
                        CASE
                            WHEN SUM(ps2.quantity) > 0
                            THEN SUM(ps2.total_cost) / SUM(ps2.quantity)
                            ELSE 0
                        END
                    FROM product_stocks ps2
                    WHERE ps2.product_id = si.product_id
                    AND ps2.type = "purchase"
                    AND ps2.created_at <= s.created_at
                )
            ) as total_cost')
        )
            ->first()
            ->total_cost ?? 0;
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

        // Prepare data for PDF
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.income-expenditure', $data);

        // Generate filename
        $filename = 'income-expenditure-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
