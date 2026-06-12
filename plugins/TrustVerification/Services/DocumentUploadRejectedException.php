<?php

namespace App\Plugins\TrustVerification\Services;

use RuntimeException;

class DocumentUploadRejectedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reasonCode = 'rejected'
    ) {
        parent::__construct($message);
    }
}
