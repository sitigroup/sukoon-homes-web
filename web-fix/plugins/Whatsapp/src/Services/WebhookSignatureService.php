<?php

namespace App\Plugins\Whatsapp\Services;

class WebhookSignatureService
{
    public function isValid(string $rawBody, string $headerSignature, string $appSecret): bool
    {
        if ($rawBody === '' || $headerSignature === '' || $appSecret === '') {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $headerSignature);
    }
}

