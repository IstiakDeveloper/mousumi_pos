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
        $salesByMonth = $query->get()->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('Y-m');
        });

        $monthlyReports = collect();

        foreach ($salesByMonth as $yearMonth => $sales) {
            $monthlyData = $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->created_at)->format('Y-m-d');
            })->map(function ($daySales) {
                return [
                    'date' => Carbon::parse($daySales->first()->created_at)->format('d M, Y'),
                    'sales' => $daySales->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'created_at' => $sale->created_at->format('h:i A'),
                            'invoice_no' => $sale->invoice_no,
                            'customer' => optional($sale->customer)->name ?? 'Walk-in Customer',
                            'total' => $sale->total,
                            'paid' => $sale->paid,
                            'due' => $sale->due,
                            'payment_status' => $sale->payment_status,
                            'payments' => $sale->salePayments->map(function ($payment) {
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
                        'received' => $daySales->sum('paid'),
                        'due' => $daySales->sum('due'),
                        'payment_methods' => $daySales->flatMap->salePayments
                            ->groupBy('payment_method')
                            ->map(function ($methodPayments) {
                                return [
                                    'amount' => $methodPayments->sum('amount'),
                                    'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                        ->groupBy('bank_account_id')
                                        ->map(function ($bankPayments) {
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
                    'received' => $sales->sum('paid'),
                    'due' => $sales->sum('due'),
                    'payment_methods' => $sales->flatMap->salePayments
                        ->groupBy('payment_method')
                        ->map(function ($methodPayments) {
                            return [
                                'amount' => $methodPayments->sum('amount'),
                                'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                    ->groupBy('bank_account_id')
                                    ->map(function ($bankPayments) {
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
                'received' => $query->sum('paid'),
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

        // Build the base query
        $query = Sale::with([
            'customer',
            'saleItems.product',
            'salePayments.bankAccount',
            'createdBy'
        ])->when($request->customer_id, function ($q) use ($request) {
            return $q->where('customer_id', $request->customer_id);
        })->when($request->payment_status, function ($q) use ($request) {
            return $q->where('payment_status', $request->payment_status);
        })->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);

        // Get sales grouped by month
        $salesByMonth = $query->get()->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('Y-m');
        });

        $monthlyReports = collect();

        foreach ($salesByMonth as $yearMonth => $sales) {
            $dailySales = $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->created_at)->format('Y-m-d');
            })->map(function ($daySales) {
                return [
                    'date' => Carbon::parse($daySales->first()->created_at)->format('d M, Y'),
                    'sales' => $daySales->map(function ($sale) {
                        return [
                            'created_at' => $sale->created_at->format('h:i A'),
                            'invoice_no' => $sale->invoice_no,
                            'customer' => optional($sale->customer)->name ?? 'Walk-in Customer',
                            'total' => $sale->total,
                            'paid' => $sale->paid,
                            'due' => $sale->due,
                            'payment_status' => $sale->payment_status,
                            'payments' => $sale->salePayments->map(function ($payment) {
                                return [
                                    'amount' => $payment->amount,
                                    'method' => $payment->payment_method,
                                    'bank_account' => $payment->bank_account_id ? [
                                        'name' => $payment->bankAccount->bank_name,
                                        'account' => $payment->bankAccount->account_number
                                    ] : null,
                                    'transaction_id' => $payment->transaction_id
                                ];
                            })
                        ];
                    })->values(),
                    'summary' => [
                        'total_sales' => $daySales->count(),
                        'total_amount' => $daySales->sum('total'),
                        'subtotal' => $daySales->sum('subtotal'),
                        'discount' => $daySales->sum('discount'),
                        'tax' => $daySales->sum('tax'),
                        'received' => $daySales->sum('paid'),
                        'due' => $daySales->sum('due'),
                        'payment_methods' => $daySales->flatMap->salePayments
                            ->groupBy('payment_method')
                            ->map(function ($methodPayments) {
                                return [
                                    'amount' => $methodPayments->sum('amount'),
                                    'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                        ->groupBy('bank_account_id')
                                        ->map(function ($bankPayments) {
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
            })->values();

            // Get all payments for this month's sales
            $monthPayments = $sales->flatMap->salePayments;

            $monthlyReports->push([
                'month' => Carbon::createFromFormat('Y-m', $yearMonth)->format('F Y'),
                'daily_sales' => $dailySales,
                'summary' => [
                    'total_sales' => $sales->count(),
                    'total_amount' => $sales->sum('total'),
                    'subtotal' => $sales->sum('subtotal'),
                    'discount' => $sales->sum('discount'),
                    'tax' => $sales->sum('tax'),
                    'received' => $sales->sum('paid'),
                    'due' => $sales->sum('due'),
                    'payment_methods' => $monthPayments
                        ->groupBy('payment_method')
                        ->map(function ($methodPayments) {
                            return [
                                'amount' => $methodPayments->sum('amount'),
                                'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                    ->groupBy('bank_account_id')
                                    ->map(function ($bankPayments) {
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

        // Get all payments for the entire query
        $allPayments = $query->get()->flatMap->salePayments;

        return [
            'monthly_reports' => $monthlyReports,
            'summary' => [
                'total_sales' => $query->count(),
                'total_amount' => $query->sum('total'),
                'subtotal' => $query->sum('subtotal'),
                'discount' => $query->sum('discount'),
                'tax' => $query->sum('tax'),
                'received' => $query->sum('paid'),
                'due' => $query->sum('due'),
                'payment_methods' => $allPayments
                    ->groupBy('payment_method')
                    ->map(function ($methodPayments) {
                        return [
                            'amount' => $methodPayments->sum('amount'),
                            'bank_details' => $methodPayments->whereNotNull('bank_account_id')
                                ->groupBy('bank_account_id')
                                ->map(function ($bankPayments) {
                                    $firstPayment = $bankPayments->first();
                                    return [
                                        'bank_name' => $firstPayment->bankAccount->bank_name,
                                        'account_number' => $firstPayment->bankAccount->account_number,
                                        'amount' => $bankPayments->sum('amount')
                                    ];
                                })
                        ];
                    })
            ],
            'filters' => [
                'from_date' => $fromDate->format('d M, Y'),
                'to_date' => $toDate->format('d M, Y')
            ]
        ];
    }
}


