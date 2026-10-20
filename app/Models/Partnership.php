<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Partnership extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'umkm_id',
        'supplier_id',
        'submission_details',
        'status',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class, 'umkm_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(PartnershipDeliverySchedule::class, 'partnership_id');
    }

    public function activeDeliverySchedule(): HasOne
    {
        return $this->hasOne(PartnershipDeliverySchedule::class, 'partnership_id')->latest('created_at');
    }

    public function supplyPlans(): HasMany
    {
        return $this->hasMany(SupplyPlan::class, 'partnership_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'partnership_id');
    }
}
