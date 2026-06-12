<?php

namespace App\Plugins\Whatsapp\Support;

class WhatsappConversationTagCatalog
{
    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [
            'hot_lead',
            'tenant',
            'owner',
            'vendor',
            'renewal_due',
            'payment_pending',
            'verification',
            'maintenance',
        ];
    }

    public static function isValid(string $tag): bool
    {
        return in_array($tag, self::keys(), true);
    }

    public static function label(string $tag): string
    {
        return self::isValid($tag)
            ? __('whatsapp::whatsapp.tag_' . $tag)
            : $tag;
    }

    /**
     * @return array<int, string>
     */
    public static function autoFromCustomerType(?string $customerType): array
    {
        return match (strtolower(trim((string) $customerType))) {
            'tenant' => ['tenant'],
            'owner' => ['owner'],
            'vendor' => ['vendor'],
            default => [],
        };
    }
}
