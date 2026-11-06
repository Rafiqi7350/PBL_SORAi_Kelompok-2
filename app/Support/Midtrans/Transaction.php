<?php

namespace Midtrans;

use Exception;
use Illuminate\Support\Facades\Http;

class Transaction
{
    /**
     * Dapatkan status transaksi dari API Midtrans
     *
     * @throws Exception
     */
    public static function status(string $id): object
    {
        $url = Config::getApiBaseUrl().'/v2/'.$id.'/status';

        $response = Http::withBasicAuth(Config::$serverKey, '')
            ->acceptJson()
            ->get($url);

        if ($response->successful()) {
            return (object) $response->json();
        }

        throw new Exception('Midtrans Transaction Status Error: '.$response->body());
    }
}
