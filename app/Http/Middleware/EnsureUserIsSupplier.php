<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSupplier
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu.');
        }

        if (Auth::user()->role !== 'supplier') {
            return abort(403, 'Akses Ditolak: Halaman ini khusus untuk Pemasok. Anda saat ini masuk dengan peran '.strtoupper(Auth::user()->role).'.');
        }

        return $next($request);
    }
}
