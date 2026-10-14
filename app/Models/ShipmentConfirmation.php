<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentConfirmation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
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
}
