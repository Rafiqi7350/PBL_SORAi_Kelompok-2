<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_PARTIALLY_SHIPPED = 'partially_shipped';

    public const STATUS_WAITING_BACKORDER_APPROVAL = 'waiting_backorder_approval';

    public const STATUS_BACKORDER_PENDING = 'backorder_pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_PARTIAL = 'completed_partial';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'partnership_id',
        'order_date',
        'delivery_date',
        'total_amount',
        'order_status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipmentConfirmation(): HasOne
    {
        return $this->hasOne(ShipmentConfirmation::class)->latest('created_at');
    }

    public function shipmentConfirmations(): HasMany
    {
        return $this->hasMany(ShipmentConfirmation::class)->orderBy('shipment_batch', 'asc');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latest('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('shipment_batch', 'asc');
    }

    public function hasBackorder(): bool
    {
        if ($this->order_status === self::STATUS_BACKORDER_PENDING) {
            return true;
        }

        if ($this->relationLoaded('items') ? $this->items->sum('quantity_remaining') > 0 : $this->items()->sum('quantity_remaining') > 0) {
            return true;
        }

        if ($this->relationLoaded('shipmentConfirmations')) {
            return $this->shipmentConfirmations->contains('backorder_action', 'create_backorder');
        }

        return $this->shipmentConfirmations()->where('backorder_action', 'create_backorder')->exists();
    }

    public function isNoBackorder(): bool
    {
        if ($this->order_status === self::STATUS_COMPLETED_PARTIAL) {
            return true;
        }

        if ($this->relationLoaded('shipmentConfirmations')) {
            return $this->shipmentConfirmations->contains('backorder_action', 'no_backorder');
        }

        return $this->shipmentConfirmations()->where('backorder_action', 'no_backorder')->exists();
    }

    public function getTotalOrderedQuantityAttribute(): float
    {
        return (float) $this->items->sum('quantity');
    }

    public function getTotalDeliveredQuantityAttribute(): float
    {
        return (float) $this->items->sum('quantity_delivered');
    }

    public function getTotalRemainingQuantityAttribute(): float
    {
        return (float) $this->items->sum('quantity_remaining');
    }
}
