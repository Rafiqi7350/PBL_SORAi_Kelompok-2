<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'supplier_profiles';

    protected $fillable = [
        'user_id',
        'commodity_id',
        'company_name',
        'owner_name',
        'phone',
        'warehouse_address',
        'max_capacity',
        'delivery_area',
        'nib_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(CommodityCategory::class, 'commodity_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommodityCategory::class, 'commodity_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'supplier_id');
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class, 'supplier_id');
    }

    public function supplyPlans(): HasManyThrough
    {
        return $this->hasManyThrough(SupplyPlan::class, Partnership::class, 'supplier_id', 'partnership_id');
    }

    public function orderReviews(): HasMany
    {
        return $this->hasMany(OrderReview::class, 'supplier_id');
    }

    public function performanceScores(): HasMany
    {
        return $this->hasMany(SupplierPerformanceScore::class, 'supplier_id');
    }

    public function latestPerformanceScore(): HasOne
    {
        return $this->hasOne(SupplierPerformanceScore::class, 'supplier_id')->latest('calculated_at');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(SupplierInventoryItem::class, 'supplier_id');
    }

    public function materialSources(): HasMany
    {
        return $this->hasMany(SupplierMaterialSource::class, 'supplier_id');
    }

    public function forecastRuns(): HasMany
    {
        return $this->hasMany(DemandForecastRun::class, 'supplier_id');
    }

    public function purchaseSuggestions(): HasMany
    {
        return $this->hasMany(SupplierPurchaseSuggestion::class, 'supplier_id');
    }

    public function whatIfSimulations(): HasMany
    {
        return $this->hasMany(WhatIfSimulation::class, 'supplier_id');
    }
}
