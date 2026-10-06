<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommodityCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'commodity_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];
}
