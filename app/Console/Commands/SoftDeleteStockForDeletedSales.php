<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SoftDeleteStockForDeletedSales extends Command
{
    protected $signature = 'stock:soft-delete-for-deleted-sales';
    protected $description = 'Soft delete stock entries for soft deleted sales';

    public function handle()
    {
        $this->info('Starting soft delete of stock entries for deleted sales...');

        DB::transaction(function () {
            // Find all soft deleted sales
            $deletedSales = Sale::onlyTrashed()->with('saleItems')->get();

            $this->info("Found {$deletedSales->count()} deleted sales");

            foreach ($deletedSales as $sale) {
                $this->info("Processing deleted sale: {$sale->invoice_no}");

                foreach ($sale->saleItems as $item) {
                    // Soft delete product stock entries
                    $stockDeleted = ProductStock::where('product_id', $item->product_id)
                        ->where('type', 'sale')
                        ->where('quantity', -$item->quantity)
                        ->whereBetween('created_at', [
                            $sale->created_at->startOfDay(),
                            $sale->created_at->endOfDay()
                        ])
                        ->delete(); // This will soft delete due to trait

                    if ($stockDeleted) {
                        $this->info("Soft deleted stock entry for product ID: {$item->product_id}");
                    }

                    // Soft delete stock movements
                    $movementDeleted = StockMovement::where('product_id', $item->product_id)
                        ->where('reference_type', 'sale')
                        ->where('reference_id', $sale->id)
                        ->delete(); // This will soft delete due to trait

                    if ($movementDeleted) {
                        $this->info("Soft deleted stock movement for product ID: {$item->product_id}");
                    }
                }

                // Also mark the sale's created date in stock entries
                ProductStock::where('type', 'sale')
                    ->whereBetween('created_at', [
                        $sale->created_at->startOfDay(),
                        $sale->created_at->endOfDay()
                    ])
                    ->whereHas('product', function ($query) use ($sale) {
                        $query->whereIn('id', $sale->saleItems->pluck('product_id'));
                    })
                    ->update(['deleted_at' => $sale->deleted_at]);
            }
        });

        $this->info('Soft delete completed successfully!');
        return 0;
    }
}
