<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductStock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'total_quantity',
        'total_cost',
        'unit_cost',
        'note',
        'created_by',
        'available_quantity',
        'type',
        'bank_account_id'  // Add this

    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }


    // Add the createdBy relationship
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
