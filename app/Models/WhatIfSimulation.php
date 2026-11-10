<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatIfSimulation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'scenario_name',
        'demand_shock_pct',
        'lead_time_delay_days',
        'results_summary',
    ];

    protected function casts(): array
    {
        return [
            'demand_shock_pct' => 'decimal:2',
            'lead_time_delay_days' => 'integer',
            'results_summary' => 'array',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }
}
