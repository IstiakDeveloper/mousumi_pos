<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class IncomeExpenditureController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // Get previous day for opening calculations
        $previousDay = Carbon::parse($startDate)->subDay()->endOfDay();

        // 1. Expenditure Section (Excluding Fixed Assets)
        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();

        // Get expense categories and their totals
        $expenseCategories = ExpenseCategory::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
                return $query->where('id', '!=', $fixedAssetsCategory->id);
            })
            ->where('status', true)
            ->get()
            ->map(function ($category) use ($startDate, $endDate, $previousDay) {
                // Get period expenses
                $periodExpenses = Expense::where('expense_category_id', $category->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('amount');

                // Get opening expenses (cumulative up to start date)
                $openingExpenses = Expense::where('expense_category_id', $category->id)
                    ->where('date', '<=', $previousDay)
                    ->sum('amount');

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'period' => $periodExpenses,
                    'cumulative' => $openingExpenses + $periodExpenses
                ];
            });

        $totalExpenses = [
            'period' => $expenseCategories->sum('period'),
            'cumulative' => $expenseCategories->sum('cumulative')
        ];

        // 2. Income Section

        // 2.1 Calculate Sales Profit

        // a. Get Sales Revenue
        $salesData = [
            'period' => Sale::whereBetween('created_at', [$startDate, $endDate])
                ->sum('total'),
            'opening' => Sale::where('created_at', '<=', $previousDay)
                ->sum('total')
        ];

        // b. Get Cost of Goods Sold for the period
        $costOfGoodsSold = $this->calculateCostOfGoodsSold($startDate, $endDate, $previousDay);

        $salesProfit = [
            'period' => $salesData['period'] - $costOfGoodsSold['period'],
            'cumulative' => ($salesData['period'] + $salesData['opening']) -
                          ($costOfGoodsSold['period'] + $costOfGoodsSold['opening'])
        ];

        // 2.2 Get Extra Income
        $extraIncomeData = [
            'period' => ExtraIncome::whereBetween('date', [$startDate, $endDate])
                ->sum('amount'),
            'opening' => ExtraIncome::where('date', '<=', $previousDay)
                ->sum('amount')
        ];

        // Calculate Total Income
        $totalIncome = [
            'period' => $salesProfit['period'] + $extraIncomeData['period'],
            'cumulative' => $salesProfit['cumulative'] +
                          ($extraIncomeData['period'] + $extraIncomeData['opening'])
        ];

        $data = [
            'expenditure' => [
                'categories' => $expenseCategories,
                'total' => $totalExpenses
            ],
            'income' => [
                'sales_profit' => [
                    'revenue' => [
                        'period' => $salesData['period'],
                        'cumulative' => $salesData['period'] + $salesData['opening']
                    ],
                    'cost_of_goods_sold' => [
                        'period' => $costOfGoodsSold['period'],
                        'cumulative' => $costOfGoodsSold['period'] + $costOfGoodsSold['opening']
                    ],
                    'profit' => $salesProfit
                ],
                'extra_income' => [
                    'period' => $extraIncomeData['period'],
                    'cumulative' => $extraIncomeData['period'] + $extraIncomeData['opening']
                ],
                'total' => $totalIncome
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ];

        return Inertia::render('Admin/Reports/IncomeExpenditure', $data);
    }

    private function calculateCostOfGoodsSold($startDate, $endDate, $previousDay = null)
    {
        // Calculate COGS for period
        $periodCOGS = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
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

        // Calculate opening COGS if previousDay is provided
        $openingCOGS = 0;
        if ($previousDay) {
            $openingCOGS = DB::table('sale_items as si')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->where('s.created_at', '<=', $previousDay)
                ->select(
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

        return [
            'period' => $periodCOGS,
            'opening' => $openingCOGS
        ];
    }
}
