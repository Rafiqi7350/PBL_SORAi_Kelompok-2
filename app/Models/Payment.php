<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'shipment_batch',
        'invoice_number',
        'shipment_confirmation_id',
        'midtrans_transaction_id',
        'payment_method',
        'amount',
        'quantity_billed',
        'payment_status',
        'notes',
        'transaction_time',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'shipment_batch' => 'integer',
            'amount' => 'decimal:2',
            'quantity_billed' => 'decimal:2',
            'transaction_time' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipmentConfirmation(): BelongsTo
    {
        return $this->belongsTo(ShipmentConfirmation::class);
    }

    public function isPaid(): bool
    {
        return in_array(strtoupper((string) $this->payment_status), ['PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS']);
    }
}
