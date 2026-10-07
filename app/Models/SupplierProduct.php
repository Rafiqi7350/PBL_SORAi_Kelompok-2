<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'commodity_id',
        'price',
        'stock_capacity',
        'stock_status',
        'image_url',
        'description',
        'minimum_order',
        'is_halal_certified',
        'quality_grade',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_halal_certified' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    public function getEffectiveUnitAttribute(): string
    {
        return $this->commodity?->unit ?: 'kg';
    }

    public function scopeTersedia($query)
    {
        return $query->where('stock_status', 'Tersedia');
    }

    public function scopeHalal($query)
    {
        return $query->where('is_halal_certified', true);
    }
}
