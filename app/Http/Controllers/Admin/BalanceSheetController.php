<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fund;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\BankAccount;
use App\Models\ExtraIncome;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // Get previous day for opening balance
        $previousDay = Carbon::parse($startDate)->subDay()->endOfDay();

        // 1. Fund & Liabilities Section

        // 1.1 Fund Calculations
        $fundBalances = [
            'opening' => Fund::where('type', 'in')
                ->where('date', '<', $startDate)
                ->sum('amount'),
            'period' => Fund::where('type', 'in')
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount')
        ];

        $totalFund = $fundBalances['opening'] + $fundBalances['period'];

        // 1.2 Net Profit Calculations

        // a. Sales Calculations
        $salesData = [
            'opening' => Sale::where('created_at', '<', $startDate)->sum('total'),
            'period' => Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total')
        ];

        // b. Extra Income Calculations
        $extraIncomeData = [
            'opening' => ExtraIncome::where('date', '<', $startDate)->sum('amount'),
            'period' => ExtraIncome::whereBetween('date', [$startDate, $endDate])->sum('amount')
        ];

        // c. Regular Expenses (Excluding Fixed Assets)
        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();

        $expensesData = [
            'opening' => Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
                    return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
                })
                ->where('date', '<', $startDate)
                ->sum('amount'),
            'period' => Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
                    return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount')
        ];

        // d. Cost of Goods Sold Calculations
        $costOfGoodsSold = [
            'opening' => $this->calculateCostOfGoodsSold(null, $previousDay),
            'period' => $this->calculateCostOfGoodsSold($startDate, $endDate)
        ];

        // Calculate Net Profits
        $openingNetProfit = $salesData['opening'] + $extraIncomeData['opening'] -
                           $expensesData['opening'] - $costOfGoodsSold['opening'];

        $periodNetProfit = $salesData['period'] + $extraIncomeData['period'] -
                          $expensesData['period'] - $costOfGoodsSold['period'];

        $totalNetProfit = $openingNetProfit + $periodNetProfit;

        // 2. Property & Assets Section

        // 2.1 Bank Balance Calculations
        $bankBalances = [
            'opening' => $this->calculateBankBalance(null, $previousDay),
            'period' => $this->calculateBankBalance($startDate, $endDate)
        ];

        $totalBankBalance = $bankBalances['opening'] + $bankBalances['period'];

        // 2.2 Fixed Assets
        $fixedAssets = $fixedAssetsCategory ?
            Expense::where('expense_category_id', $fixedAssetsCategory->id)
            ->sum('amount') : 0;

        // 2.3 Customer Due Calculations
        $customerDueData = [
            'opening' => Sale::where('created_at', '<', $startDate)->sum('due'),
            'period' => Sale::whereBetween('created_at', [$startDate, $endDate])->sum('due')
        ];

        $totalCustomerDue = $customerDueData['opening'] + $customerDueData['period'];

        // 2.4 Stock Value Calculations
        $stockValueData = [
            'opening' => $this->calculateStockValue($previousDay),
            'period' => $this->calculateStockValue($endDate)
        ];

        // Calculate Section Totals
        $fundAndLiabilitiesTotal = $totalFund + $totalNetProfit;
        $propertyAndAssetsTotal = $totalBankBalance + $fixedAssets + $totalCustomerDue + $stockValueData['period'];

        $data = [
            'fund_and_liabilities' => [
                'fund' => [
                    'opening' => $fundBalances['opening'],
                    'period' => $fundBalances['period'],
                    'total' => $totalFund
                ],
                'net_profit' => [
                    'opening' => $openingNetProfit,
                    'period' => $periodNetProfit,
                    'total' => $totalNetProfit,
                    'details' => [
                        'sales' => $salesData,
                        'extra_income' => $extraIncomeData,
                        'expenses' => $expensesData,
                        'cost_of_goods_sold' => $costOfGoodsSold
                    ]
                ],
                'total' => $fundAndLiabilitiesTotal
            ],
            'property_and_assets' => [
                'bank_balance' => [
                    'opening' => $bankBalances['opening'],
                    'period' => $bankBalances['period'],
                    'total' => $totalBankBalance
                ],
                'fixed_assets' => $fixedAssets,
                'customer_due' => [
                    'opening' => $customerDueData['opening'],
                    'period' => $customerDueData['period'],
                    'total' => $totalCustomerDue
                ],
                'stock_value' => [
                    'opening' => $stockValueData['opening'],
                    'period' => $stockValueData['period']
                ],
                'total' => $propertyAndAssetsTotal
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ];

        return Inertia::render('Admin/Reports/BalanceSheet', $data);
    }

    private function calculateCostOfGoodsSold($startDate = null, $endDate = null)
    {
        $query = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->select(
                'si.product_id',
                'si.quantity',
                's.created_at'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('s.created_at', [$startDate, $endDate]);
        } elseif ($endDate) {
            $query->where('s.created_at', '<=', $endDate);
        }

        $soldProducts = $query->get();

        $soldProductsCost = 0;
        foreach ($soldProducts as $sale) {
            $costAtSaleTime = DB::table('product_stocks as ps')
                ->where('product_id', $sale->product_id)
                ->where('created_at', '<=', $sale->created_at)
                ->where('type', 'purchase')
                ->select(DB::raw('
                    CASE
                        WHEN SUM(quantity) > 0
                        THEN CAST(SUM(total_cost) / SUM(quantity) AS DECIMAL(15,6))
                        ELSE 0
                    END as unit_cost
                '))
                ->first()
                ->unit_cost ?? 0;

            $soldProductsCost += $sale->quantity * $costAtSaleTime;
        }

        return $soldProductsCost;
    }

    private function calculateBankBalance($startDate = null, $endDate = null)
    {
        $query = DB::table('bank_transactions');

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->select(DB::raw('CAST(
            SUM(CASE
                WHEN transaction_type = "in" THEN amount
                ELSE -amount
            END) AS DECIMAL(15,6)
        ) as balance'))
        ->first()
        ->balance ?? 0;
    }

    private function calculateStockValue($date)
    {
        $stockPositions = DB::table('product_stocks as ps')
            ->select('ps.product_id')
            ->selectRaw('
                CASE
                    WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE product_id = ps.product_id
                        AND created_at <= ?
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                END as available_quantity
            ', [$date])
            ->selectRaw('
                CASE
                    WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                    THEN CAST(
                        SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                        SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                        AS DECIMAL(15,6)
                    )
                    ELSE 0
                END as weighted_avg_cost
            ')
            ->join('product_stocks as ps2', function($join) use ($date) {
                $join->on('ps2.product_id', '=', 'ps.product_id')
                    ->where('ps2.created_at', '<=', $date);
            })
            ->groupBy('ps.product_id', 'ps.id', 'ps.available_quantity')
            ->having('available_quantity', '>', 0)
            ->get();

        return $stockPositions->sum(function($stock) {
            return $stock->available_quantity * $stock->weighted_avg_cost;
        });
    }
}
