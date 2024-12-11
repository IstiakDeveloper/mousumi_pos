<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'credit_limit',
        'balance',
        'points',
        'status'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'points' => 'integer',
        'status' => 'boolean'
    ];

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', true);
    }

    // Relationships
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
