<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo_path)) {
            return null;
        }

        if (str_starts_with($this->profile_photo_path, 'http://') || str_starts_with($this->profile_photo_path, 'https://')) {
            return $this->profile_photo_path;
        }

        if (! Storage::disk('public')->exists($this->profile_photo_path)) {
            return null;
        }

        return route('account.photo.show', [
            'user' => $this->id,
            'v' => $this->updated_at?->timestamp ?? 0,
        ]);
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(UmkmProfile::class, 'user_id');
    }

    public function supplierProfile(): HasOne
    {
        return $this->hasOne(SupplierProfile::class, 'user_id');
    }
}
