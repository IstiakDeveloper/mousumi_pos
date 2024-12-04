<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Get the products for the unit.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope active units
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
