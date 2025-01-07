<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankReportController extends Controller
{
    private function getTransactionEffect($type, $amount)
    {
        return match ($type) {
            'deposit', 'loan_taken' => $amount,
            'withdrawal', 'loan_payment' => -$amount,
            default => 0,
        };
    }
    public function index(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:bank_accounts,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        // Get date range
        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        // Base query for accounts
        $query = BankAccount::query();
        if ($request->account_id) {
            $query->where('id', $request->account_id);
        }
        $accounts = $query->get();

        $reports = $accounts->map(function ($account) use ($fromDate, $toDate) {
            // Get previous balance (before from_date)
            $previousBalance = $account->opening_balance +
                DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->where('date', '<', $fromDate)
                ->sum(DB::raw('CASE
                        WHEN transaction_type IN ("deposit", "loan_taken") THEN amount
                        ELSE -amount
                    END'));

            // Get all transactions for the period
            $transactions = BankTransaction::with('createdBy')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->map(function ($transaction) use (&$previousBalance) {
                    $effect = $this->getTransactionEffect($transaction->transaction_type, $transaction->amount);
                    $balance = $previousBalance + $effect;

                    $result = [
                        'id' => $transaction->id,
                        'date' => $transaction->date,
                        'type' => $transaction->transaction_type,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'created_by' => $transaction->createdBy->name,
                        'running_balance' => $balance
                    ];
                    $previousBalance = $balance;
                    return $result;
                });

            // Calculate monthly summaries
            $monthlySummary = $transactions->groupBy(function ($transaction) {
                return Carbon::parse($transaction['date'])->format('Y-m');
            })->map(function ($monthTransactions) {
                $deposits = $monthTransactions->whereIn('type', ['deposit', 'loan_taken'])->sum('amount');
                $withdrawals = $monthTransactions->whereIn('type', ['withdrawal', 'loan_payment'])->sum('amount');

                return [
                    'month' => Carbon::parse($monthTransactions->first()['date'])->format('F Y'),
                    'deposits' => $deposits,
                    'withdrawals' => $withdrawals,
                    'net' => $deposits - $withdrawals,
                    'transaction_count' => $monthTransactions->count()
                ];
            })->values();

            return [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'bank' => $account->bank_name,
                    'number' => $account->account_number,
                ],
                'opening_balance' => $account->opening_balance,
                'previous_balance' => $previousBalance,
                'current_balance' => $account->current_balance,
                'monthly_summary' => $monthlySummary,
                'transactions' => $transactions
            ];
        });

        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => $accounts->sum('current_balance'),
            'total_deposits' => BankTransaction::whereIn('transaction_type', ['deposit', 'loan_taken'])
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('amount'),
            'total_withdrawals' => BankTransaction::whereIn('transaction_type', ['withdrawal', 'loan_payment'])
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('amount'),
            'total_transactions' => BankTransaction::whereBetween('date', [$fromDate, $toDate])->count()
        ];

        return Inertia::render('Admin/Reports/BankReport', [
            'accounts' => BankAccount::select('id', 'account_name', 'bank_name')->get(),
            'filters' => [
                'account_id' => $request->account_id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'reports' => $reports,
            'summary' => $summary
        ]);
    }

    # BankReportController.php
    public function downloadPdf(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:bank_accounts,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = BankAccount::query();
        if ($request->account_id) {
            $query->where('id', $request->account_id);
        }
        $accounts = $query->get();

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $reports = $accounts->map(function ($account) use ($fromDate, $toDate) {
            // Get previous balance before from_date
            $previousBalance = $account->opening_balance +
                DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->where('date', '<', $fromDate)
                ->sum(DB::raw('CASE WHEN transaction_type = "deposit" THEN amount ELSE -amount END'));

            // Get all transactions with details
            $allTransactions = BankTransaction::where('bank_account_id', $account->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->get();

            // Group transactions by month
            $monthlyData = [];
            $runningBalance = $previousBalance;

            foreach (
                $allTransactions->groupBy(function ($transaction) {
                    return Carbon::parse($transaction->date)->format('Y-m');
                }) as $month => $transactions
            ) {
                $monthTransactions = [];
                foreach ($transactions as $transaction) {
                    // Update running balance
                    $amount = $transaction->transaction_type == 'deposit' ? $transaction->amount : -$transaction->amount;
                    $runningBalance += $amount;

                    $monthTransactions[] = [
                        'date' => Carbon::parse($transaction->date)->format('d M, Y'),
                        'description' => $transaction->description,
                        'type' => $transaction->transaction_type,
                        'deposit' => $transaction->transaction_type == 'deposit' ? $transaction->amount : 0,
                        'withdrawal' => $transaction->transaction_type == 'withdrawal' ? $transaction->amount : 0,
                        'balance' => $runningBalance
                    ];
                }

                // Calculate monthly summary
                $monthlyDeposits = $transactions->where('transaction_type', 'deposit')->sum('amount');
                $monthlyWithdrawals = $transactions->where('transaction_type', 'withdrawal')->sum('amount');

                $monthlyData[] = [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                    'transactions' => $monthTransactions,
                    'summary' => [
                        'deposits' => $monthlyDeposits,
                        'withdrawals' => $monthlyWithdrawals,
                        'net' => $monthlyDeposits - $monthlyWithdrawals,
                        'ending_balance' => $runningBalance
                    ]
                ];
            }

            return [
                'account' => [
                    'name' => $account->account_name,
                    'bank' => $account->bank_name,
                    'number' => $account->account_number
                ],
                'opening_balance' => $account->opening_balance,
                'previous_balance' => $previousBalance,
                'current_balance' => $runningBalance,
                'monthly_data' => $monthlyData
            ];
        });

        // Calculate summary
        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => $accounts->sum('current_balance'),
            'total_deposits' => BankTransaction::where('transaction_type', 'deposit')
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('amount'),
            'total_withdrawals' => BankTransaction::where('transaction_type', 'withdrawal')
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('amount'),
        ];

        $data = [
            'reports' => $reports,
            'summary' => $summary,
            'date_range' => [
                'from' => $fromDate->format('d M, Y'),
                'to' => $toDate->format('d M, Y')
            ]
        ];

        $pdf = PDF::loadView('reports.bank-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('bank-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
