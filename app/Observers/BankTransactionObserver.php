<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;

class BankTransactionObserver
{
    /**
     * Handle the BankTransaction "creating" event.
     */
    public function creating(BankTransaction $transaction): void
    {
        // Get the latest transaction for this bank account (exclude soft deleted)
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

    /**
     * Handle the BankTransaction "created" event.
     * Update bank account current balance after transaction is created.
     */
    public function created(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance += $transaction->amount;
            } else {
                $bankAccount->current_balance -= $transaction->amount;
            }

            $bankAccount->saveQuietly(); // Save without triggering observers
        }
    }

    /**
     * Handle the BankTransaction "updated" event.
     */
    public function updated(BankTransaction $transaction): void
    {
        // If amount or transaction_type changed, we need to update bank balance
        if ($transaction->isDirty(['amount', 'transaction_type'])) {
            $bankAccount = BankAccount::find($transaction->bank_account_id);

            if ($bankAccount) {
                $oldAmount = $transaction->getOriginal('amount');
                $oldType = $transaction->getOriginal('transaction_type');
                $newAmount = $transaction->amount;
                $newType = $transaction->transaction_type;

                // Reverse old transaction effect
                if ($oldType === 'in') {
                    $bankAccount->current_balance -= $oldAmount;
                } else {
                    $bankAccount->current_balance += $oldAmount;
                }

                // Apply new transaction effect
                if ($newType === 'in') {
                    $bankAccount->current_balance += $newAmount;
                } else {
                    $bankAccount->current_balance -= $newAmount;
                }

                $bankAccount->saveQuietly();
            }
        }
    }

    /**
     * Handle the BankTransaction "deleted" event.
     */
    public function deleted(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            // Reverse the transaction effect
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance -= $transaction->amount;
            } else {
                $bankAccount->current_balance += $transaction->amount;
            }

            $bankAccount->saveQuietly();
        }
    }

    /**
     * Handle the BankTransaction "restored" event.
     */
    public function restored(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            // Reapply the transaction effect
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance += $transaction->amount;
            } else {
                $bankAccount->current_balance -= $transaction->amount;
            }

            $bankAccount->saveQuietly();
        }
    }
}
