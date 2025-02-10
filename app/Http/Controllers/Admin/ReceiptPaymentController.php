<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ExtraIncome;
use App\Models\Expense;
use App\Models\Fund;
use App\Models\ProductStock;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ReceiptPaymentController extends Controller
{
    protected $bankAccountId = 1;

    public function index(Request $request)
    {
        // Use request year and month if provided, otherwise use current date
        if ($request->filled(['year', 'month'])) {
            $date = Carbon::createFromDate($request->year, $request->month, 1);
        } else {
            $date = Carbon::now();
        }

        // Always use start and end of month
        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

        \Log::info('Date Range', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $receiptData = $this->getReceiptData($startDate, $endDate);
        $paymentData = $this->getPaymentData($startDate, $endDate);

        return Inertia::render('Admin/ReceiptPayment/Index', [
            'filters' => [
                'year' => $date->year,
                'month' => $date->month,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'receipt' => $receiptData,
            'payment' => $paymentData,
        ]);
    }

    protected function getReceiptData($startDate, $endDate)
    {
        // Get the bank account
        $bank = BankAccount::findOrFail($this->bankAccountId);

        // Get the latest bank transaction from previous month
        $prevMonthEndBalance = DB::table('bank_transactions')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('date', '<', $startDate)  // All transactions before current month
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')  // In case multiple transactions on same date
            ->first();

        // If we found a previous transaction, use its running balance
        $openingCashAtBank = $prevMonthEndBalance ? $prevMonthEndBalance->running_balance : $bank->opening_balance;


        // Get sale collection for the period
        $salePaid = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('paid');

        // Get extra income for the period
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->sum('amount');

        $extraIncomeByCategory = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($income) {
                return [
                    'category' => $income->category ? $income->category->name : 'Uncategorized',
                    'amount' => (float) $income->total_amount
                ];
            });

        // Get fund received for the period
        $fundReceive = Fund::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->where('type', 'in')
            ->sum('amount');

        // Calculate total receipt
        $totalReceipt = $openingCashAtBank + $salePaid + $extraIncome + $fundReceive;

        return [
            'opening_cash_on_bank' => (float) $openingCashAtBank,
            'sale_collection' => (float) $salePaid,
            'extra_income' => [
                'total' => (float) $extraIncome,
                'categories' => $extraIncomeByCategory
            ],
            'fund_receive' => (float) $fundReceive,
            'total' => (float) $totalReceipt,
        ];
    }

    protected function getPaymentData($startDate, $endDate)
    {
        // Get the bank account with current balance
        $bank = BankAccount::findOrFail($this->bankAccountId);

        // Purchase amount for the period
        $purchaseAmount = ProductStock::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'purchase')
            ->sum('total_cost');

        // Fund refunds for the period
        $fundRefund = Fund::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->where('type', 'out')
            ->sum('amount');

        // Expenses for the period
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->sum('amount');

        // Get expenses by category
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->select('expense_category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(function ($expense) {
                return [
                    'category' => $expense->category ? $expense->category->name : 'Uncategorized',
                    'amount' => (float) $expense->total_amount
                ];
            });

        // Use current bank balance directly as closing balance
        $closingCashAtBank = $bank->current_balance;

        // Calculate total payment including current bank balance
        $totalPayment = $purchaseAmount + $fundRefund + $expenses + $closingCashAtBank;

        return [
            'purchase' => (float) $purchaseAmount,
            'fund_refund' => (float) $fundRefund,
            'expenses' => [
                'total' => (float) $expenses,
                'categories' => $expensesByCategory
            ],
            'closing_cash_at_bank' => (float) $closingCashAtBank,
            'total' => (float) $totalPayment,
        ];
    }

    public function downloadPdf(Request $request)
    {
        try {
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

            // Get receipt and payment data
            $receiptData = $this->getReceiptData($startDate, $endDate);
            $paymentData = $this->getPaymentData($startDate, $endDate);

            // Prepare data for PDF
            $data = [
                'receipt' => $receiptData,
                'payment' => $paymentData,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];

            // For debugging
            \Log::info('PDF Data:', $data);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.receipt-payment', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            // Generate filename
            $filename = 'receipt-payment-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

            // Download PDF
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
