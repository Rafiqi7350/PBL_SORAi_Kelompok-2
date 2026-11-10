<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipEvaluation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'partnership_evaluations';

    protected $fillable = [
        'partnership_id',
        'evaluation_period',
        'target_volume',
        'realized_volume',
        'fulfillment_rate_percentage',
        'discrepancy_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_volume' => 'decimal:2',
            'realized_volume' => 'decimal:2',
            'fulfillment_rate_percentage' => 'decimal:2',
        ];
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class, 'partnership_id');
    }
}
