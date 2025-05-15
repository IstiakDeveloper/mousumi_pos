<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecalculateHistoricalStock extends Command
{
    protected $signature = 'stock:recalculate-historical {--from=2024-01-01} {--to=2025-01-29}';
    protected $description = 'Recalculate historical stock data for old records';

    public function handle()
    {
        $fromDate = Carbon::parse($this->option('from'));
        $toDate = Carbon::parse($this->option('to'));

        $this->info("Recalculating stock from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}");

        DB::transaction(function () use ($fromDate, $toDate) {
            $products = Product::all();
            $bar = $this->output->createProgressBar(count($products));

            foreach ($products as $product) {
                $this->recalculateProductStock($product, $fromDate, $toDate);
                $bar->advance();
            }

            $bar->finish();
        });

        $this->info("\nHistorical stock recalculation completed!");
        return 0;
    }

    private function recalculateProductStock($product, $fromDate, $toDate)
    {
        // Get all transactions for this product in date range
        $transactions = collect();

        // Get purchases
        $purchases = ProductStock::where('product_id', $product->id)
            ->where('type', 'purchase')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at')
            ->get();

        foreach ($purchases as $purchase) {
            $transactions->push([
                'type' => 'purchase',
                'date' => $purchase->created_at,
                'quantity' => $purchase->quantity,
                'reference_type' => 'App\Models\ProductStock',
                'reference_id' => $purchase->id,
                'created_by' => $purchase->created_by,
                'data' => $purchase
            ]);
        }

        // Get sales
        $sales = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $product->id)
            ->whereBetween('sales.created_at', [$fromDate, $toDate])
            ->whereNull('sales.deleted_at')
            ->orderBy('sales.created_at')
            ->select('sale_items.*', 'sales.created_at as sale_date', 'sales.created_by', 'sales.invoice_no')
            ->get();

        foreach ($sales as $sale) {
            $transactions->push([
                'type' => 'sale',
                'date' => Carbon::parse($sale->sale_date),
                'quantity' => $sale->quantity,
                'reference_type' => 'App\Models\SaleItem',
                'reference_id' => $sale->id,
                'created_by' => $sale->created_by,
                'data' => $sale
            ]);
        }

        // Sort by date
        $transactions = $transactions->sortBy('date');

        // Get running quantity before the date range
        $beforeQuantity = $this->getQuantityBefore($product->id, $fromDate);
        $runningQuantity = $beforeQuantity;

        // Process each transaction
        foreach ($transactions as $transaction) {
            $beforeQty = $runningQuantity;

            if ($transaction['type'] === 'purchase') {
                $runningQuantity += $transaction['quantity'];

                // Update product_stocks available_quantity if needed
                $stock = ProductStock::find($transaction['data']->id);
                if ($stock && $stock->available_quantity != $runningQuantity) {
                    $stock->update([
                        'available_quantity' => $runningQuantity
                    ]);
                }

                // Create stock movement if missing
                $existingMovement = StockMovement::where('reference_type', $transaction['reference_type'])
                    ->where('reference_id', $transaction['reference_id'])
                    ->first();

                if (!$existingMovement) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'reference_type' => $transaction['reference_type'],
                        'reference_id' => $transaction['reference_id'],
                        'quantity' => $transaction['quantity'],
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $runningQuantity,
                        'type' => 'in',
                        'created_by' => $transaction['created_by'],
                        'created_at' => $transaction['date'],
                        'updated_at' => $transaction['date']
                    ]);
                }

            } else if ($transaction['type'] === 'sale') {
                $runningQuantity -= $transaction['quantity'];

                // Check if product_stocks entry exists for this sale
                $existingStock = ProductStock::where('product_id', $product->id)
                    ->where('type', 'sale')
                    ->whereDate('created_at', $transaction['date']->format('Y-m-d'))
                    ->where('quantity', -$transaction['quantity'])
                    ->first();

                if (!$existingStock) {
                    // Create missing stock entry
                    ProductStock::create([
                        'product_id' => $product->id,
                        'quantity' => -$transaction['quantity'],
                        'available_quantity' => $runningQuantity,
                        'type' => 'sale',
                        'unit_cost' => 0, // You may need to calculate this
                        'total_cost' => 0,
                        'created_by' => $transaction['created_by'],
                        'created_at' => $transaction['date'],
                        'updated_at' => $transaction['date']
                    ]);
                }

                // Create stock movement if missing
                $existingMovement = StockMovement::where('reference_type', $transaction['reference_type'])
                    ->where('reference_id', $transaction['reference_id'])
                    ->first();

                if (!$existingMovement) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'reference_type' => $transaction['reference_type'],
                        'reference_id' => $transaction['reference_id'],
                        'quantity' => $transaction['quantity'],
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $runningQuantity,
                        'type' => 'out',
                        'created_by' => $transaction['created_by'],
                        'created_at' => $transaction['date'],
                        'updated_at' => $transaction['date']
                    ]);
                }
            }
        }

        $this->info("\nProduct {$product->name}: Final quantity = {$runningQuantity}");
    }

    private function getQuantityBefore($productId, $date)
    {
        // Calculate total purchases before date
        $purchaseQty = ProductStock::where('product_id', $productId)
            ->where('type', 'purchase')
            ->where('created_at', '<', $date)
            ->sum('quantity');

        // Calculate total sales before date
        $saleQty = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $productId)
            ->where('sales.created_at', '<', $date)
            ->whereNull('sales.deleted_at')
            ->sum('sale_items.quantity');

        return $purchaseQty - $saleQty;
    }
}
