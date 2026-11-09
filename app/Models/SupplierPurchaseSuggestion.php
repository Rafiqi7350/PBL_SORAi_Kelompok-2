<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseSuggestion extends Model
{
    use HasFactory, HasUuids;

    public const URGENCY_CRITICAL = 'CRITICAL';

    public const URGENCY_WARNING = 'WARNING';

    public const URGENCY_NORMAL = 'NORMAL';

    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public const STATUS_ORDERED = 'ORDERED';

    public const STATUS_IGNORED = 'IGNORED';

    protected $fillable = [
        'supplier_id',
        'commodity_id',
        'material_source_id',
        'suggested_order_date',
        'target_arrival_date',
        'suggested_quantity',
        'estimated_cost',
        'urgency_level',
        'status',
        'po_reference_number',
    ];

    protected function casts(): array
    {
        return [
            'suggested_order_date' => 'date',
            'target_arrival_date' => 'date',
            'suggested_quantity' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
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

    public function materialSource(): BelongsTo
    {
        return $this->belongsTo(SupplierMaterialSource::class, 'material_source_id');
    }

    public function getUrgencyBadgeColorAttribute(): string
    {
        return match ($this->urgency_level) {
            self::URGENCY_CRITICAL => 'bg-rose-100 text-rose-800 border-rose-200',
            self::URGENCY_WARNING => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-blue-100 text-blue-800 border-blue-200',
        };
    }
}
