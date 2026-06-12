<?php

namespace App\Plugins\Whatsapp\Support;

class MetaApiErrorFormatter
{
    public static function summarize(mixed $errorJson): ?string
    {
        if (! is_array($errorJson) || $errorJson === []) {
            return null;
        }

        $message = data_get($errorJson, 'error.message');
        if (! is_string($message) || $message === '') {
            $fallback = $errorJson['error'] ?? $errorJson['message'] ?? null;
            $message = is_string($fallback) ? $fallback : null;
        }

        $code = data_get($errorJson, 'error.code');
        $details = data_get($errorJson, 'error.error_data.details');

        $parts = [];
        if (is_string($message) && $message !== '') {
            $parts[] = $message;
        }
        if ($code) {
            $parts[] = 'code '.$code;
        }
        if (is_string($details) && $details !== '' && ! in_array($details, $parts, true)) {
            $parts[] = $details;
        }

        return $parts !== [] ? implode(' — ', $parts) : null;
    }
}
