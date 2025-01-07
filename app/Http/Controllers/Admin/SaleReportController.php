<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_status' => 'nullable|in:paid,partial,due',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        // Get all bank accounts
        $bankAccounts = BankAccount::when($request->bank_account_id, function($query) use ($request) {
            return $query->where('id', $request->bank_account_id);
        })->get();

        // Base query for sales
        $query = Sale::with(['customer', 'saleItems.product', 'salePayments.bankAccount', 'createdBy'])
            ->when($request->customer_id, function ($q) use ($request) {
                return $q->where('customer_id', $request->customer_id);
            })
            ->when($request->payment_status, function ($q) use ($request) {
                return $q->where('payment_status', $request->payment_status);
            })
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);

        // Get sales grouped by month
        $salesByMonth = $query->get()->groupBy(function($sale) {
            return Carbon::parse($sale->created_at)->format('Y-m');
        });

        $monthlyReports = collect();

        foreach ($salesByMonth as $yearMonth => $sales) {
            $payments = SalePayment::with('bankAccount')
                ->whereIn('sale_id', $sales->pluck('id'))
                ->when($request->bank_account_id, function ($q) use ($request) {
                    return $q->where('bank_account_id', $request->bank_account_id);
                })
                ->get();

            $monthlyData = $sales->groupBy(function($sale) {
                return Carbon::parse($sale->created_at)->format('Y-m-d');
            })->map(function($daySales) {
                $dayPayments = SalePayment::with('bankAccount')
                    ->whereIn('sale_id', $daySales->pluck('id'))
                    ->get();

                return [
                    'date' => Carbon::parse($daySales->first()->created_at)->format('d M, Y'),
                    'sales' => $daySales->map(function($sale) {
                        return [
                            'id' => $sale->id,
                            'created_at' => $sale->created_at->format('h:i A'),
                            'invoice_no' => $sale->invoice_no,
                            'customer' => optional($sale->customer)->name ?? 'Walk-in Customer',
                            'total' => $sale->total,
                            'paid' => $sale->paid,
                            'due' => $sale->due,
                            'payment_status' => $sale->payment_status,
                            'payments' => $sale->salePayments->map(function($payment) {
                                return [
                                    'amount' => $payment->amount,
                                    'method' => $payment->payment_method,
                                    'bank_name' => optional($payment->bankAccount)->bank_name,
                                    'account_number' => optional($payment->bankAccount)->account_number,
                                    'transaction_id' => $payment->transaction_id
                                ];
                            })
                        ];
                    }),
                    'summary' => [
                        'total_sales' => $daySales->count(),
                        'total_amount' => $daySales->sum('total'),
                        'received' => $dayPayments->sum('amount'),
                        'due' => $daySales->sum('due'),
                        'payment_methods' => $dayPayments->groupBy('payment_method')
                            ->map(function($methodPayments) {
                                return [
                                    'amount' => $methodPayments->sum('amount'),
                                    'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                        ->groupBy('bank_account_id')
                                        ->map(function($bankPayments) {
                                            $firstPayment = $bankPayments->first();
                                            return [
                                                'bank_name' => $firstPayment->bankAccount->bank_name,
                                                'account_number' => $firstPayment->bankAccount->account_number,
                                                'amount' => $bankPayments->sum('amount')
                                            ];
                                        })
                                ];
                            })
                    ]
                ];
            });

            $monthlyReports->push([
                'month' => Carbon::createFromFormat('Y-m', $yearMonth)->format('F Y'),
                'daily_data' => $monthlyData,
                'summary' => [
                    'total_sales' => $sales->count(),
                    'total_amount' => $sales->sum('total'),
                    'received' => $payments->sum('amount'),
                    'due' => $sales->sum('due'),
                    'payment_methods' => $payments->groupBy('payment_method')
                        ->map(function($methodPayments) {
                            return [
                                'amount' => $methodPayments->sum('amount'),
                                'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                    ->groupBy('bank_account_id')
                                    ->map(function($bankPayments) {
                                        $firstPayment = $bankPayments->first();
                                        return [
                                            'bank_name' => $firstPayment->bankAccount->bank_name,
                                            'account_number' => $firstPayment->bankAccount->account_number,
                                            'amount' => $bankPayments->sum('amount')
                                        ];
                                    })
                            ];
                        })
                ]
            ]);
        }

        return Inertia::render('Admin/Reports/SalesReport', [
            'customers' => Customer::select('id', 'name')->get(),
            'bank_accounts' => BankAccount::select('id', 'bank_name', 'account_number')->get(),
            'filters' => [
                'customer_id' => $request->customer_id,
                'payment_status' => $request->payment_status,
                'bank_account_id' => $request->bank_account_id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'reports' => $monthlyReports,
            'summary' => [
                'total_sales' => $query->count(),
                'total_amount' => $query->sum('total'),
                'received' => SalePayment::whereIn('sale_id', $query->pluck('id'))->sum('amount'),
                'due' => $query->sum('due'),
            ]
        ]);
}

    public function downloadPdf(Request $request)
    {
        // Similar data processing as index method
        $data = $this->processReportData($request);

        $pdf = PDF::loadView('reports.sales-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function processReportData(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_status' => 'nullable|in:paid,partial,due',
            'payment_method' => 'nullable|in:cash,card,bank,mobile_banking',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        // Get bank accounts for transactions
        $bankAccounts = BankAccount::when($request->bank_account_id, function ($query) use ($request) {
            return $query->where('id', $request->bank_account_id);
        })->get();

        // Build the base query
        $query = Sale::with([
            'customer',
            'saleItems.product',
            'salePayments.bankAccount', // Include bank account information
            'createdBy'
        ])->when($request->customer_id, function ($q) use ($request) {
            return $q->where('customer_id', $request->customer_id);
        })->when($request->payment_status, function ($q) use ($request) {
            return $q->where('payment_status', $request->payment_status);
        })->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);

        // Group sales by month
        $salesByMonth = $query->get()->groupBy(function($sale) {
            return Carbon::parse($sale->created_at)->format('Y-m');
        });

        // Get bank transactions
        $bankTransactions = BankTransaction::with('bankAccount')
            ->whereIn('bank_account_id', $bankAccounts->pluck('id'))
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        $monthlyReports = collect();

        foreach ($salesByMonth as $yearMonth => $sales) {
            $payments = SalePayment::with('bankAccount')
                ->whereIn('sale_id', $sales->pluck('id'))
                ->when($request->payment_method, function ($q) use ($request) {
                    return $q->where('payment_method', $request->payment_method);
                })
                ->when($request->bank_account_id, function ($q) use ($request) {
                    return $q->where('bank_account_id', $request->bank_account_id);
                })
                ->get();

            $dailySales = $sales->groupBy(function($sale) {
                return Carbon::parse($sale->created_at)->format('Y-m-d');
            })->map(function($daySales) {
                $dayPayments = SalePayment::with('bankAccount')
                    ->whereIn('sale_id', $daySales->pluck('id'))
                    ->get();

                return [
                    'date' => Carbon::parse($daySales->first()->created_at)->format('d M, Y'),
                    'sales' => $daySales->map(function($sale) {
                        return [
                            'created_at' => $sale->created_at->format('h:i A'),
                            'invoice_no' => $sale->invoice_no,
                            'customer' => optional($sale->customer)->name ?? 'Walk-in Customer',
                            'items' => $sale->saleItems->map(function($item) {
                                return [
                                    'product' => $item->product->name,
                                    'quantity' => $item->quantity,
                                    'unit_price' => $item->unit_price,
                                    'subtotal' => $item->subtotal
                                ];
                            }),
                            'total' => $sale->total,
                            'paid' => $sale->paid,
                            'due' => $sale->due,
                            'payment_status' => $sale->payment_status,
                            'payments' => $sale->salePayments->map(function($payment) {
                                return [
                                    'amount' => $payment->amount,
                                    'method' => $payment->payment_method,
                                    'bank_account' => $payment->bank_account_id ? [
                                        'name' => $payment->bankAccount->bank_name,
                                        'account' => $payment->bankAccount->account_number
                                    ] : null,
                                    'transaction_id' => $payment->transaction_id
                                ];
                            }),
                            'created_by' => $sale->createdBy->name
                        ];
                    })->values(),
                    'summary' => [
                        'total_sales' => $daySales->count(),
                        'total_items' => $daySales->sum(function($sale) {
                            return $sale->saleItems->sum('quantity');
                        }),
                        'gross_total' => $daySales->sum('subtotal'),
                        'discount' => $daySales->sum('discount'),
                        'tax' => $daySales->sum('tax'),
                        'net_total' => $daySales->sum('total'),
                        'received' => $dayPayments->sum('amount'),
                        'due' => $daySales->sum('due')
                    ],
                    'payments_by_method' => $dayPayments->groupBy('payment_method')
                        ->map(function($methodPayments) {
                            return [
                                'amount' => $methodPayments->sum('amount'),
                                'transactions' => $methodPayments->where('bank_account_id', '!=', null)
                                    ->map(function($payment) {
                                        return [
                                            'amount' => $payment->amount,
                                            'bank' => $payment->bankAccount->bank_name,
                                            'account' => $payment->bankAccount->account_number,
                                            'transaction_id' => $payment->transaction_id
                                        ];
                                    })
                            ];
                        })
                ];
            })->values();

            // Get bank transactions for this month
            $monthBankTransactions = $bankTransactions->filter(function($transaction) use ($yearMonth) {
                return Carbon::parse($transaction->date)->format('Y-m') === $yearMonth;
            });

            $monthlyReports->push([
                'month' => Carbon::createFromFormat('Y-m', $yearMonth)->format('F Y'),
                'daily_sales' => $dailySales,
                'summary' => [
                    'total_sales' => $sales->count(),
                    'total_items' => $sales->sum(function($sale) {
                        return $sale->saleItems->sum('quantity');
                    }),
                    'gross_total' => $sales->sum('subtotal'),
                    'discount' => $sales->sum('discount'),
                    'tax' => $sales->sum('tax'),
                    'net_total' => $sales->sum('total'),
                    'received' => $payments->sum('amount'),
                    'due' => $sales->sum('due'),
                    'payment_methods' => $payments->groupBy('payment_method')
                        ->map(function($methodPayments) {
                            return [
                                'amount' => $methodPayments->sum('amount'),
                                'bank_transactions' => $methodPayments->where('bank_account_id', '!=', null)
                                    ->groupBy('bank_account_id')
                                    ->map(function($bankPayments) {
                                        $firstPayment = $bankPayments->first();
                                        return [
                                            'bank_name' => $firstPayment->bankAccount->bank_name,
                                            'account_number' => $firstPayment->bankAccount->account_number,
                                            'total' => $bankPayments->sum('amount'),
                                            'count' => $bankPayments->count()
                                        ];
                                    })
                            ];
                        }),
                    'bank_transactions' => $monthBankTransactions->groupBy('bank_account_id')
                        ->map(function($transactions) {
                            $account = $transactions->first()->bankAccount;
                            return [
                                'bank_name' => $account->bank_name,
                                'account_number' => $account->account_number,
                                'deposits' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
                                'withdrawals' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
                                'transactions' => $transactions->map(function($transaction) {
                                    return [
                                        'date' => Carbon::parse($transaction->date)->format('d M, Y'),
                                        'type' => $transaction->transaction_type,
                                        'amount' => $transaction->amount,
                                        'description' => $transaction->description
                                    ];
                                })
                            ];
                        })
                ]
            ]);
        }

        // Add bank accounts summary
        $bankAccountsSummary = $bankAccounts->map(function($account) use ($bankTransactions) {
            $accountTransactions = $bankTransactions->where('bank_account_id', $account->id);
            return [
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'opening_balance' => $account->opening_balance,
                'current_balance' => $account->current_balance,
                'total_deposits' => $accountTransactions->where('transaction_type', 'deposit')->sum('amount'),
                'total_withdrawals' => $accountTransactions->where('transaction_type', 'withdrawal')->sum('amount')
            ];
        });

        return [
            'monthly_reports' => $monthlyReports,
            'bank_accounts' => $bankAccountsSummary,
            'summary' => [
                'total_sales' => $query->count(),
                'gross_total' => $query->sum('subtotal'),
                'discount' => $query->sum('discount'),
                'tax' => $query->sum('tax'),
                'net_total' => $query->sum('total'),
                'received' => SalePayment::whereIn('sale_id', $query->pluck('id'))->sum('amount'),
                'due' => $query->sum('due')
            ],
            'filters' => [
                'from_date' => $fromDate->format('d M, Y'),
                'to_date' => $toDate->format('d M, Y')
            ]
        ];
    }
}
