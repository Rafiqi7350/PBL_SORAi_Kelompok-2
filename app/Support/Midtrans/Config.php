<?php

namespace Midtrans;

class Config
{
    public static string $serverKey = '';

    public static string $clientKey = '';

    public static bool $isProduction = false;

    public static bool $is3ds = false;

    public static bool $isSanitized = false;

    public static bool $overrideNotifUrl = false;

    public static array $curlOptions = [];

    public static function getBaseUrl(): string
    {
        return self::$isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }

    public static function getApiBaseUrl(): string
    {
        return self::$isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }
}
