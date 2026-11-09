<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierMaterialSource extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'commodity_id',
        'producer_name',
        'contact_phone',
        'lead_time_days',
        'minimum_order_qty',
        'unit_cost_price',
        'is_primary_source',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'minimum_order_qty' => 'decimal:2',
            'unit_cost_price' => 'decimal:2',
            'is_primary_source' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    public function purchaseSuggestions(): HasMany
    {
        return $this->hasMany(SupplierPurchaseSuggestion::class, 'material_source_id');
    }
}
