<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     * If user is already logged in, still pass active user information so they can switch accounts easily.
     */
    public function create(): View
    {
        return view('Auth.login', [
            'activeUser' => Auth::user(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * If a session is already active, log it out first to allow seamless switching to another account.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // If already logged in, logout current session first
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            $userExists = User::where('email', $request->email)->exists();

            $errorMessage = $userExists
                ? 'Kata sandi yang Anda masukkan salah. Mohon periksa kembali.'
                : 'Email belum terdaftar. Silakan lakukan registrasi terlebih dahulu.';

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $errorMessage,
                ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->status && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda saat ini tidak aktif atau ditangguhkan. Silakan hubungi admin.',
            ]);
        }

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda telah berhasil keluar dari akun.');
    }

    /**
     * Redirect authenticated user based on their role.
     */
    public function redirectBasedOnRole(User $user): RedirectResponse
    {
        if ($user->role === 'umkm') {
            $displayName = $user->umkmProfile?->owner_name ?? $user->umkmProfile?->business_name ?? 'Mitra UMKM';
            return redirect()->intended(route('umkm.katalog.index'))
                ->with('success', "Selamat datang kembali, {$displayName}!");
        }

        if ($user->role === 'supplier') {
            $displayName = $user->supplierProfile?->company_name ?? 'Mitra Pemasok';
            return redirect()->intended(route('supplier.katalog.index'))
                ->with('success', "Selamat datang kembali, {$displayName}!");
        }

        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.commodities.index'))
                ->with('success', 'Selamat datang di Panel Admin SORAi!');
        }

        return redirect()->intended('/');
    }
}
