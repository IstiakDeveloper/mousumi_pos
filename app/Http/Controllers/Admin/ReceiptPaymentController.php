<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\Fund;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            'end_date' => $endDate,
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

    public function getTotalSalesFromInvoices($fromDate, $toDate)
    {
        return BankTransaction::where('transaction_type', 'in')
            ->where('description', 'like', 'Payment received for invoice%')
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    protected function getReceiptData($startDate, $endDate)
    {
        // Get the bank account
        $bank = BankAccount::findOrFail($this->bankAccountId);

        // Get the latest bank transaction from previous month (same as BankTransactionReport)
        $prevMonthEndBalance = BankTransaction::where('bank_account_id', $this->bankAccountId)
            ->where('date', '<', $startDate)
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // If we found a previous transaction, use its running balance
        $openingCashAtBank = $prevMonthEndBalance ? $prevMonthEndBalance->running_balance : $bank->opening_balance;

        // Get sale collection for the period from bank transactions
        $salePaid = $this->getTotalSalesFromInvoices($startDate, $endDate);

        // Get extra income from separate table
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->whereNull('deleted_at')
            ->sum('amount');

        $extraIncomeByCategory = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->whereNull('deleted_at')
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($income) {
                return [
                    'category' => $income->category ? $income->category->name : 'Uncategorized',
                    'amount' => (float) $income->total_amount,
                ];
            });

        // Get fund received from bank transactions (same as BankTransactionReport)
        $fundReceive = BankTransaction::where('transaction_type', 'in')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('description', 'like', 'Fund in%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        // Calculate total receipt
        $totalReceipt = $openingCashAtBank + $salePaid + $extraIncome + $fundReceive;

        return [
            'opening_cash_on_bank' => (float) $openingCashAtBank,
            'sale_collection' => (float) $salePaid,
            'extra_income' => [
                'total' => (float) $extraIncome,
                'categories' => $extraIncomeByCategory,
            ],
            'fund_receive' => (float) $fundReceive,
            'total' => (float) $totalReceipt,
        ];
    }

    protected function getPaymentData($startDate, $endDate)
    {
        // Get the bank account
        $bank = BankAccount::findOrFail($this->bankAccountId);

        // Get purchase amount from bank transactions (same as BankTransactionReport)
        $purchaseAmount = BankTransaction::where('transaction_type', 'out')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('description', 'LIKE', 'Stock purchase%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        // Get purchase refunds from bank transactions (same as BankTransactionReport)
        $purchaseRefunds = BankTransaction::where('transaction_type', 'in')
            ->where('bank_account_id', $this->bankAccountId)
            ->where(function ($query) {
                $query->where('description', 'LIKE', '%Refund%')
                    ->orWhere('description', 'LIKE', '%cancelled%')
                    ->orWhere('description', 'LIKE', '%deleted%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        // Calculate net purchase amount
        $netPurchaseAmount = $purchaseAmount - $purchaseRefunds;

        // Get fund out from bank transactions (same as BankTransactionReport)
        $fundRefund = BankTransaction::where('transaction_type', 'out')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('description', 'like', 'Fund out%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        // Get expenses from separate table
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->whereNull('deleted_at')
            ->sum('amount');

        // Get expenses by category
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->whereNull('deleted_at')
            ->select('expense_category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(function ($expense) {
                return [
                    'category' => $expense->category ? $expense->category->name : 'Uncategorized',
                    'amount' => (float) $expense->total_amount,
                ];
            });

        // Calculate closing balance same way as BankTransactionReport
        $closingCashAtBank = $this->calculateClosingBalance($startDate, $endDate);

        // Calculate total payment including closing cash at bank
        $totalPayment = $netPurchaseAmount + $fundRefund + $expenses + $closingCashAtBank;

        return [
            'purchase' => (float) $netPurchaseAmount,
            'fund_refund' => (float) $fundRefund,
            'expenses' => [
                'total' => (float) $expenses,
                'categories' => $expensesByCategory,
            ],
            'closing_cash_at_bank' => (float) $closingCashAtBank,
            'total' => (float) $totalPayment,
        ];
    }

    protected function calculateClosingBalance($startDate, $endDate)
    {
        $bankAccountId = $this->bankAccountId;

        // Get bank account
        $bankAccount = BankAccount::findOrFail($bankAccountId);

        // Get previous month's last transaction (same as BankTransactionReport)
        $previousMonthEnd = Carbon::createFromFormat('Y-m-d', $startDate)->subDay();

        $previousMonthBalance = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('date', '<=', $previousMonthEnd)
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first()?->running_balance ?? $bankAccount->opening_balance;

        // Get daily transactions for the month (same as BankTransactionReport)
        $dailyTransactions = DB::table('bank_transactions')
            ->select('date')
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND description LIKE "Fund in%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_in,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND description LIKE "Payment received%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as payment_receive,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND deleted_at IS NULL AND (
                        description LIKE "%Refund%" OR
                        description LIKE "%Stock purchase%" OR
                        description LIKE "%cancelled%" OR
                        description LIKE "%deleted%"
                    )
                    THEN amount ELSE 0 END), 0) as refund,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Fund out%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_out,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Stock purchase%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as purchase,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Expense%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as expense')
            ->where('bank_account_id', $bankAccountId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get extra incomes separately (same as BankTransactionReport)
        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('SUM(amount) as total_amount'))
            ->where('bank_account_id', $bankAccountId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Calculate running balance for each day (same logic as BankTransactionReport)
        $startOfMonth = Carbon::createFromFormat('Y-m-d', $startDate);
        $endOfMonth = Carbon::createFromFormat('Y-m-d', $endDate);
        $currentDate = $startOfMonth->copy();
        $runningBalance = $previousMonthBalance;

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayTransaction = $dailyTransactions->get($dateStr);
            $extraIncome = $extraIncomes->get($dateStr);

            // Get values from bank transactions
            $fundIn = $dayTransaction ? floatval($dayTransaction->fund_in) : 0;
            $paymentReceive = $dayTransaction ? floatval($dayTransaction->payment_receive) : 0;
            $refund = $dayTransaction ? floatval($dayTransaction->refund) : 0;
            $fundOut = $dayTransaction ? floatval($dayTransaction->fund_out) : 0;
            $purchase = $dayTransaction ? floatval($dayTransaction->purchase) : 0;
            $expense = $dayTransaction ? floatval($dayTransaction->expense) : 0;

            // Get extra income amount
            $extraIncomeAmount = $extraIncome ? floatval($extraIncome->total_amount) : 0;

            $totalIn = $fundIn + $paymentReceive + $extraIncomeAmount + $refund;
            $totalOut = $fundOut + $purchase + $expense;

            // Calculate new running balance
            $runningBalance = $runningBalance + $totalIn - $totalOut;

            $currentDate->addDay();
        }

        return round($runningBalance, 2);
    }

    public function downloadPdf(Request $request)
    {
        try {
            // Add detailed logging at the start
            \Log::info('PDF Download Request:', [
                'year' => $request->year,
                'month' => $request->month,
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
                'end' => $endDate->toDateString(),
            ]);

            // Get data
            $receiptData = $this->getReceiptData($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            $paymentData = $this->getPaymentData($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

            \Log::info('Data Retrieved:', [
                'receiptTotal' => $receiptData['total'],
                'paymentTotal' => $paymentData['total'],
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
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('PDF generation failed: '.$e->getMessage());
            }

            return $pdf->download("receipt-payment-{$validatedData['year']}-{$validatedData['month']}.pdf");

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed:', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('PDF Download Failed:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to generate PDF',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
