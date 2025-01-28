<?php

namespace App\Traits;

use App\Models\ProductStock;
use App\Models\StockMovement;

trait ManagesStock
{
    public function getCurrentStock($productId)
    {
        return ProductStock::where('product_id', $productId)
            ->orderBy('id', 'desc')
            ->first()
            ?->available_quantity ?? 0;
    }

    public function updateProductStock($productId, $quantity, $type, $referenceType, $referenceId)
    {
        $currentStock = $this->getCurrentStock($productId);
        $newQuantity = $type === 'in' ? $currentStock + $quantity : $currentStock - $quantity;

        if ($newQuantity < 0) {
            throw new \Exception("Insufficient stock for product ID: {$productId}");
        }

        // Create stock movement record
        StockMovement::create([
            'product_id' => $productId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'before_quantity' => $currentStock,
            'after_quantity' => $newQuantity,
            'type' => $type,
            'created_by' => auth()->id(),
        ]);

        return $newQuantity;
    }
}
