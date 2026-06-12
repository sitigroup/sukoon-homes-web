<?php

namespace App\Plugins\Whatsapp\Support;

use App\Plugins\Whatsapp\Services\WhatsappService;

class Whatsapp
{
    /**
     * @return array{status:string,badge:string,message:string,reason?:string}
     */
    public static function notify(string $eventKey, array $contact, array $vars = []): array
    {
        try {
            return app(WhatsappService::class)->notify($eventKey, $contact, $vars);
        } catch (\Throwable $e) {
            report($e);
            return [
                'status' => 'failed',
                'badge' => 'danger',
                'message' => 'Failed - ' . $e->getMessage(),
                'reason' => 'facade_exception',
            ];
        }
    }
}

