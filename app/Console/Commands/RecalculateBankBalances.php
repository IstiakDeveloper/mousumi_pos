<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Console\Command;

class RecalculateBankBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:recalculate-balances {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all bank account balances and running balances from transactions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $bankAccounts = BankAccount::withTrashed()->get();

        if ($bankAccounts->isEmpty()) {
            $this->error('No bank accounts found.');

            return self::FAILURE;
        }

        foreach ($bankAccounts as $bankAccount) {
            $this->info("Processing Bank Account: {$bankAccount->account_name} (ID: {$bankAccount->id})");

            // Get all transactions ordered by date and ID
            $transactions = BankTransaction::where('bank_account_id', $bankAccount->id)
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($transactions->isEmpty()) {
                $this->warn('  No transactions found. Setting current_balance to opening_balance.');

                if (! $isDryRun) {
                    $bankAccount->current_balance = $bankAccount->opening_balance;
                    $bankAccount->save();
                }

                $this->line("  Opening Balance: {$bankAccount->opening_balance}");
                $this->line("  Current Balance: {$bankAccount->opening_balance}");
                $this->newLine();

                continue;
            }

            $runningBalance = $bankAccount->opening_balance;
            $transactionUpdates = [];

            $this->line('  Opening Balance: '.number_format((float) $bankAccount->opening_balance, 2));
            $this->line("  Total Transactions: {$transactions->count()}");

            foreach ($transactions as $transaction) {
                $oldRunningBalance = $transaction->running_balance;

                // Calculate new running balance
                if ($transaction->transaction_type === 'in') {
                    $runningBalance += $transaction->amount;
                } else {
                    $runningBalance -= $transaction->amount;
                }

                // Store update if different
                if (bccomp((string) $oldRunningBalance, (string) $runningBalance, 2) !== 0) {
                    $transactionUpdates[] = [
                        'id' => $transaction->id,
                        'date' => $transaction->date,
                        'type' => $transaction->transaction_type,
                        'amount' => $transaction->amount,
                        'old_balance' => $oldRunningBalance,
                        'new_balance' => $runningBalance,
                    ];
                }

                // Update transaction running_balance
                if (! $isDryRun) {
                    $transaction->running_balance = $runningBalance;
                    $transaction->saveQuietly(); // Save without triggering observers
                }
            }

            $oldCurrentBalance = $bankAccount->current_balance;
            $newCurrentBalance = $runningBalance;

            $this->line('  Old Current Balance: '.number_format((float) $oldCurrentBalance, 2));
            $this->line('  New Current Balance: '.number_format((float) $newCurrentBalance, 2));

            $difference = $newCurrentBalance - $oldCurrentBalance;
            if (abs($difference) > 0.01) {
                $this->warn('  ⚠️  Difference: '.number_format((float) $difference, 2));

                if (count($transactionUpdates) > 0) {
                    $this->warn('  '.count($transactionUpdates).' transactions will be updated:');
                    foreach (array_slice($transactionUpdates, 0, 5) as $update) {
                        $this->line("    - TX #{$update['id']} ({$update['date']}): {$update['old_balance']} → {$update['new_balance']}");
                    }
                    if (count($transactionUpdates) > 5) {
                        $this->line('    ... and '.(count($transactionUpdates) - 5).' more');
                    }
                }
            } else {
                $this->info('  ✓ Balance is correct');
            }

            // Update bank account current_balance
            if (! $isDryRun) {
                $bankAccount->current_balance = $newCurrentBalance;
                $bankAccount->save();
            }

            $this->newLine();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('🔍 DRY RUN COMPLETE - Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✓ All bank balances have been recalculated successfully!');
        }

        return self::SUCCESS;
    }
}
