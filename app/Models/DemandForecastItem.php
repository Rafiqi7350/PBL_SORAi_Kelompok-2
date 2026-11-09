<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandForecastItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'forecast_run_id',
        'commodity_id',
        'forecast_date',
        'baseline_demand',
        'upper_bound_95',
        'lower_bound_95',
        'contract_demand',
        'collaborative_demand',
    ];

    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'baseline_demand' => 'decimal:2',
            'upper_bound_95' => 'decimal:2',
            'lower_bound_95' => 'decimal:2',
            'contract_demand' => 'decimal:2',
            'collaborative_demand' => 'decimal:2',
        ];
    }

    public function forecastRun(): BelongsTo
    {
        return $this->belongsTo(DemandForecastRun::class, 'forecast_run_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }
}
