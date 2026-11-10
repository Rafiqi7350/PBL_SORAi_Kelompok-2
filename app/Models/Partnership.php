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

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'umkm_id',
        'supplier_id',
        'submission_details',
        'status',
        'term_of_payment_days',
        'credit_limit',
    ];

    protected function casts(): array
    {
        return [
            'term_of_payment_days' => 'integer',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function reliabilityScores(): HasMany
    {
        return $this->hasMany(UmkmReliabilityScore::class, 'partnership_id');
    }

    public function latestReliabilityScore(): HasOne
    {
        return $this->hasOne(UmkmReliabilityScore::class, 'partnership_id')->latest('calculated_at');
    }

    public function supplierPerformanceScores(): HasMany
    {
        return $this->hasMany(SupplierPerformanceScore::class, 'partnership_id');
    }

    public function latestSupplierPerformanceScore(): HasOne
    {
        return $this->hasOne(SupplierPerformanceScore::class, 'partnership_id')->latest('calculated_at');
    }

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

    public function evaluations(): HasMany
    {
        return $this->hasMany(PartnershipEvaluation::class, 'partnership_id')->latest('created_at');
    }

    public function latestEvaluation(): HasOne
    {
        return $this->hasOne(PartnershipEvaluation::class, 'partnership_id')->latest('created_at');
    }

    public function getContractCodeAttribute(): string
    {
        return 'CTR-'.strtoupper(substr((string) $this->id, 0, 8));
    }

    public function isActiveStatus(): bool
    {
        return in_array(strtolower((string) $this->status), ['active', 'approved', 'accepted', 'disetujui'], true);
    }

    public function isPausedStatus(): bool
    {
        return in_array(strtolower((string) $this->status), ['paused', 'suspended', 'ditangguhkan'], true);
    }

    public function isTerminatedStatus(): bool
    {
        return in_array(strtolower((string) $this->status), ['terminated', 'ended', 'completed', 'berakhir', 'dihentikan'], true);
    }
}
