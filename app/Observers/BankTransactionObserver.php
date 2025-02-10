<?php

namespace App\Observers;

use App\Models\BankTransaction;
use App\Models\BankAccount;

class BankTransactionObserver
{
    public function creating(BankTransaction $transaction)
    {
        // Get the latest transaction for this bank account
        $latestTransaction = BankTransaction::where('bank_account_id', $transaction->bank_account_id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $bank = BankAccount::find($transaction->bank_account_id);

        // Start with previous balance or bank opening balance
        $previousBalance = $latestTransaction ? $latestTransaction->running_balance : $bank->opening_balance;

        // Calculate new running balance
        if ($transaction->transaction_type === 'in') {
            $transaction->running_balance = $previousBalance + $transaction->amount;
        } else {
            $transaction->running_balance = $previousBalance - $transaction->amount;
        }
    }
}
