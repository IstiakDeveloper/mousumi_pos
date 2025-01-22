<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'total_quantity',
        'total_cost',
        'unit_cost',
        'note',
        'created_by',
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

    // Add the createdBy relationship
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
