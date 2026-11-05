<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Menangani callback notifikasi dari Midtrans (Webhook HTTP POST)
     */
    public function handleNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Webhook Received:', [
            'order_id' => $payload['order_id'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
        ]);

        try {
            $result = $this->midtransService->handleNotification($payload);

            return response()->json([
                'success' => true,
                'message' => 'Notification processed successfully.',
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            Log::error('Midtrans Webhook Error: '.$e->getMessage(), ['payload' => $payload]);

            $statusCode = str_contains($e->getMessage(), 'signature') ? 403 : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
