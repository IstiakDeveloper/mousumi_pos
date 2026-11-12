<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraIncome;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            : Carbon::now()->endOfMonth()->endOfDay();

        $sheet = $this->assembleSheet($endDate);

        // Prepare data for Inertia render
        $data = [
            'fund_and_liabilities' => $sheet['fund_and_liabilities'],
            'property_and_assets' => $sheet['property_and_assets'],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ];

        return Inertia::render('Admin/Reports/BalanceSheet', $data);
    }

    private function assembleSheet(Carbon $endDate): array
    {
        // Same cumulative window the sheet used before: first books date through the selected end date.
        $cumulativeStartDate = Carbon::parse('2024-12-29')->startOfDay();
        $totals = Product::getProductAnalysis($cumulativeStartDate, $endDate)['totals'];

        return [
            'fund_and_liabilities' => $this->calculateFundAndLiabilities($endDate, (float) $totals['total_profit']),
            'property_and_assets' => $this->calculatePropertyAndAssets($endDate, (float) $totals['available_stock_value']),
        ];
    }

    private function calculateFundAndLiabilities(Carbon $endDate, float $productTotalProfit)
    {
        $fundAmount = Fund::where('date', '<=', $endDate)
            ->whereIn('type', ['in', 'out'])
            ->get()
            ->reduce(function ($carry, $fund) {
                return $carry + ($fund->type === 'in' ? $fund->amount : -$fund->amount);
            }, 0);

        $extraIncomeAmount = ExtraIncome::where('date', '<=', $endDate)
            ->sum('amount');

        $fixedAssetsCategory = ExpenseCategory::where('name', 'Fixed Asset')->first();
        $expensesAmount = Expense::when($fixedAssetsCategory, function ($query) use ($fixedAssetsCategory) {
            return $query->where('expense_category_id', '!=', $fixedAssetsCategory->id);
        })
            ->where('date', '<=', $endDate)
            ->sum('amount');

        $netProfit = $productTotalProfit + $extraIncomeAmount - $expensesAmount;

        return [
            'fund' => [
                'period' => (float) $fundAmount,
            ],
            'net_profit' => [
                'period' => (float) $netProfit,
            ],
            'total' => (float) ($fundAmount + $netProfit),
        ];
    }

    private function calculatePropertyAndAssets(Carbon $endDate, float $stockValue)
    {
        $bankBalance = $this->calculateBankBalance($endDate);
        $customerDue = 0;

        $fixedAssets = (float) FixedAssetItem::query()
            ->where('status', 'active')
            ->whereDate('purchase_date', '<=', $endDate->toDateString())
            ->sum('current_value');

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
            'total' => (float) ($bankBalance + $customerDue + $fixedAssets + $stockValue),
        ];
    }

    private function calculateBankBalance(Carbon $endDate)
    {
        $openingBalance = (float) BankAccount::query()->sum('opening_balance');

        $transactionNet = (float) DB::table('bank_transactions')
            ->whereIn('bank_account_id', BankAccount::query()->select('id'))
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum(DB::raw("CASE WHEN transaction_type = 'in' THEN amount ELSE -amount END"));

        return $openingBalance + $transactionNet;
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
            : Carbon::now()->endOfMonth()->endOfDay();

        $sheet = $this->assembleSheet($endDate);

        // Prepare data for PDF
        $data = [
            'fund_and_liabilities' => $sheet['fund_and_liabilities'],
            'property_and_assets' => $sheet['property_and_assets'],
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.balance-sheet', $data);

        // Generate filename
        $filename = 'balance-sheet-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
