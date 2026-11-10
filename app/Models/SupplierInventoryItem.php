<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInventoryItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'commodity_id',
        'current_stock',
        'reserved_stock',
        'safety_stock',
        'reorder_point',
        'unit',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'reserved_stock' => 'decimal:2',
            'safety_stock' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'last_restocked_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    /**
     * Hitung stok tersedia yang belum dipesan untuk kontrak aktif.
     */
    public function getAvailableStockAttribute(): float
    {
        return max(0, (float) $this->current_stock - (float) $this->reserved_stock);
    }

    /**
     * Status Early Warning System (EWS) inventori:
     * - CRITICAL: current_stock < safety_stock (Kritis, risiko stockout tinggi)
     * - WARNING: current_stock <= reorder_point (Siaga, saatnya pesan hulu)
     * - OPTIMAL: current_stock > reorder_point (Aman)
     */
    public function getEwsStatusAttribute(): string
    {
        $stock = (float) $this->current_stock;
        $ss = (float) $this->safety_stock;
        $rop = (float) $this->reorder_point;

        if ($stock < $ss) {
            return 'CRITICAL';
        }

        if ($stock <= $rop) {
            return 'WARNING';
        }

        return 'OPTIMAL';
    }

    public function getEwsBadgeColorAttribute(): string
    {
        return match ($this->ews_status) {
            'CRITICAL' => 'bg-rose-100 text-rose-800 border-rose-200',
            'WARNING' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        };
    }

    public function getEwsLabelAttribute(): string
    {
        return match ($this->ews_status) {
            'CRITICAL' => 'Stok Kritis (< SS)',
            'WARNING' => 'Siaga Reorder (≤ ROP)',
            default => 'Stok Optimal (Aman)',
        };
    }
}
