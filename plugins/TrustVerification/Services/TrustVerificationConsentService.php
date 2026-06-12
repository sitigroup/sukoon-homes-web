<?php

namespace App\Plugins\TrustVerification\Services;

use Illuminate\Http\Request;

class TrustVerificationConsentService
{
    public const LEGAL_VERSION = '2026-05-24';

    public const CONSENT_TEXT =
        'I confirm that I have permission to request this verification and the information/documents provided are correct.';

    /** @return array<string, mixed> */
    public static function orderConsentAttributes(?Request $request = null): array
    {
        $consent = TrustVerificationContentService::activeConsentForOrder();

        return [
            'consent_given' => true,
            'consent_text' => $consent['consent_text'],
            'consent_ip' => $request?->ip(),
            'consent_user_agent' => self::truncateUserAgent($request?->userAgent()),
            'consent_given_at' => now(),
            'legal_version' => $consent['legal_version'],
        ];
    }

    public static function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 2000);
    }
}
