<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAccountPasswordRequest;
use App\Http\Requests\Auth\UpdateProfilePhotoRequest;
use App\Http\Requests\Supplier\UpdateSupplierAccountRequest;
use App\Models\CommodityCategory;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupplierAccountController extends Controller
{
    /**
     * Tampilkan halaman kelola akun dan profil pemasok.
     */
    public function edit(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('supplierProfile.commodity');

        $categories = CommodityCategory::orderBy('name')->get();

        return view('supplier.account.edit', [
            'user' => $user,
            'profile' => $user->supplierProfile,
            'categories' => $categories,
        ]);
    }

    /**
     * Perbarui informasi akun (email) dan profil usaha pemasok.
     */
    public function updateProfile(UpdateSupplierAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validated();
        $existingProfile = $user->supplierProfile;

        DB::transaction(function () use ($user, $validated, $existingProfile): void {
            $user->update([
                'email' => $validated['email'],
            ]);

            $resolvedOwnerName = filled($validated['owner_name'] ?? null)
                ? $validated['owner_name']
                : ($existingProfile?->owner_name ?: $validated['company_name']);

            SupplierProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $validated['company_name'],
                    'owner_name' => $resolvedOwnerName,
                    'phone' => $validated['phone'],
                    'commodity_id' => array_key_exists('commodity_id', $validated)
                        ? $validated['commodity_id']
                        : $existingProfile?->commodity_id,
                    'max_capacity' => $validated['max_capacity'],
                    'delivery_area' => $validated['delivery_area'],
                    'nib_number' => $validated['nib_number'] ?? null,
                    'warehouse_address' => $validated['warehouse_address'],
                ]
            );
        });

        return redirect()
            ->route('supplier.account.edit')
            ->with('success', 'Data akun & profil Pemasok berhasil diperbarui.');
    }

    /**
     * Perbarui kata sandi akun pemasok.
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
            ->route('supplier.account.edit')
            ->with('success', 'Kata sandi akun berhasil diperbarui.');
    }

    /**
     * Unggah atau ganti foto profil akun pemasok.
     */
    public function updatePhoto(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $oldPath = $user->profile_photo_path;
        $newPath = $request->file('photo')->store('profile-photos', 'public');

        if (! is_string($newPath) || $newPath === '') {
            return redirect()
                ->route('supplier.account.edit')
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
            ->route('supplier.account.edit')
            ->with('success', 'Foto profil akun berhasil diperbarui.');
    }

    /**
     * Hapus foto profil akun pemasok yang tersimpan.
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
            ->route('supplier.account.edit')
            ->with('success', 'Foto profil akun berhasil dihapus.');
    }
}
