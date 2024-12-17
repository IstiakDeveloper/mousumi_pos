<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'customer_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid',
        'due',
        'payment_status',
        'note',
        'created_by'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class); // Adjust the relationship type and model as necessary
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
