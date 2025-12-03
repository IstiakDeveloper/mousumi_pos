<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateProductStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocks:recalculate {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate available_quantity for all product stocks based on purchases and sales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting product stock recalculation...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all products
        $products = Product::where('status', true)->get();
        $this->info("Found {$products->count()} active products");

        $updatedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            try {
                // Get all stock transactions for this product, ordered by date
                $stockTransactions = ProductStock::where('product_id', $product->id)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($stockTransactions->isEmpty()) {
                    $progressBar->advance();
                    continue;
                }

                $runningQuantity = 0;

                foreach ($stockTransactions as $transaction) {
                    $oldAvailableQty = $transaction->available_quantity;

                    // Calculate new available quantity
                    if ($transaction->type === 'purchase') {
                        $runningQuantity += $transaction->quantity;
                    } else {
                        // For other types (if any), subtract
                        $runningQuantity -= $transaction->quantity;
                    }

                    // Also need to subtract sales up to this point
                    $salesUpToThisPoint = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sale_items.product_id', $product->id)
                        ->where('sales.created_at', '<=', $transaction->created_at)
                        ->whereNull('sales.deleted_at')
                        ->sum('sale_items.quantity');

                    $newAvailableQty = $runningQuantity - $salesUpToThisPoint;

                    // Ensure non-negative
                    $newAvailableQty = max(0, $newAvailableQty);

                    if (abs($oldAvailableQty - $newAvailableQty) > 0.01) {
                        if (!$dryRun) {
                            $transaction->available_quantity = $newAvailableQty;
                            $transaction->saveQuietly();
                        }
                        $updatedCount++;
                    }
                }

            } catch (\Exception $e) {
                $this->error("\nError processing product {$product->name}: " . $e->getMessage());
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("Would update {$updatedCount} stock records");
        } else {
            $this->info("Successfully updated {$updatedCount} stock records");
        }

        if ($errorCount > 0) {
            $this->error("Encountered {$errorCount} errors during processing");
        }

        $this->info('Product stock recalculation completed!');

        return Command::SUCCESS;
    }
}
