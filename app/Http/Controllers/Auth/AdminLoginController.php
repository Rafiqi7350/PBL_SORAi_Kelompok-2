<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.commodities.index');
        }

        return view('admin.auth.login', [
            'activeUser' => Auth::user(),
        ]);
    }

    /**
     * Handle incoming admin authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // If already logged in, logout current session first to avoid state conflicts
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $userExists = User::where('email', $request->email)->exists();

            $errorMessage = $userExists
                ? 'Kata sandi administrator yang Anda masukkan salah. Mohon periksa kembali.'
                : 'Email administrator belum terdaftar di sistem SORAi.';

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $errorMessage,
                ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Ensure user is an administrator
        if ($user->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Akses ditolak. Akun ini tidak memiliki hak akses sebagai Administrator SORAi.',
                ]);
        }

        // Check if account status is active
        if ($user->status && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Akun Administrator Anda saat ini ditangguhkan atau tidak aktif.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.commodities.index'))
            ->with('success', 'Selamat datang di Control Panel Administrator SORAi!');
    }

    /**
     * Destroy admin authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Anda telah berhasil keluar dari Control Panel Administrator.');
    }
}
