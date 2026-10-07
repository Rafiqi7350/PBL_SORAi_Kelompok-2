<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingRegistrationController extends Controller
{
    /**
     * Show the pending registration loading / waiting status page.
     */
    public function index(Request $request): View
    {
        $email = $request->query('email') ?? session('registered_email');
        $user = null;

        if ($email) {
            $user = User::with(['umkmProfile', 'supplierProfile'])
                ->where('email', $email)
                ->first();
        }

        $businessName = null;
        if ($user) {
            $businessName = $user->role === 'supplier'
                ? ($user->supplierProfile?->company_name ?? 'Pemasok Lokal')
                : ($user->umkmProfile?->business_name ?? 'Mitra UMKM');
        }

        return view('Auth.pending', [
            'email' => $email,
            'user' => $user,
            'businessName' => $businessName,
            'status' => $user?->status ?? 'pending',
            'role' => $user?->role,
        ]);
    }

    /**
     * API to check live verification status for the pending page.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $email = $request->query('email');

        if (! $email) {
            return response()->json([
                'exists' => false,
                'status' => 'unknown',
                'is_active' => false,
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'exists' => false,
                'status' => 'not_found',
                'is_active' => false,
            ], 404);
        }

        $isActive = $user->status === 'active';

        return response()->json([
            'exists' => true,
            'status' => $user->status,
            'is_active' => $isActive,
            'role' => $user->role,
            'login_url' => route('login', ['email' => $user->email]),
        ]);
    }
}
