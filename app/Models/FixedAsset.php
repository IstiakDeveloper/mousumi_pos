<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'status',
        'description',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FixedAssetItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('status', 'active');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
