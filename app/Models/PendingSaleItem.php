<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingSaleItem extends Model
{
    protected $fillable = [
        'pending_sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function pendingSale()
    {
        return $this->belongsTo(PendingSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

