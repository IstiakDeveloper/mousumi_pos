<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

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
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'status' => 'boolean'
    ];

    /**
     * Scope a query to only include active bank accounts.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', true);
    }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function updateBalance($amount, $type = 'deposit')
    {
        $this->current_balance = $type === 'deposit'
            ? $this->current_balance + $amount
            : $this->current_balance - $amount;
        $this->save();
    }
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
