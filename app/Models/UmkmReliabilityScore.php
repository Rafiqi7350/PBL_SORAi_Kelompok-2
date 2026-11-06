<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmReliabilityScore extends Model
{
    use HasFactory, HasUuids;

    public const GRADE_A = 'A';

    public const GRADE_B = 'B';

    public const GRADE_C = 'C';

    public const GRADE_D = 'D';

    public const GRADE_F = 'F';

    public const GRADE_UNRATED = 'UNRATED';

    protected $fillable = [
        'umkm_id',
        'partnership_id',
        'final_score',
        'grade',
        'payment_discipline_score',
        'order_consistency_score',
        'tenure_history_score',
        'metrics_breakdown',
        'recommendations',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'payment_discipline_score' => 'decimal:2',
            'order_consistency_score' => 'decimal:2',
            'tenure_history_score' => 'decimal:2',
            'metrics_breakdown' => 'array',
            'recommendations' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class, 'umkm_id');
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class, 'partnership_id');
    }

    public function isEligibleForTop(): bool
    {
        return in_array($this->grade, [self::GRADE_A, self::GRADE_B, self::GRADE_C]);
    }

    public function getGradeBadgeColor(): string
    {
        return match ($this->grade) {
            self::GRADE_A => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::GRADE_B => 'bg-blue-50 text-blue-700 border-blue-200',
            self::GRADE_C => 'bg-amber-50 text-amber-700 border-amber-200',
            self::GRADE_D => 'bg-orange-50 text-orange-700 border-orange-200',
            self::GRADE_F => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    }

    public function getGradeLabel(): string
    {
        return match ($this->grade) {
            self::GRADE_A => 'Sangat Andal (Tier 1 Prime)',
            self::GRADE_B => 'Andal (Reliable Partner)',
            self::GRADE_C => 'Moderat (Perlu Pemantauan)',
            self::GRADE_D => 'Risiko Tinggi (Pembayaran Tunai)',
            self::GRADE_F => 'Kritis (Penalti / Gagal Bayar)',
            default => 'Belum Terperingkat (Mitra Baru)',
        };
    }

    public function getRecommendedTopDays(): int
    {
        return (int) ($this->recommendations['max_top_days'] ?? 0);
    }
}
