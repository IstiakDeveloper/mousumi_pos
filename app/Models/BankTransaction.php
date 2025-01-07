<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_account_id',
        'transaction_type',
        'amount',
        'description',
        'date',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date'
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
