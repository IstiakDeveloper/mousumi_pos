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

class BankReportController extends Controller
{
    private function getTransactionEffect($type, $amount)
    {
        return match ($type) {
            'in' => $amount,
            'out' => -$amount,
            default => 0,
        };
    }

    public function getProductPurchaseTotal($productId)
    {
        // Get purchases for this product
        $purchases = BankTransaction::where('transaction_type', 'out')
            ->where('description', 'LIKE', "Stock purchase for product ID: $productId%")
            ->sum('amount');

        // Get refunds for this product
        $refunds = BankTransaction::where('transaction_type', 'in')
            ->where('description', 'LIKE', "Total refund for deleted product ID: $productId%")
            ->sum('amount');

        // Calculate net amount
        return $purchases - $refunds;
    }

    public function productTransactions(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $productId = $request->product_id;
        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        // Get transactions for the product
        $transactions = BankTransaction::with('createdBy')
            ->where(function ($query) use ($productId) {
                $query->where('description', 'LIKE', "Stock purchase for product ID: $productId%")
                    ->orWhere('description', 'LIKE', "Total refund for deleted product ID: $productId%");
            })
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // Calculate running total
        $runningTotal = 0;
        $formattedTransactions = $transactions->map(function ($transaction) use (&$runningTotal) {
            if ($transaction->transaction_type === 'out') {
                $runningTotal += $transaction->amount;
            } else {
                $runningTotal -= $transaction->amount;
            }

            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'type' => $transaction->transaction_type,
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'running_total' => $runningTotal,
                'created_by' => $transaction->createdBy->name,
            ];
        });

        $summary = [
            'total_purchases' => $transactions->where('transaction_type', 'out')->sum('amount'),
            'total_refunds' => $transactions->where('transaction_type', 'in')->sum('amount'),
            'net_amount' => $transactions->where('transaction_type', 'out')->sum('amount') -
                $transactions->where('transaction_type', 'in')->sum('amount'),
        ];

        return Inertia::render('Admin/Reports/ProductTransactions', [
            'product_id' => $productId,
            'filters' => [
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'transactions' => $formattedTransactions,
            'summary' => $summary,
        ]);
    }

    public function getTotalSalesFromInvoices($fromDate, $toDate)
    {
        return BankTransaction::where('transaction_type', 'in')
            ->where('description', 'like', 'Payment received for invoice%')
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum('amount');
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
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('CASE
                        WHEN transaction_type = "in" THEN amount
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
                        'running_balance' => $balance,
                    ];
                    $previousBalance = $balance;

                    return $result;
                });

            // Calculate monthly summaries
            $monthlySummary = $transactions->groupBy(function ($transaction) {
                return Carbon::parse($transaction['date'])->format('Y-m');
            })->map(function ($monthTransactions) {
                $inflows = $monthTransactions->where('type', 'in')->sum('amount');
                $outflows = $monthTransactions->where('type', 'out')->sum('amount');

                return [
                    'month' => Carbon::parse($monthTransactions->first()['date'])->format('F Y'),
                    'inflows' => $inflows,
                    'outflows' => $outflows,
                    'net' => $inflows - $outflows,
                    'transaction_count' => $monthTransactions->count(),
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
                'transactions' => $transactions,
            ];
        });

        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => $accounts->sum('current_balance'),
            'total_sales_from_invoices' => $this->getTotalSalesFromInvoices($fromDate, $toDate),
            'total_inflows' => BankTransaction::where('transaction_type', 'in')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
            'total_outflows' => BankTransaction::where('transaction_type', 'out')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
            'total_transactions' => BankTransaction::whereBetween('date', [$fromDate, $toDate])->whereNull('deleted_at')->count(),

            // Add these new fields
            'total_product_purchases' => BankTransaction::where('transaction_type', 'out')
                ->where('description', 'LIKE', 'Stock purchase for product ID:%')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
            'total_product_refunds' => BankTransaction::where('transaction_type', 'in')
                ->where('description', 'LIKE', 'Total refund for deleted product ID:%')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
            'net_product_amount' => BankTransaction::where('transaction_type', 'out')
                ->where('description', 'LIKE', 'Stock purchase for product ID:%')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount') -
                BankTransaction::where('transaction_type', 'in')
                    ->where('description', 'LIKE', 'Total refund for deleted product ID:%')
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->whereNull('deleted_at')
                    ->sum('amount'),
        ];

        return Inertia::render('Admin/Reports/BankReport', [
            'accounts' => BankAccount::select('id', 'account_name', 'bank_name')->get(),
            'filters' => [
                'account_id' => $request->account_id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'reports' => $reports,
            'summary' => $summary,
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

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $reports = $accounts->map(function ($account) use ($fromDate, $toDate) {
            // Get previous balance before from_date
            $previousBalance = $account->opening_balance +
                DB::table('bank_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('date', '<', $fromDate)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('CASE WHEN transaction_type = "in" THEN amount ELSE -amount END'));

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
                    $amount = $transaction->transaction_type == 'in' ? $transaction->amount : -$transaction->amount;
                    $runningBalance += $amount;

                    $monthTransactions[] = [
                        'date' => Carbon::parse($transaction->date)->format('d M, Y'),
                        'description' => $transaction->description,
                        'type' => $transaction->transaction_type,
                        'inflow' => $transaction->transaction_type == 'in' ? $transaction->amount : 0,
                        'outflow' => $transaction->transaction_type == 'out' ? $transaction->amount : 0,
                        'balance' => $runningBalance,
                    ];
                }

                // Calculate monthly summary
                $monthlyInflows = $transactions->where('transaction_type', 'in')->sum('amount');
                $monthlyOutflows = $transactions->where('transaction_type', 'out')->sum('amount');

                $monthlyData[] = [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                    'transactions' => $monthTransactions,
                    'summary' => [
                        'inflows' => $monthlyInflows,
                        'outflows' => $monthlyOutflows,
                        'net' => $monthlyInflows - $monthlyOutflows,
                        'ending_balance' => $runningBalance,
                    ],
                ];
            }

            return [
                'account' => [
                    'name' => $account->account_name,
                    'bank' => $account->bank_name,
                    'number' => $account->account_number,
                ],
                'opening_balance' => $account->opening_balance,
                'previous_balance' => $previousBalance,
                'current_balance' => $runningBalance,
                'monthly_data' => $monthlyData,
            ];
        });

        // Calculate summary
        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => $accounts->sum('current_balance'),
            'total_inflows' => BankTransaction::where('transaction_type', 'in')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
            'total_outflows' => BankTransaction::where('transaction_type', 'out')
                ->whereBetween('date', [$fromDate, $toDate])
                ->whereNull('deleted_at')
                ->sum('amount'),
        ];

        $data = [
            'reports' => $reports,
            'summary' => $summary,
            'date_range' => [
                'from' => $fromDate->format('d M, Y'),
                'to' => $toDate->format('d M, Y'),
            ],
        ];

        $pdf = PDF::loadView('reports.bank-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('bank-report-'.now()->format('Y-m-d').'.pdf');
    }
}
