<?php

namespace App\Plugins\Whatsapp\Support;

/**
 * System events are wired in plugin code; admin can map/enable/disable only.
 * Custom events are fully managed in admin (add/edit/delete) for manual sends.
 */
class WhatsappEventCatalog
{
    /**
     * @return array<string, array{display_name:string,description:string,platform_trigger:string,wired:bool}>
     */
    public static function systemDefinitions(): array
    {
        return [
            'vendor_assigned' => [
                'display_name' => 'Vendor Assigned',
                'description' => 'Sent to the vendor when an agent assigns them to a maintenance job.',
                'platform_trigger' => 'Agent assigns vendor (maintenance API)',
                'wired' => true,
            ],
            'maintenance_assigned' => [
                'display_name' => 'Maintenance Assigned',
                'description' => 'Notify tenant or owner when a vendor is assigned to their maintenance request.',
                'platform_trigger' => 'Maintenance vendor assigned (admin or agent)',
                'wired' => true,
            ],
            'maintenance_resolved' => [
                'display_name' => 'Maintenance Resolved',
                'description' => 'Sent to the tenant when a repair is marked resolved.',
                'platform_trigger' => 'Agent marks maintenance resolved',
                'wired' => true,
            ],
            'rent_reminder' => [
                'display_name' => 'Rent Reminder',
                'description' => 'Rent due reminder to tenant.',
                'platform_trigger' => 'Admin sends rent reminder (owner tenancies)',
                'wired' => true,
            ],
            'agreement_ready' => [
                'display_name' => 'Agreement Ready',
                'description' => 'Rental agreement PDF generated or signed copy uploaded — notifies owner and tenant.',
                'platform_trigger' => 'Agreement generated / signed-copy ready',
                'wired' => true,
            ],
            'agreement_renewal' => [
                'display_name' => 'Agreement Renewal',
                'description' => 'Renewal reminder or new renewal agreement — tenant and owner when phones differ.',
                'platform_trigger' => 'Renewal reminder schedule / renewal agreement created',
                'wired' => true,
            ],
            'verification_completed' => [
                'display_name' => 'Verification Completed',
                'description' => 'Trust verification completed.',
                'platform_trigger' => 'Not wired yet — manual send only',
                'wired' => false,
            ],
            'owner_payment_request' => [
                'display_name' => 'Owner Payment Request',
                'description' => 'Owner must pay vendor for an owner-responsibility repair.',
                'platform_trigger' => 'Owner-responsibility repair resolved + cost known',
                'wired' => true,
            ],
            'otp' => [
                'display_name' => 'OTP',
                'description' => 'One-time password / authentication code.',
                'platform_trigger' => 'Not wired yet — manual send only',
                'wired' => false,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::systemDefinitions());
    }

    public static function isSystemKey(string $eventKey): bool
    {
        return array_key_exists($eventKey, self::systemDefinitions());
    }

    public static function isWired(string $eventKey): bool
    {
        return (bool) (self::systemDefinitions()[$eventKey]['wired'] ?? false);
    }

    public static function platformTrigger(string $eventKey): string
    {
        if (self::isSystemKey($eventKey)) {
            return (string) (self::systemDefinitions()[$eventKey]['platform_trigger'] ?? '');
        }

        return __('Manual send only (custom event)');
    }

    /**
     * Hints for manual send variable fields ({{1}}, {{2}}, …).
     *
     * @return array<int, string>
     */
    public static function variableHints(string $eventKey): array
    {
        $hints = [
            'vendor_assigned' => [
                'Job label (category — subcategory)',
                'Property area',
                'Priority',
                'Agent name | responsibility context',
                'Request ref (MR-…)',
            ],
            'maintenance_resolved' => [
                'Repair title',
                'Request ref (MR-…)',
            ],
            'rent_reminder' => [
                'Rent amount',
                'Property label',
                'Due date',
            ],
            'agreement_ready' => [
                'Agreement number (RA-…)',
            ],
            'owner_payment_request' => [
                'Repair description',
                'Amount (e.g. Rs. 1,500)',
                'Request ref (MR-…)',
            ],
            'agreement_renewal' => ['Agreement / renewal ref'],
            'otp' => ['OTP code'],
            'verification_completed' => ['Verification summary'],
            'maintenance_assigned' => ['Request ref', 'Summary'],
        ];

        return $hints[$eventKey] ?? [];
    }
}
