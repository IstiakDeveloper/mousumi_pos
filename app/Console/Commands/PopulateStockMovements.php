<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class PopulateStockMovements extends Command
{
    protected $signature = 'stock:populate-movements {--product=}';
    protected $description = 'Populate stock movements from existing data';

    public function handle()
    {
        $this->info('Populating stock movements...');

        try {
            // Process in smaller chunks to avoid memory issues
            $productId = $this->option('product');

            if ($productId) {
                $this->processProduct($productId);
            } else {
                // Clear existing stock movements
                StockMovement::truncate();

                // Process products in chunks
                Product::chunk(50, function ($products) {
                    foreach ($products as $product) {
                        $this->processProduct($product->id);
                    }
                });
            }

            $this->info("\nStock movements populated successfully!");
            return 0;

        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function processProduct($productId)
    {
        try {
            DB::beginTransaction();

            $product = Product::find($productId);
            if (!$product) {
                $this->warn("Product ID {$productId} not found");
                DB::rollBack();
                return;
            }

            $this->info("Processing product: {$product->name}");

            // Delete existing movements for this product
            StockMovement::where('product_id', $productId)->delete();

            $movements = [];
            $runningQuantity = 0;

            // Get all stock purchases
            $purchases = ProductStock::where('product_id', $productId)
                ->where('type', 'purchase')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($purchases as $purchase) {
                $beforeQuantity = $runningQuantity;
                $runningQuantity += $purchase->quantity;

                $movements[] = [
                    'product_id' => $productId,
                    'reference_type' => ProductStock::class,
                    'reference_id' => $purchase->id,
                    'quantity' => $purchase->quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $runningQuantity,
                    'type' => 'in',
                    'created_by' => $purchase->created_by,
                    'created_at' => $purchase->created_at,
                    'updated_at' => $purchase->created_at,
                ];
            }

            // Get all sales
            $sales = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $productId)
                ->whereNull('sales.deleted_at')
                ->orderBy('sales.created_at')
                ->orderBy('sales.id')
                ->select('sale_items.*', 'sales.created_at as sale_date', 'sales.created_by as sale_created_by')
                ->get();

            foreach ($sales as $sale) {
                $beforeQuantity = $runningQuantity;
                $runningQuantity -= $sale->quantity;

                $movements[] = [
                    'product_id' => $productId,
                    'reference_type' => SaleItem::class,
                    'reference_id' => $sale->id,
                    'quantity' => $sale->quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => max(0, $runningQuantity),
                    'type' => 'out',
                    'created_by' => $sale->sale_created_by,
                    'created_at' => $sale->sale_date,
                    'updated_at' => $sale->sale_date,
                ];
            }

            // Sort movements by date
            usort($movements, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });

            // Recalculate running quantities after sorting
            $runningQty = 0;
            foreach ($movements as &$movement) {
                $movement['before_quantity'] = $runningQty;
                if ($movement['type'] === 'in') {
                    $runningQty += $movement['quantity'];
                } else {
                    $runningQty -= $movement['quantity'];
                }
                $movement['after_quantity'] = max(0, $runningQty);
            }

            // Insert in chunks to avoid memory issues
            foreach (array_chunk($movements, 100) as $chunk) {
                StockMovement::insert($chunk);
            }

            DB::commit();
            $this->info("Completed product: {$product->name}");

        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Error processing product {$productId}: " . $e->getMessage());
            throw $e;
        }
    }
}
