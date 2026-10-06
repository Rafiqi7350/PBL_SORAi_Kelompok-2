<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSupplierRequest;
use App\Models\Commodity;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SupplierRegisterController extends Controller
{
    /**
     * Show the supplier registration form.
     */
    public function create(): View
    {
        $commodities = Commodity::orderBy('name')->get();

        return view('auth.form-supplier', compact('commodities'));
    }

    /**
     * Handle supplier registration request.
     */
    public function store(RegisterSupplierRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'supplier',
                'status' => 'active',
            ]);

            SupplierProfile::create([
                'user_id' => $user->id,
                'commodity_id' => $validated['commodity_id'],
                'company_name' => $validated['company_name'],
                'owner_name' => $validated['owner_name'] ?? $validated['company_name'],
                'phone' => $validated['phone'],
                'warehouse_address' => $validated['warehouse_address'],
                'max_capacity' => $validated['max_capacity'],
                'delivery_area' => $validated['delivery_area'],
                'nib_number' => $validated['nib_number'],
            ]);
        });

        return redirect()->route('register.supplier')
            ->with('success', 'Pendaftaran pemasok berhasil diselesaikan! Akun Anda telah aktif.');
    }
}
