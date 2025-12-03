<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BankTransactionReportController extends Controller
{
    public function index(Request $request)
    {
        // Get the selected month and year, default to current month
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        // Get the selected bank account, default to first account
        $selectedBankAccount = $request->input('bank_account_id', BankAccount::first()?->id);

        // Get all bank accounts for the dropdown
        $bankAccounts = BankAccount::select('id', 'account_name', 'bank_name')->get();

        // Get the selected account
        $selectedAccount = BankAccount::findOrFail($selectedBankAccount);

        // Get previous month's last transaction to get the closing balance
        $previousMonthEnd = Carbon::create($selectedYear, $selectedMonth)->subDay();

        $previousMonthBalance = BankTransaction::withTrashed()
            ->where('bank_account_id', $selectedBankAccount)
            ->where('date', '<=', $previousMonthEnd)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first()?->running_balance ?? $selectedAccount->opening_balance;

        // Get all dates in the selected month
        $startOfMonth = Carbon::create($selectedYear, $selectedMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get daily transactions with categorization (WITHOUT extra income)
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
            ->where('bank_account_id', $selectedBankAccount)
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get extra incomes separately for the month
        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('SUM(amount) as total_amount'))
            ->where('bank_account_id', $selectedBankAccount)
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Log the transactions for debugging
        \Log::info('Daily Transactions:', $dailyTransactions->toArray());
        \Log::info('Extra Incomes:', $extraIncomes->toArray());

        // Create an array for all dates in the month with running balance calculation
        $allDates = [];
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

            $allDates[] = [
                'date' => $dateStr,
                'in' => [
                    'fund' => round($fundIn, 2),
                    'payment' => round($paymentReceive, 2),
                    'extra' => round($extraIncomeAmount, 2),
                    'refund' => round($refund, 2),
                    'total' => round($totalIn, 2),
                ],
                'out' => [
                    'fund' => round($fundOut, 2),
                    'purchase' => round($purchase, 2),
                    'expense' => round($expense, 2),
                    'total' => round($totalOut, 2),
                ],
                'balance' => round($runningBalance, 2),
            ];

            $currentDate->addDay();
        }

        // Calculate month totals
        $monthTotals = [
            'in' => [
                'fund' => round($dailyTransactions->sum('fund_in'), 2),
                'payment' => round($dailyTransactions->sum('payment_receive'), 2),
                'extra' => round($extraIncomes->sum('total_amount'), 2),
                'refund' => round($dailyTransactions->sum('refund'), 2),
                'total' => round($dailyTransactions->sum('fund_in') +
                    $dailyTransactions->sum('payment_receive') +
                    $extraIncomes->sum('total_amount') +
                    $dailyTransactions->sum('refund'), 2),
            ],
            'out' => [
                'fund' => round($dailyTransactions->sum('fund_out'), 2),
                'purchase' => round($dailyTransactions->sum('purchase'), 2),
                'expense' => round($dailyTransactions->sum('expense'), 2),
                'total' => round($dailyTransactions->sum('fund_out') +
                    $dailyTransactions->sum('purchase') +
                    $dailyTransactions->sum('expense'), 2),
            ],
        ];

        return Inertia::render('Admin/Reports/BankTransactionReport', [
            'bankAccounts' => $bankAccounts,
            'selectedAccount' => $selectedAccount,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'previousMonthBalance' => round(floatval($previousMonthBalance), 2),
            'dailyTransactions' => $allDates,
            'monthTotals' => $monthTotals,
            'filters' => $request->all(['month', 'year', 'bank_account_id']),
        ]);
    }

    public function downloadPdf(Request $request)
    {
        // Get the selected month and year, default to current month
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        // Get the selected bank account, default to first account
        $selectedBankAccount = $request->input('bank_account_id', BankAccount::first()?->id);

        // Get the selected account
        $selectedAccount = BankAccount::findOrFail($selectedBankAccount);

        // Get previous month's last transaction to get the closing balance
        $previousMonthEnd = Carbon::create($selectedYear, $selectedMonth, 1)->subDay();

        $previousMonthBalance = BankTransaction::withTrashed()
            ->where('bank_account_id', $selectedBankAccount)
            ->where('date', '<=', $previousMonthEnd)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first()?->running_balance ?? $selectedAccount->opening_balance;

        // Get all dates in the selected month
        $startOfMonth = Carbon::create($selectedYear, $selectedMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get daily transactions with categorization (WITHOUT extra income)
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
            ->where('bank_account_id', $selectedBankAccount)
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get extra incomes separately for the month
        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('SUM(amount) as total_amount'))
            ->where('bank_account_id', $selectedBankAccount)
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Create an array for all dates in the month with running balance calculation
        $allDates = [];
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

            $allDates[] = [
                'date' => $dateStr,
                'in' => [
                    'fund' => round($fundIn, 2),
                    'payment' => round($paymentReceive, 2),
                    'extra' => round($extraIncomeAmount, 2),
                    'refund' => round($refund, 2),
                    'total' => round($totalIn, 2),
                ],
                'out' => [
                    'fund' => round($fundOut, 2),
                    'purchase' => round($purchase, 2),
                    'expense' => round($expense, 2),
                    'total' => round($totalOut, 2),
                ],
                'balance' => round($runningBalance, 2),
            ];

            $currentDate->addDay();
        }

        // Calculate month totals
        $monthTotals = [
            'in' => [
                'fund' => round($dailyTransactions->sum('fund_in'), 2),
                'payment' => round($dailyTransactions->sum('payment_receive'), 2),
                'extra' => round($extraIncomes->sum('total_amount'), 2),
                'refund' => round($dailyTransactions->sum('refund'), 2),
                'total' => round($dailyTransactions->sum('fund_in') +
                    $dailyTransactions->sum('payment_receive') +
                    $extraIncomes->sum('total_amount') +
                    $dailyTransactions->sum('refund'), 2),
            ],
            'out' => [
                'fund' => round($dailyTransactions->sum('fund_out'), 2),
                'purchase' => round($dailyTransactions->sum('purchase'), 2),
                'expense' => round($dailyTransactions->sum('expense'), 2),
                'total' => round($dailyTransactions->sum('fund_out') +
                    $dailyTransactions->sum('purchase') +
                    $dailyTransactions->sum('expense'), 2),
            ],
        ];

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $pdf = PDF::loadView('pdf.bank-transaction-report', [
            'bankAccount' => $selectedAccount,
            'month' => $months[$selectedMonth],
            'year' => $selectedYear,
            'previousMonthBalance' => round(floatval($previousMonthBalance), 2),
            'dailyTransactions' => $allDates,
            'monthTotals' => $monthTotals,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Add metadata to the PDF
        $pdf->getDomPDF()->add_info('Title', 'Bank Transaction Report');
        $pdf->getDomPDF()->add_info('Author', 'Your Company Name');
        $pdf->getDomPDF()->add_info('Subject', 'Bank Transaction Report for '.$months[$selectedMonth].' '.$selectedYear);

        // Generate filename
        $filename = sprintf(
            'bank-transaction-report-%s-%s-%s.pdf',
            $selectedAccount->bank_name,
            $months[$selectedMonth],
            $selectedYear
        );

        // Download the PDF
        return $pdf->download($filename);
    }
}
