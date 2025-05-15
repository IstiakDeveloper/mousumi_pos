<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'reference_type',
        'reference_id',
        'quantity',
        'before_quantity',
        'after_quantity',
        'type',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'before_quantity' => 'decimal:6',
        'after_quantity' => 'decimal:6'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic relation for reference
    public function reference()
    {
        return $this->morphTo();
    }
}
