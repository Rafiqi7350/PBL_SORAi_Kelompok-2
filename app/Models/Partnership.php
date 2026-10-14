<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partnership extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'umkm_id',
        'supplier_id',
        'commodity_id',
        'submission_details',
        'delivery_schedule_notes',
        'duration',
        'status',
        'rejection_reason',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class, 'umkm_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * delivery_schedule_notes disimpan UMKM dengan format "100 kg | Frekuensi: 2x Seminggu".
     * Dua accessor ini memecahnya kembali untuk ditampilkan sebagai Volume dan Frekuensi.
     */
    public function getVolumeLabelAttribute(): string
    {
        return trim(explode('|', (string) $this->delivery_schedule_notes)[0]) ?: '-';
    }

    public function getFrequencyLabelAttribute(): string
    {
        $parts = explode('|', (string) $this->delivery_schedule_notes, 2);

        if (! isset($parts[1])) {
            return '-';
        }

        return trim(preg_replace('/^\s*Frekuensi:\s*/i', '', $parts[1])) ?: '-';
    }
}