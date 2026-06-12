<?php

namespace App\Plugins\TrustVerification\Services;

use Exception;

class WebhookSecurityException extends Exception
{
    public function __construct(string $message, protected int $statusCode = 403)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
