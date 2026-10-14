<?php

namespace App\Payments;

final class WaveWebhookSignature
{
    public static function valid(string $body, string $header, string $secret): bool
    {
        if ($secret === '' || strlen($header) > 2048) {
            return false;
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                if ($timestamp !== null || !ctype_digit($value)) return false;
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        if ($timestamp === null || (int) $timestamp < time() - 300 || (int) $timestamp > time() + 30) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.$body, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) return true;
        }
        return false;
    }
}
