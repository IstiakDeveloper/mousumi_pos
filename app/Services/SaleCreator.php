<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SaleCreator
{
    /**
     * Create a Sale exactly like POS (stock lock/update + customer balance + optional bank payment).
     *
     * @param  array<int, array{product_id:int, quantity:numeric, unit_price:numeric}>  $items
     */
    public function create(array $items, float $subtotal, float $discount, float $total, float $paid, ?int $bankAccountId, ?int $customerId, ?string $note, ?int $createdBy, ?string $date = null): Sale
    {
        $date = $date ?: now()->toDateString();

        return DB::transaction(function () use ($items, $subtotal, $discount, $total, $paid, $bankAccountId, $customerId, $note, $createdBy, $date) {
            $invoiceNumber = $this->generateInvoiceNo();

            $sale = Sale::create([
                'invoice_no' => $invoiceNumber,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'due' => $total - $paid,
                'payment_status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                'note' => $note,
                'created_by' => $createdBy,
            ]);

            foreach ($items as $item) {
                $currentStock = ProductStock::where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity - $item['quantity'];

                if ($afterQuantity < 0) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}, Available: {$beforeQuantity}, Requested: {$item['quantity']}");
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                ProductStock::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'],
                    'available_quantity' => $afterQuantity,
                    'type' => 'sale',
                    'date' => $date,
                    'unit_cost' => $currentStock ? $currentStock->unit_cost : 0,
                    'total_cost' => $currentStock ? ($currentStock->unit_cost * $item['quantity']) : 0,
                    'created_by' => $createdBy,
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'quantity' => $item['quantity'],
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'type' => 'out',
                    'created_by' => $createdBy,
                ]);
            }

            if ($customerId && $sale->due > 0) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $newBalance = $customer->balance + $sale->due;
                    if ($newBalance > $customer->credit_limit) {
                        throw new \Exception("This sale would exceed the customer's credit limit. Current balance: {$customer->balance}, Credit limit: {$customer->credit_limit}, New due: {$sale->due}");
                    }
                    $customer->increment('balance', (float) $sale->due);
                }
            }

            if ($paid > 0) {
                if (! $bankAccountId) {
                    throw new \Exception('Bank account is required when paid amount is greater than 0.');
                }

                $bankAccount = BankAccount::findOrFail($bankAccountId);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $paid,
                    'date' => $date,
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'created_by' => $createdBy,
                ]);
                $bankAccount->increment('current_balance', $paid);
            }

            return $sale;
        });
    }

    private function generateInvoiceNo(): string
    {
        $date = date('Ymd');
        $attempt = 0;

        do {
            $attempt++;
            $count = Sale::where('invoice_no', 'like', "INV-{$date}-%")->count();
            $invoiceNumber = 'INV-'.$date.'-'.str_pad($count + $attempt, 4, '0', STR_PAD_LEFT);
            $exists = Sale::where('invoice_no', $invoiceNumber)->exists();
        } while ($exists && $attempt < 10);

        if ($attempt >= 10) {
            throw new \Exception('Failed to generate a unique invoice number after multiple attempts');
        }

        return $invoiceNumber;
    }
}

