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
        'rejection_reason',
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

    /**
     * Mengambil daftar aturan/ketentuan mutu dari rincian pengajuan.
     *
     * @return array<int, string>
     */
    public function getQualityTermsAttribute(): array
    {
        if (empty($this->submission_details)) {
            return [];
        }

        if (preg_match('/\[Ketentuan Mutu:\s*([^\]]+)\]/i', (string) $this->submission_details, $matches)) {
            return array_values(array_filter(array_map('trim', explode('|', $matches[1]))));
        }

        if (str_starts_with(trim((string) $this->submission_details), '{')) {
            $decoded = json_decode((string) $this->submission_details, true);
            if (is_array($decoded) && isset($decoded['terms']) && is_array($decoded['terms'])) {
                return array_values(array_filter(array_map('trim', $decoded['terms'])));
            }
        }

        return [];
    }

    /**
     * Mengambil catatan khusus yang telah dibersihkan dari tag ketentuan mutu.
     */
    public function getCleanNotesAttribute(): string
    {
        if (empty($this->submission_details)) {
            return '';
        }

        if (str_starts_with(trim((string) $this->submission_details), '{')) {
            $decoded = json_decode((string) $this->submission_details, true);
            if (is_array($decoded) && isset($decoded['notes'])) {
                return trim((string) $decoded['notes']);
            }
        }

        $cleaned = preg_replace('/\[Ketentuan Mutu:\s*[^\]]+\]\s*/i', '', (string) $this->submission_details);
        $cleaned = preg_replace('/\[Alasan Penolakan:\s*[^\]]+\]\s*/i', '', (string) $cleaned);

        return trim((string) $cleaned);
    }

    /**
     * Mengambil alasan penolakan kemitraan jika ditolak oleh pemasok.
     */
    public function getRejectionReasonAttribute(): ?string
    {
        if (! empty($this->attributes['rejection_reason'])) {
            return $this->attributes['rejection_reason'];
        }

        if (preg_match('/\[Alasan Penolakan:\s*([^\]]+)\]/i', (string) $this->submission_details, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
