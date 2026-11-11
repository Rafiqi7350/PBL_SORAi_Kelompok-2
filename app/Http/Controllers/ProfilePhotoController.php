<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    /**
     * Sajikan file foto profil pengguna yang tersimpan pada disk public.
     */
    public function show(User $user): StreamedResponse
    {
        if (empty($user->profile_photo_path) || ! Storage::disk('public')->exists($user->profile_photo_path)) {
            abort(404);
        }

        return Storage::disk('public')->response(
            $user->profile_photo_path,
            null,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }
}
