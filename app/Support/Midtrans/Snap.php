<?php

namespace Midtrans;

use Exception;
use Illuminate\Support\Facades\Http;

class Snap
{
    /**
     * Dapatkan Snap Token dari API Midtrans
     *
     * @param  array<string, mixed>  $params
     *
     * @throws Exception
     */
    public static function getSnapToken(array $params): string
    {
        $url = Config::getBaseUrl().'/snap/v1/transactions';

        $response = Http::withBasicAuth(Config::$serverKey, '')
            ->acceptJson()
            ->post($url, $params);

        if ($response->successful()) {
            return (string) ($response->json('token') ?? '');
        }

        throw new Exception('Midtrans Snap Error: '.$response->body());
    }
}
