<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

        // Get daily transactions with categorization including soft deleted records and refunds
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
            WHEN transaction_type = "in" AND description LIKE "Extra Income%" AND deleted_at IS NULL
            THEN amount ELSE 0 END), 0) as extra_income,
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
            ->get();

        // Log the transactions for debugging
        \Log::info('Daily Transactions:', $dailyTransactions->toArray());

        // Create an array for all dates in the month with running balance calculation
        $allDates = [];
        $currentDate = $startOfMonth->copy();
        $runningBalance = $previousMonthBalance;

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayTransaction = $dailyTransactions->firstWhere('date', $dateStr);

            if ($dayTransaction) {
                $totalIn = floatval($dayTransaction->fund_in) +
                    floatval($dayTransaction->payment_receive) +
                    floatval($dayTransaction->extra_income) +
                    floatval($dayTransaction->refund);

                $totalOut = floatval($dayTransaction->fund_out) +
                    floatval($dayTransaction->purchase) +
                    floatval($dayTransaction->expense);

                // Calculate new running balance
                $runningBalance = $runningBalance + $totalIn - $totalOut;

                $allDates[] = [
                    'date' => $dateStr,
                    'in' => [
                        'fund' => round(floatval($dayTransaction->fund_in), 2),
                        'payment' => round(floatval($dayTransaction->payment_receive), 2),
                        'extra' => round(floatval($dayTransaction->extra_income), 2),
                        'refund' => round(floatval($dayTransaction->refund), 2),
                        'total' => round($totalIn, 2)
                    ],
                    'out' => [
                        'fund' => round(floatval($dayTransaction->fund_out), 2),
                        'purchase' => round(floatval($dayTransaction->purchase), 2),
                        'expense' => round(floatval($dayTransaction->expense), 2),
                        'total' => round($totalOut, 2)
                    ],
                    'balance' => round($runningBalance, 2)
                ];
            } else {
                $allDates[] = [
                    'date' => $dateStr,
                    'in' => [
                        'fund' => 0,
                        'payment' => 0,
                        'extra' => 0,
                        'refund' => 0,
                        'total' => 0
                    ],
                    'out' => [
                        'fund' => 0,
                        'purchase' => 0,
                        'expense' => 0,
                        'total' => 0
                    ],
                    'balance' => round($runningBalance, 2)
                ];
            }

            $currentDate->addDay();
        }

        // Calculate month totals
        $monthTotals = [
            'in' => [
                'fund' => round($dailyTransactions->sum('fund_in'), 2),
                'payment' => round($dailyTransactions->sum('payment_receive'), 2),
                'extra' => round($dailyTransactions->sum('extra_income'), 2),
                'refund' => round($dailyTransactions->sum('refund'), 2),
                'total' => round($dailyTransactions->sum('fund_in') +
                    $dailyTransactions->sum('payment_receive') +
                    $dailyTransactions->sum('extra_income') +
                    $dailyTransactions->sum('refund'), 2)
            ],
            'out' => [
                'fund' => round($dailyTransactions->sum('fund_out'), 2),
                'purchase' => round($dailyTransactions->sum('purchase'), 2),
                'expense' => round($dailyTransactions->sum('expense'), 2),
                'total' => round($dailyTransactions->sum('fund_out') +
                    $dailyTransactions->sum('purchase') +
                    $dailyTransactions->sum('expense'), 2)
            ]
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

        // Get daily transactions with categorization including soft deleted records and refunds
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
                    WHEN transaction_type = "in" AND description LIKE "Extra Income%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as extra_income,
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
            ->get();

        // Create an array for all dates in the month with running balance calculation
        $allDates = [];
        $currentDate = $startOfMonth->copy();
        $runningBalance = $previousMonthBalance;

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayTransaction = $dailyTransactions->firstWhere('date', $dateStr);

            if ($dayTransaction) {
                $totalIn = floatval($dayTransaction->fund_in) +
                    floatval($dayTransaction->payment_receive) +
                    floatval($dayTransaction->extra_income) +
                    floatval($dayTransaction->refund);

                $totalOut = floatval($dayTransaction->fund_out) +
                    floatval($dayTransaction->purchase) +
                    floatval($dayTransaction->expense);

                // Calculate new running balance
                $runningBalance = $runningBalance + $totalIn - $totalOut;

                $allDates[] = [
                    'date' => $dateStr,
                    'in' => [
                        'fund' => round(floatval($dayTransaction->fund_in), 2),
                        'payment' => round(floatval($dayTransaction->payment_receive), 2),
                        'extra' => round(floatval($dayTransaction->extra_income), 2),
                        'refund' => round(floatval($dayTransaction->refund), 2),
                        'total' => round($totalIn, 2)
                    ],
                    'out' => [
                        'fund' => round(floatval($dayTransaction->fund_out), 2),
                        'purchase' => round(floatval($dayTransaction->purchase), 2),
                        'expense' => round(floatval($dayTransaction->expense), 2),
                        'total' => round($totalOut, 2)
                    ],
                    'balance' => round($runningBalance, 2)
                ];
            } else {
                $allDates[] = [
                    'date' => $dateStr,
                    'in' => [
                        'fund' => 0,
                        'payment' => 0,
                        'extra' => 0,
                        'refund' => 0,
                        'total' => 0
                    ],
                    'out' => [
                        'fund' => 0,
                        'purchase' => 0,
                        'expense' => 0,
                        'total' => 0
                    ],
                    'balance' => round($runningBalance, 2)
                ];
            }

            $currentDate->addDay();
        }

        // Calculate month totals
        $monthTotals = [
            'in' => [
                'fund' => round($dailyTransactions->sum('fund_in'), 2),
                'payment' => round($dailyTransactions->sum('payment_receive'), 2),
                'extra' => round($dailyTransactions->sum('extra_income'), 2),
                'refund' => round($dailyTransactions->sum('refund'), 2),
                'total' => round($dailyTransactions->sum('fund_in') +
                    $dailyTransactions->sum('payment_receive') +
                    $dailyTransactions->sum('extra_income') +
                    $dailyTransactions->sum('refund'), 2)
            ],
            'out' => [
                'fund' => round($dailyTransactions->sum('fund_out'), 2),
                'purchase' => round($dailyTransactions->sum('purchase'), 2),
                'expense' => round($dailyTransactions->sum('expense'), 2),
                'total' => round($dailyTransactions->sum('fund_out') +
                    $dailyTransactions->sum('purchase') +
                    $dailyTransactions->sum('expense'), 2)
            ]
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
            12 => 'December'
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
        $pdf->getDomPDF()->add_info('Subject', 'Bank Transaction Report for ' . $months[$selectedMonth] . ' ' . $selectedYear);

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
