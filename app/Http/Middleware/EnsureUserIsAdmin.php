<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // MOCK LOGIN FOR DEMO PURPOSES: 
        // Automatically login the seeded Admin user if nobody is logged in
        if (!Auth::check()) {
            $adminUser = User::where('role', 'admin')->first();
            if ($adminUser) {
                Auth::login($adminUser);
            }
        }

        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('umkm.katalog.index')
                ->with('error', 'Akses ditolak. Halaman ini hanya dapat diakses oleh Administrator.');
        }

        return $next($request);
    }
}
