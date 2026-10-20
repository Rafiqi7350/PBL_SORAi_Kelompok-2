<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShipmentConfirmation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'shipment_batch',
        'backorder_action',
        'shipped_quantity',
        'backorder_estimated_delivery_date',
        'backorder_notes',
        'backorder_approval_status',
        'backorder_rejection_reason',
        'backorder_approved_at',
        'backorder_rejected_at',
        'shipped_at',
        'received_at',
        'fulfillment_status',
        'tracking_notes',
        'proof_photo_url',
        'confirmation_notes',
        'confirmed_by_umkm_id',
    ];

    protected function casts(): array
    {
        return [
            'shipment_batch' => 'integer',
            'shipped_quantity' => 'decimal:2',
            'backorder_estimated_delivery_date' => 'date',
            'backorder_approved_at' => 'datetime',
            'backorder_rejected_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedByUmkm(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class, 'confirmed_by_umkm_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'shipment_confirmation_id');
    }
}
