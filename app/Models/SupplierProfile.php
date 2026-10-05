<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'supplier_id');
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class, 'supplier_id');
    }
}
