<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSalePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:fix-payments {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix sales where paid amount exceeds total amount or due calculation is incorrect';

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

        // Find sales with incorrect payments
        $incorrectSales = Sale::where(function ($query) {
            // Paid > Total (overpayment)
            $query->whereRaw('paid > total')
                // OR incorrect due calculation
                ->orWhereRaw('(total - paid) != due');
        })
            ->whereNull('deleted_at')
            ->get();

        if ($incorrectSales->isEmpty()) {
            $this->info('✓ No sales with incorrect payments found!');

            return self::SUCCESS;
        }

        $this->warn("Found {$incorrectSales->count()} sales with incorrect payments:");
        $this->newLine();

        $fixedCount = 0;

        foreach ($incorrectSales as $sale) {
            $oldPaid = $sale->paid;
            $oldDue = $sale->due;

            // Calculate correct values
            $correctPaid = min($sale->paid, $sale->total); // Paid cannot exceed total
            $correctDue = $sale->total - $correctPaid;
            $correctPaymentStatus = $correctPaid >= $sale->total ? 'paid' : ($correctPaid > 0 ? 'partial' : 'due');

            // Show what will be fixed
            $this->line("Sale #{$sale->id} ({$sale->invoice_no}):");
            $this->line("  Total: {$sale->total}");
            $this->line("  Old Paid: {$oldPaid} → New Paid: {$correctPaid}");
            $this->line("  Old Due: {$oldDue} → New Due: {$correctDue}");
            $this->line("  Payment Status: {$sale->payment_status} → {$correctPaymentStatus}");

            if ($oldPaid > $sale->total) {
                $overpayment = $oldPaid - $sale->total;
                $this->warn("  ⚠️  Overpayment: {$overpayment}");
            }

            if (! $isDryRun) {
                DB::transaction(function () use ($sale, $correctPaid, $correctDue, $correctPaymentStatus) {
                    $sale->update([
                        'paid' => $correctPaid,
                        'due' => $correctDue,
                        'payment_status' => $correctPaymentStatus,
                    ]);
                });

                $fixedCount++;
            }

            $this->newLine();
        }

        if ($isDryRun) {
            $this->info('🔍 DRY RUN COMPLETE - Run without --dry-run to apply changes');
        } else {
            $this->info("✓ Fixed {$fixedCount} sales successfully!");
        }

        return self::SUCCESS;
    }
}
