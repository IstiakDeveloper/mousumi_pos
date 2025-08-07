<?php

namespace App\Traits;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait BankBalanceCalculator
{
    /**
     * Calculate accurate closing balance for a given month
     * This method ensures consistency across all reports
     */
    protected function calculateAccurateClosingBalance($bankAccountId, $year, $month)
    {
        // Get bank account
        $bankAccount = BankAccount::findOrFail($bankAccountId);

        // Get month boundaries
        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $previousMonthEnd = $startOfMonth->copy()->subDay();

        // Get opening balance (from previous month's closing)
        $openingBalance = $this->getOpeningBalance($bankAccountId, $previousMonthEnd, $bankAccount->opening_balance);

        // Get all daily data for the month
        $dailyData = $this->getDailyTransactionData($bankAccountId, $startOfMonth, $endOfMonth);

        // Calculate step by step
        $runningBalance = $openingBalance;
        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dailyData->get($dateStr);

            if ($dayData) {
                $totalIn = $dayData['total_in'];
                $totalOut = $dayData['total_out'];
                $runningBalance = $runningBalance + $totalIn - $totalOut;
            }

            $currentDate->addDay();
        }

        return round($runningBalance, 2);
    }

    /**
     * Get opening balance for a specific date
     */
    protected function getOpeningBalance($bankAccountId, $beforeDate, $defaultBalance)
    {
        $lastTransaction = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('date', '<=', $beforeDate)
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastTransaction ? $lastTransaction->running_balance : $defaultBalance;
    }

    /**
     * Get comprehensive daily transaction data
     */
    protected function getDailyTransactionData($bankAccountId, $startDate, $endDate)
    {
        // Get bank transactions grouped by date
        $bankTransactions = DB::table('bank_transactions')
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
            ->where('bank_account_id', $bankAccountId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get extra incomes grouped by date
        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('COALESCE(SUM(amount), 0) as total_amount'))
            ->where('bank_account_id', $bankAccountId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Combine and calculate totals for each date
        $combinedData = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $bankTx = $bankTransactions->get($dateStr);
            $extraIncome = $extraIncomes->get($dateStr);

            // Calculate day totals
            $fundIn = $bankTx ? floatval($bankTx->fund_in) : 0;
            $paymentReceive = $bankTx ? floatval($bankTx->payment_receive) : 0;
            $refund = $bankTx ? floatval($bankTx->refund) : 0;
            $fundOut = $bankTx ? floatval($bankTx->fund_out) : 0;
            $purchase = $bankTx ? floatval($bankTx->purchase) : 0;
            $expense = $bankTx ? floatval($bankTx->expense) : 0;
            $extraIncomeAmount = $extraIncome ? floatval($extraIncome->total_amount) : 0;

            $totalIn = $fundIn + $paymentReceive + $extraIncomeAmount + $refund;
            $totalOut = $fundOut + $purchase + $expense;

            $combinedData->put($dateStr, [
                'date' => $dateStr,
                'fund_in' => $fundIn,
                'payment_receive' => $paymentReceive,
                'extra_income' => $extraIncomeAmount,
                'refund' => $refund,
                'fund_out' => $fundOut,
                'purchase' => $purchase,
                'expense' => $expense,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net_change' => $totalIn - $totalOut
            ]);

            $currentDate->addDay();
        }

        return $combinedData;
    }

    /**
     * Get month totals for reporting
     */
    protected function getMonthTotals($bankAccountId, $year, $month)
    {
        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $dailyData = $this->getDailyTransactionData($bankAccountId, $startOfMonth, $endOfMonth);

        return [
            'in' => [
                'fund' => round($dailyData->sum('fund_in'), 2),
                'payment' => round($dailyData->sum('payment_receive'), 2),
                'extra' => round($dailyData->sum('extra_income'), 2),
                'refund' => round($dailyData->sum('refund'), 2),
                'total' => round($dailyData->sum('total_in'), 2)
            ],
            'out' => [
                'fund' => round($dailyData->sum('fund_out'), 2),
                'purchase' => round($dailyData->sum('purchase'), 2),
                'expense' => round($dailyData->sum('expense'), 2),
                'total' => round($dailyData->sum('total_out'), 2)
            ],
            'net_change' => round($dailyData->sum('net_change'), 2)
        ];
    }

    /**
     * Get daily balance progression for detailed reports
     */
    protected function getDailyBalanceProgression($bankAccountId, $year, $month)
    {
        $bankAccount = BankAccount::findOrFail($bankAccountId);
        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $previousMonthEnd = $startOfMonth->copy()->subDay();

        // Get opening balance
        $runningBalance = $this->getOpeningBalance($bankAccountId, $previousMonthEnd, $bankAccount->opening_balance);

        // Get daily data
        $dailyData = $this->getDailyTransactionData($bankAccountId, $startOfMonth, $endOfMonth);

        // Calculate daily progression
        $progression = [];
        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dailyData->get($dateStr);

            if ($dayData) {
                $runningBalance += $dayData['net_change'];
            }

            $progression[] = [
                'date' => $dateStr,
                'in' => [
                    'fund' => round($dayData['fund_in'] ?? 0, 2),
                    'payment' => round($dayData['payment_receive'] ?? 0, 2),
                    'extra' => round($dayData['extra_income'] ?? 0, 2),
                    'refund' => round($dayData['refund'] ?? 0, 2),
                    'total' => round($dayData['total_in'] ?? 0, 2)
                ],
                'out' => [
                    'fund' => round($dayData['fund_out'] ?? 0, 2),
                    'purchase' => round($dayData['purchase'] ?? 0, 2),
                    'expense' => round($dayData['expense'] ?? 0, 2),
                    'total' => round($dayData['total_out'] ?? 0, 2)
                ],
                'balance' => round($runningBalance, 2)
            ];

            $currentDate->addDay();
        }

        return $progression;
    }
}
