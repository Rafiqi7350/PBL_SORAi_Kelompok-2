<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UmkmProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'business_name',
        'owner_name',
        'phone',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class, 'umkm_id');
    }

    public function supplyPlans(): HasManyThrough
    {
        return $this->hasManyThrough(SupplyPlan::class, Partnership::class, 'umkm_id', 'partnership_id');
    }

    public function orderReviews(): HasMany
    {
        return $this->hasMany(OrderReview::class, 'umkm_id');
    }

    public function reliabilityScores(): HasMany
    {
        return $this->hasMany(UmkmReliabilityScore::class, 'umkm_id');
    }

    public function latestReliabilityScore(): HasOne
    {
        return $this->hasOne(UmkmReliabilityScore::class, 'umkm_id')->latest('calculated_at');
    }
}
