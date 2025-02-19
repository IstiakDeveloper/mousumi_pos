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
            // Add detailed logging at the start
            \Log::info('PDF Download Request:', [
                'year' => $request->year,
                'month' => $request->month
            ]);

            // Validate input
            $validatedData = $request->validate([
                'year' => 'required|integer|min:2000|max:2099',
                'month' => 'required|integer|between:1,12',
            ]);

            // Create date range
            $startDate = Carbon::create($validatedData['year'], $validatedData['month'], 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            \Log::info('Date Range:', [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);

            // Get data
            $receiptData = $this->getReceiptData($startDate, $endDate);
            $paymentData = $this->getPaymentData($startDate, $endDate);

            \Log::info('Data Retrieved:', [
                'receiptCount' => count($receiptData),
                'paymentCount' => count($paymentData)
            ]);

            // Generate PDF with error handling
            try {
                $pdf = PDF::loadView('pdf.receipt-payment', [
                    'receipt' => $receiptData,
                    'payment' => $paymentData,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                \Log::error('PDF Generation Failed:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('PDF generation failed: ' . $e->getMessage());
            }

            return $pdf->download("receipt-payment-{$validatedData['year']}-{$validatedData['month']}.pdf");

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed:', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('PDF Download Failed:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to generate PDF',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
