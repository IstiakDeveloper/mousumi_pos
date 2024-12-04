<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'status' => 'boolean',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2'
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }


}
