<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerSalesReportController extends Controller
{
    public function index()
    {
        $customers = Customer::select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Reports/CustomerSales/Index', [
            'customers' => $customers,
            'months' => $this->getMonthsList(),
            'years' => $this->getYearsList(),
        ]);
    }

    public function getReportData(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $year = $request->year;
        $month = $request->month;

        // Get previous balance
        $previousBalance = Sale::where('customer_id', $customer->id)
            ->whereDate('created_at', '<', Carbon::createFromDate($year, $month, 1))
            ->sum('due');

        // Get monthly sales
        $monthlySales = Sale::where('customer_id', $customer->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['saleItems.product', 'salePayments'])
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'date' => $sale->created_at->format('Y-m-d'),
                    'invoice_no' => $sale->invoice_no,
                    'total' => $sale->total,
                    'paid' => $sale->paid,
                    'due' => $sale->due,
                    'items' => $sale->saleItems->map(function ($item) {
                        return [
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ];
                    }),
                    'payments' => $sale->salePayments->map(function ($payment) {
                        return [
                            'date' => $payment->created_at->format('Y-m-d'),
                            'amount' => $payment->amount,
                            'method' => $payment->payment_method,
                        ];
                    }),
                ];
            });

        return response()->json([
            'customer' => $customer,
            'previousBalance' => $previousBalance,
            'monthlySales' => $monthlySales,
            'monthlyTotals' => [
                'total_sales' => $monthlySales->sum('total'),
                'total_paid' => $monthlySales->sum('paid'),
                'total_due' => $monthlySales->sum('due'),
            ],
        ]);
    }

    private function getMonthsList()
    {
        return collect(range(1, 12))->map(function ($month) {
            return [
                'value' => $month,
                'label' => Carbon::create()->month($month)->format('F'),
            ];
        });
    }

    private function getYearsList()
    {
        $currentYear = Carbon::now()->year;
        return collect(range($currentYear - 4, $currentYear))->map(function ($year) {
            return [
                'value' => $year,
                'label' => $year,
            ];
        });
    }
}
