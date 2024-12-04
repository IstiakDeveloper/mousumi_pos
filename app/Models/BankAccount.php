<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'opening_balance',
        'current_balance',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2'
    ];

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
