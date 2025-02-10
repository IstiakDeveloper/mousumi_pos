<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Add the running_balance column to the bank_transactions table
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->decimal('running_balance', 10, 2)->after('amount');
        });

        // Update running_balance for all existing transactions
        $banks = DB::table('bank_accounts')->get();

        foreach ($banks as $bank) {
            // Start with the bank's opening balance
            $balance = $bank->opening_balance;

            // Get transactions related to the current bank account, ordered by date and id
            DB::table('bank_transactions')
                ->where('bank_account_id', $bank->id)
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->each(function ($transaction) use (&$balance) {
                    // Skip if the transaction is deleted (soft deleted)
                    if ($transaction->deleted_at !== null) {
                        return; // Skip this transaction
                    }

                    // Adjust the balance based on the transaction type
                    if ($transaction->transaction_type === 'in') {
                        $balance += $transaction->amount;
                    } else {
                        $balance -= $transaction->amount;
                    }

                    // Update the transaction with the calculated running balance
                    DB::table('bank_transactions')
                        ->where('id', $transaction->id)
                        ->update(['running_balance' => $balance]);
                });
        }
    }

    public function down()
    {
        // Drop the running_balance column in the bank_transactions table
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('running_balance');
        });
    }
};
