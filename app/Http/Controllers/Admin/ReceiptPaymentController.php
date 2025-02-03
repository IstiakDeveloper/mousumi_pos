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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceiptPaymentController extends Controller
{
    public function index(Request $request)
    {
        // Default to current month if no dates are provided
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Fetch data for the specified date range
        $receiptData = $this->getReceiptData($startDate, $endDate);
        $paymentData = $this->getPaymentData($startDate, $endDate);

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
        // Assuming bank_account_id is 1 as specified
        $bankAccountId = 1;

        // Ensure bank exists, use 0 if not found
        $bank = BankAccount::find($bankAccountId) ?? new BankAccount();

        // Opening Cash at Bank (previous month's closing balance)
        $openingCashAtBank = $bank->opening_balance ?? 0;

        // Sale Collection (Sale Paid)
        $salePaid = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')  // Explicitly exclude soft-deleted records
            ->sum('paid');

        // Extra Income
        $extraIncome = ExtraIncome::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $bankAccountId)
            ->sum('amount');

        // Fund Receive (Fund IN)
        $fundReceive = Fund::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $bankAccountId)
            ->where('type', 'in')
            ->sum('amount');

        // Calculate Total Receipt
        $totalReceipt = $openingCashAtBank + $salePaid + $extraIncome + $fundReceive;

        return [
            'opening_cash_on_bank' => (float)$openingCashAtBank,
            'sale_collection' => (float)$salePaid,
            'extra_income' => (float)$extraIncome,
            'fund_receive' => (float)$fundReceive,
            'total' => (float)$totalReceipt,
        ];
    }

    protected function getPaymentData($startDate, $endDate)
    {
        // Assuming bank_account_id is 1 as specified
        $bankAccountId = 1;

        // Ensure bank exists, use 0 if not found
        $bank = BankAccount::find($bankAccountId) ?? new BankAccount();

        // Purchase (Total Cost of Product Stocks)
        $purchaseAmount = ProductStock::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'purchase')
            ->sum('total_cost');

        // Fund Refund (Fund OUT)
        $fundRefund = Fund::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $bankAccountId)
            ->where('type', 'out')
            ->sum('amount');

        // Expenses
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->where('bank_account_id', $bankAccountId)
            ->sum('amount');

        // Closing Cash at Bank (current balance)
        $closingCashAtBank = $bank->current_balance ?? 0;

        // Calculate Total Payment
        $totalPayment = $purchaseAmount + $fundRefund + $expenses + $closingCashAtBank;

        return [
            'purchase' => (float)$purchaseAmount,
            'fund_refund' => (float)$fundRefund,
            'expenses' => (float)$expenses,
            'closing_cash_at_bank' => (float)$closingCashAtBank,
            'total' => (float)$totalPayment,
        ];
    }
}
