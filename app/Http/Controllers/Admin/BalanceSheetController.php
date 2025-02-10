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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller
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

        // Fund & Liabilities Section
        $fundAndLiabilities = $this->calculateFundAndLiabilities($startDate, $endDate);

        // Property & Assets Section
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        // Prepare data for Inertia render
        $data = [
            'fund_and_liabilities' => $fundAndLiabilities,
            'property_and_assets' => $propertyAndAssets,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ];

        return Inertia::render('Admin/Reports/BalanceSheet', $data);
    }


    private function calculateFundAndLiabilities(Carbon $startDate, Carbon $endDate)
    {
        // 1. Fund Calculation up to the end date
        $fundAmount = Fund::where('date', '<=', $endDate)
            ->whereIn('type', ['in', 'out'])
            ->get()
            ->reduce(function ($carry, $fund) {
                return $carry + ($fund->type === 'in' ? $fund->amount : -$fund->amount);
            }, 0);

        // Sales up to the end date
        $salesAmount = Sale::where('created_at', '<=', $endDate)
            ->sum('total');

        // Extra Income up to the end date
        $extraIncomeAmount = ExtraIncome::where('date', '<=', $endDate)
            ->sum('amount');

        // Expenses up to the end date (excluding fixed assets)
        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();
        $expensesAmount = Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
            return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
        })
            ->where('date', '<=', $endDate)
            ->sum('amount');

        // Cost of Goods Sold up to the end date
        $costOfGoodsSold = $this->calculateCostOfGoodsSold(null, $endDate);

        // Calculate Net Profit
        $netProfit = $salesAmount + $extraIncomeAmount - $expensesAmount - $costOfGoodsSold;

        // Total (Fund + Net Profit)
        $total = $fundAmount + $netProfit;

        return [
            'fund' => [
                'period' => (float) $fundAmount,
            ],
            'net_profit' => [
                'period' => (float) $netProfit,
            ],
            'total' => (float) $total,
        ];
    }


    private function calculatePropertyAndAssets(Carbon $startDate, Carbon $endDate)
    {
        // 1. Bank Balance up to the end date
        $bankBalance = $this->calculateBankBalance(null, $endDate);

        // 2. Customer Due up to the end date
        $customerDue = Sale::where('created_at', '<=', $endDate)
            ->sum('due');

        // 3. Fixed Assets up to the end date
        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();
        $fixedAssets = $fixedAssetsCategory
            ? Expense::where('expense_category_id', $fixedAssetsCategory->id)
                ->where('date', '<=', $endDate)
                ->sum('amount')
            : 0;

        // 4. Stock Value up to the end date
        $stockValue = $this->calculateStockValue($endDate);

        // Calculate Total
        $total = $bankBalance + $customerDue + $fixedAssets + $stockValue;

        return [
            'bank_balance' => [
                'period' => (float) $bankBalance,
            ],
            'customer_due' => [
                'period' => (float) $customerDue,
            ],
            'fixed_assets' => (float) $fixedAssets,
            'stock_value' => [
                'period' => (float) $stockValue,
            ],
            'total' => (float) $total,
        ];
    }

    private function calculateCostOfGoodsSold(?Carbon $startDate, Carbon $endDate)
    {
        $query = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.created_at', '<=', $endDate);

        // If start date is provided, add the start date condition
        if ($startDate) {
            $query->where('s.created_at', '>=', $startDate);
        }

        $soldProducts = $query->select(
            'si.product_id',
            'si.quantity',
            's.created_at'
        )->get();

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

    private function calculateBankBalance(?Carbon $startDate, Carbon $endDate)
    {
        $query = DB::table('bank_transactions')
            ->where('date', '<=', $endDate);

        // If start date is provided, add the start date condition
        if ($startDate) {
            $query->where('date', '>=', $startDate);
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

    private function calculateStockValue(Carbon $endDate)
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
            ', [$endDate])
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
            ->join('product_stocks as ps2', function ($join) use ($endDate) {
                $join->on('ps2.product_id', '=', 'ps.product_id')
                    ->where('ps2.created_at', '<=', $endDate);
            })
            ->groupBy('ps.product_id', 'ps.id', 'ps.available_quantity')
            ->having('available_quantity', '>', 0)
            ->get();

        return $stockPositions->sum(function ($stock) {
            return $stock->available_quantity * $stock->weighted_avg_cost;
        });
    }


    public function downloadPdf(Request $request)
    {
        // Validate and set date range (same as index method)
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

        // Fund & Liabilities Section
        $fundAndLiabilities = $this->calculateFundAndLiabilities($startDate, $endDate);

        // Property & Assets Section
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        // Prepare data for PDF
        $data = [
            'fund_and_liabilities' => $fundAndLiabilities,
            'property_and_assets' => $propertyAndAssets,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.balance-sheet', $data);

        // Generate filename
        $filename = 'balance-sheet-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
