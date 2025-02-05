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
    protected $bankAccountId = 1; // Since you always use one bank

    public function index(Request $request)
    {
        // If no dates provided, use current month
        if (!$request->filled(['start_date', 'end_date'])) {
            $now = Carbon::now();
            $startDate = $now->startOfMonth()->format('Y-m-d');
            $endDate = $now->endOfMonth()->format('Y-m-d');
        } else {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        \Log::info('Date Range', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $receiptData = $this->getReceiptData($startDate, $endDate);
        $paymentData = $this->getPaymentData($startDate, $endDate);

        // For debugging
        \Log::info('Receipt Data', $receiptData);
        \Log::info('Payment Data', $paymentData);

        return Inertia::render('Admin/ReceiptPayment/Index', [
            'filters' => [
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

        // Calculate opening balance up to start date
        $openingBalance = $bank->opening_balance;

        // Sum all transactions before start date
        $previousTransactions = DB::table('bank_transactions')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('date', '<', $startDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN transaction_type = "in" THEN amount ELSE 0 END), 0) as total_in'),
                DB::raw('COALESCE(SUM(CASE WHEN transaction_type = "out" THEN amount ELSE 0 END), 0) as total_out')
            )
            ->first();

        // Calculate actual opening balance for the selected date
        $openingCashAtBank = $openingBalance + $previousTransactions->total_in - $previousTransactions->total_out;

        // Get sale collection for the period
        $salePaid = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('paid');

        // Get extra income for the period
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $this->bankAccountId)
            ->sum('amount');

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
            'extra_income' => (float) $extraIncome,
            'fund_receive' => (float) $fundReceive,
            'total' => (float) $totalReceipt,
        ];
    }

    protected function getPaymentData($startDate, $endDate)
    {
        // Get the bank account
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

        // Calculate closing balance for the period
        $currentTransactions = DB::table('bank_transactions')
            ->where('bank_account_id', $this->bankAccountId)
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN transaction_type = "in" THEN amount ELSE 0 END), 0) as total_in'),
                DB::raw('COALESCE(SUM(CASE WHEN transaction_type = "out" THEN amount ELSE 0 END), 0) as total_out')
            )
            ->first();

        $closingCashAtBank = $bank->opening_balance + $currentTransactions->total_in - $currentTransactions->total_out;

        // Calculate total payment
        $totalPayment = $purchaseAmount + $fundRefund + $expenses + $closingCashAtBank;

        return [
            'purchase' => (float) $purchaseAmount,
            'fund_refund' => (float) $fundRefund,
            'expenses' => (float) $expenses,
            'closing_cash_at_bank' => (float) $closingCashAtBank,
            'total' => (float) $totalPayment,
        ];
    }

    public function downloadPdf(Request $request)
    {
        try {
            // Validate and set date range
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            // Set default to current month if no dates provided
            $startDate = $request->start_date
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::now()->startOfMonth();
            $endDate = $request->end_date
                ? Carbon::parse($request->end_date)->endOfDay()
                : Carbon::now()->endOfDay();

            // Get receipt and payment data
            $receiptData = $this->getReceiptData($startDate, $endDate);
            $paymentData = $this->getPaymentData($startDate, $endDate);

            // Prepare data for PDF
            $data = [
                'receipt' => $receiptData,
                'payment' => $paymentData,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];

            // For debugging
            \Log::info('PDF Data:', $data);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.receipt-payment', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            // Generate filename
            $filename = 'receipt-payment-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf';

            // Download PDF
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
