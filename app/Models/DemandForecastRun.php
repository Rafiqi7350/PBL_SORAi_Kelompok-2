<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandForecastRun extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'forecast_horizon',
        'model_used',
        'model_accuracy_mape',
        'parameters',
        'run_at',
    ];

    protected function casts(): array
    {
        return [
            'model_accuracy_mape' => 'decimal:2',
            'parameters' => 'array',
            'run_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DemandForecastItem::class, 'forecast_run_id');
    }
}
