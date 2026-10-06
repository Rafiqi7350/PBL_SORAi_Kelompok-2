<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commodity extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'commodities';

    protected $fillable = [
        'name',
        'category',
        'unit',
        'government_ref_price',
        'description',
    ];

    protected $casts = [
        'government_ref_price' => 'decimal:2',
    ];

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'commodity_id');
    }

    public function supplierProfiles(): HasMany
    {
        return $this->hasMany(SupplierProfile::class, 'commodity_id');
    }
}
