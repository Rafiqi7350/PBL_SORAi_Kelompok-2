<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'supply_plans';

    protected $fillable = [
        'partnership_id',
        'commodity_name',
        'period_month_year',
        'estimated_quantity',
        'unit',
        'supplier_confirmation_status',
        'confirmed_quantity',
        'notes',
        'supplier_notes',
    ];

    protected $casts = [
        'estimated_quantity' => 'decimal:2',
        'confirmed_quantity' => 'decimal:2',
    ];

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class, 'partnership_id');
    }
}
