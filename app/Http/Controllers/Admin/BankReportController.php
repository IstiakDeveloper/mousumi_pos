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
                    ->sum(DB::raw('CASE WHEN transaction_type = "deposit" THEN amount ELSE -amount END'));

            // Get all transactions for the period
            $transactions = BankTransaction::with('createdBy')
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->map(function ($transaction) use (&$previousBalance) {
                    $balance = $previousBalance + ($transaction->transaction_type === 'deposit' ? $transaction->amount : -$transaction->amount);
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
                return [
                    'month' => Carbon::parse($monthTransactions->first()['date'])->format('F Y'),
                    'deposits' => $monthTransactions->where('type', 'deposit')->sum('amount'),
                    'withdrawals' => $monthTransactions->where('type', 'withdrawal')->sum('amount'),
                    'net' => $monthTransactions->where('type', 'deposit')->sum('amount') -
                            $monthTransactions->where('type', 'withdrawal')->sum('amount'),
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
            'total_deposits' => BankTransaction::where('transaction_type', 'deposit')
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('amount'),
            'total_withdrawals' => BankTransaction::where('transaction_type', 'withdrawal')
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

        // Get date range
        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        // Get monthly transactions for each account
        $reports = $accounts->map(function ($account) use ($fromDate, $toDate) {
            // Previous balance calculation
            $previousBalance = $account->opening_balance +
                DB::table('bank_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('date', '<', $fromDate)
                    ->sum(DB::raw('CASE WHEN transaction_type = "deposit" THEN amount ELSE -amount END'));

            // Monthly transactions
            $transactions = DB::table('bank_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                    DB::raw('SUM(CASE WHEN transaction_type = "deposit" THEN amount ELSE 0 END) as deposits'),
                    DB::raw('SUM(CASE WHEN transaction_type = "withdrawal" THEN amount ELSE 0 END) as withdrawals')
                )
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Calculate running balance
            $runningBalance = $previousBalance;
            $monthlyData = $transactions->map(function ($transaction) use (&$runningBalance) {
                $runningBalance += ($transaction->deposits - $transaction->withdrawals);
                return [
                    'month' => Carbon::createFromFormat('Y-m', $transaction->month)->format('F Y'),
                    'deposits' => $transaction->deposits,
                    'withdrawals' => $transaction->withdrawals,
                    'net' => $transaction->deposits - $transaction->withdrawals,
                    'balance' => $runningBalance
                ];
            });

            return [
                'account' => [
                    'name' => $account->account_name,
                    'bank' => $account->bank_name,
                    'number' => $account->account_number
                ],
                'opening_balance' => $account->opening_balance,
                'previous_balance' => $previousBalance,
                'current_balance' => $account->current_balance,
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

        $pdf = Pdf::loadView('reports.bank-report-pdf', $data);
        $pdf->setPaper('a4');

        return $pdf->download('bank-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
