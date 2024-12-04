<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'additional_cost',
        'additional_price',
        'specifications',
        'status'
    ];

    protected $casts = [
        'specifications' => 'array',
        'status' => 'boolean',
        'additional_cost' => 'decimal:2',
        'additional_price' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}
