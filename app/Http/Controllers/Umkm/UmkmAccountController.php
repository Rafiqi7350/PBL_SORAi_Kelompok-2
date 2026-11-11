<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAccountPasswordRequest;
use App\Http\Requests\Auth\UpdateProfilePhotoRequest;
use App\Http\Requests\Umkm\UpdateUmkmAccountRequest;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UmkmAccountController extends Controller
{
    /**
     * Tampilkan halaman kelola akun dan profil UMKM.
     */
    public function edit(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('umkmProfile');

        return view('umkm.account.edit', [
            'user' => $user,
            'profile' => $user->umkmProfile,
        ]);
    }

    /**
     * Perbarui informasi akun (email) dan profil usaha UMKM.
     */
    public function updateProfile(UpdateUmkmAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'email' => $validated['email'],
            ]);

            UmkmProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $validated['business_name'],
                    'owner_name' => $validated['owner_name'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                ]
            );
        });

        return redirect()
            ->route('umkm.account.edit')
            ->with('success', 'Data akun & profil UMKM berhasil diperbarui.');
    }

    /**
     * Perbarui kata sandi akun UMKM.
     */
    public function updatePassword(UpdateAccountPasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validated();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route('umkm.account.edit')
            ->with('success', 'Kata sandi akun berhasil diperbarui.');
    }

    /**
     * Unggah atau ganti foto profil akun UMKM.
     */
    public function updatePhoto(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $oldPath = $user->profile_photo_path;
        $newPath = $request->file('photo')->store('profile-photos', 'public');

        if (! is_string($newPath) || $newPath === '') {
            return redirect()
                ->route('umkm.account.edit')
                ->with('error', 'Gagal menyimpan file foto profil.');
        }

        DB::transaction(function () use ($user, $newPath): void {
            $user->profile_photo_path = $newPath;
            $user->save();
        });

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('umkm.account.edit')
            ->with('success', 'Foto profil akun berhasil diperbarui.');
    }

    /**
     * Hapus foto profil akun UMKM yang tersimpan.
     */
    public function destroyPhoto(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $oldPath = $user->profile_photo_path;

        $user->profile_photo_path = null;
        $user->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('umkm.account.edit')
            ->with('success', 'Foto profil akun berhasil dihapus.');
    }
}
