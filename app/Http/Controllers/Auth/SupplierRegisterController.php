<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSupplierRequest;
use App\Models\CommodityCategory;
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
        $categories = CommodityCategory::orderBy('name')->get();

        return view('auth.form-supplier', compact('categories'));
    }

    /**
     * Handle supplier registration request.
     */
    public function store(RegisterSupplierRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $newUser = null;

        DB::transaction(function () use ($validated, &$newUser) {
            $newUser = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'supplier',
                'status' => 'pending',
            ]);

            SupplierProfile::create([
                'user_id' => $newUser->id,
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

        return redirect()->route('register.pending', ['email' => $validated['email']])
            ->with('registered_email', $validated['email'])
            ->with('success', 'Pendaftaran Pemasok berhasil diajukan! Akun Anda sedang dalam proses verifikasi oleh Administrator sebelum dapat diakses.');
    }
}
