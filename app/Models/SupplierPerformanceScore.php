<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceScore extends Model
{
    use HasFactory, HasUuids;

    public const GRADE_A = 'A';

    public const GRADE_B = 'B';

    public const GRADE_C = 'C';

    public const GRADE_D = 'D';

    public const GRADE_UNRATED = 'UNRATED';

    protected $fillable = [
        'supplier_id',
        'partnership_id',
        'final_score',
        'star_rating',
        'grade',
        'on_time_delivery_score',
        'quota_fulfillment_score',
        'quality_rating_score',
        'metrics_breakdown',
        'recommendations',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'star_rating' => 'decimal:1',
            'on_time_delivery_score' => 'decimal:2',
            'quota_fulfillment_score' => 'decimal:2',
            'quality_rating_score' => 'decimal:2',
            'metrics_breakdown' => 'array',
            'recommendations' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class, 'partnership_id');
    }

    public function getGradeBadgeColor(): string
    {
        return match ($this->grade) {
            self::GRADE_A => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::GRADE_B => 'bg-blue-50 text-blue-700 border-blue-200',
            self::GRADE_C => 'bg-amber-50 text-amber-700 border-amber-200',
            self::GRADE_D => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    }

    public function getGradeLabel(): string
    {
        return match ($this->grade) {
            self::GRADE_A => 'Top Vendor (Sangat Andal & Tepat Waktu)',
            self::GRADE_B => 'Reliable Vendor (Standar Baik)',
            self::GRADE_C => 'Perlu Pemantauan (Sering Terlambat/Backorder)',
            self::GRADE_D => 'Risiko Logistik Tinggi (Pelanggaran SLA)',
            default => 'Vendor Baru (Belum Terperingkat)',
        };
    }

    public function isRecommendedForRenewal(): bool
    {
        return in_array($this->grade, [self::GRADE_A, self::GRADE_B]);
    }
}
