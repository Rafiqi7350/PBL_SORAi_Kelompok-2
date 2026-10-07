<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUmkmRequest;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UmkmRegisterController extends Controller
{
    /**
     * Show the UMKM registration form.
     */
    public function create(): View
    {
        return view('Auth.form-umkm');
    }

    /**
     * Handle UMKM registration request.
     */
    public function store(RegisterUmkmRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'umkm',
                'status' => 'active',
            ]);

            UmkmProfile::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'owner_name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);
        });

        return redirect()->route('register.umkm')
            ->with('success', 'Pendaftaran UMKM Kuliner berhasil diselesaikan! Akun Anda telah aktif.');
    }
}
