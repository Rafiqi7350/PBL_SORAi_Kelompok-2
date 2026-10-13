<?php

namespace App\Models;

use Database\Factories\PartnershipDeliveryScheduleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipDeliverySchedule extends Model
{
    /** @use HasFactory<PartnershipDeliveryScheduleFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'partnership_id',
        'supplier_product_id',
        'volume_per_delivery',
        'total_contract_volume',
        'unit',
        'frequency_type',
        'delivery_days',
        'delivery_time',
        'is_auto_order_active',
        'start_date',
        'end_date',
        'special_instructions',
    ];

    protected $casts = [
        'delivery_days' => 'array',
        'is_auto_order_active' => 'boolean',
        'volume_per_delivery' => 'decimal:2',
        'total_contract_volume' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class, 'partnership_id');
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class, 'supplier_product_id');
    }
}
