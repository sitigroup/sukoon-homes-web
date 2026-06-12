<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvOrder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TrustVerificationRateLimiterRegistrar
{
    public const LIMITER_ORDER_CREATE_USER = 'tv-order-create-user';

    public const LIMITER_ORDER_CREATE_IP = 'tv-order-create-ip';

    public const LIMITER_DOCUMENT_UPLOAD_ORDER = 'tv-document-upload-order';

    public const LIMITER_DOCUMENT_UPLOAD_USER = 'tv-document-upload-user';

    public const LIMITER_DOCUMENT_UPLOAD_IP = 'tv-document-upload-ip';

    public const LIMITER_PAYMENT_INTENT_ORDER = 'tv-payment-intent-order';

    public const LIMITER_PAYMENT_INTENT_USER = 'tv-payment-intent-user';

    public const LIMITER_CONFIRM_PAYMENT_ORDER = 'tv-confirm-payment-order';

    public const LIMITER_WEBHOOK_AUTOMATION_IP = 'tv-webhook-automation-ip';

    public const LIMITER_WEBHOOK_CASHFREE_IP = 'tv-webhook-cashfree-ip';

    public const LIMITER_SAMPLE_REPORT_DOWNLOAD_IP = 'tv-sample-report-download-ip';

    public const LIMITER_SAMPLE_REPORT_VIEW_IP = 'tv-sample-report-view-ip';

    /** @var array<string, list<string>> */
    public const PROFILE_LIMITERS = [
        'order-create' => [
            self::LIMITER_ORDER_CREATE_USER,
            self::LIMITER_ORDER_CREATE_IP,
        ],
        'document-upload' => [
            self::LIMITER_DOCUMENT_UPLOAD_ORDER,
            self::LIMITER_DOCUMENT_UPLOAD_USER,
            self::LIMITER_DOCUMENT_UPLOAD_IP,
        ],
        'payment-intent' => [
            self::LIMITER_PAYMENT_INTENT_ORDER,
            self::LIMITER_PAYMENT_INTENT_USER,
        ],
        'confirm-payment' => [
            self::LIMITER_CONFIRM_PAYMENT_ORDER,
        ],
        'webhook-automation' => [
            self::LIMITER_WEBHOOK_AUTOMATION_IP,
        ],
        'webhook-cashfree' => [
            self::LIMITER_WEBHOOK_CASHFREE_IP,
        ],
    ];

    public static function register(): void
    {
        RateLimiter::for(self::LIMITER_ORDER_CREATE_USER, function (Request $request) {
            return Limit::perHour(5)->by('user:'.($request->user()?->id ?? 'guest'));
        });

        RateLimiter::for(self::LIMITER_ORDER_CREATE_IP, function (Request $request) {
            return Limit::perHour(10)->by('ip:'.self::clientIp($request));
        });

        RateLimiter::for(self::LIMITER_DOCUMENT_UPLOAD_ORDER, function (Request $request) {
            return Limit::perHour(10)->by('order:'.self::orderIdFromRequest($request));
        });

        RateLimiter::for(self::LIMITER_DOCUMENT_UPLOAD_USER, function (Request $request) {
            return Limit::perHour(20)->by('user:'.($request->user()?->id ?? 'guest'));
        });

        RateLimiter::for(self::LIMITER_DOCUMENT_UPLOAD_IP, function (Request $request) {
            return Limit::perHour(30)->by('ip:'.self::clientIp($request));
        });

        RateLimiter::for(self::LIMITER_PAYMENT_INTENT_ORDER, function (Request $request) {
            return Limit::perHour(5)->by('order:'.self::orderIdFromRequest($request));
        });

        RateLimiter::for(self::LIMITER_PAYMENT_INTENT_USER, function (Request $request) {
            return Limit::perHour(10)->by('user:'.($request->user()?->id ?? 'guest'));
        });

        RateLimiter::for(self::LIMITER_CONFIRM_PAYMENT_ORDER, function (Request $request) {
            return Limit::perHour(10)->by('order:'.self::orderIdFromRequest($request));
        });

        RateLimiter::for(self::LIMITER_WEBHOOK_AUTOMATION_IP, function (Request $request) {
            return Limit::perMinute(60)->by('ip:'.self::clientIp($request));
        });

        RateLimiter::for(self::LIMITER_WEBHOOK_CASHFREE_IP, function (Request $request) {
            return Limit::perMinute(120)->by('ip:'.self::clientIp($request));
        });

        RateLimiter::for(self::LIMITER_SAMPLE_REPORT_DOWNLOAD_IP, function (Request $request) {
            return Limit::perHour(40)->by('ip:'.self::clientIp($request));
        });

        RateLimiter::for(self::LIMITER_SAMPLE_REPORT_VIEW_IP, function (Request $request) {
            return Limit::perHour(80)->by('ip:'.self::clientIp($request));
        });
    }

    /**
     * @return list<\Illuminate\Cache\RateLimiting\Limit>
     */
    public static function resolveLimits(Request $request, string $profile): array
    {
        $names = self::PROFILE_LIMITERS[$profile] ?? [];
        $limits = [];

        foreach ($names as $name) {
            $factory = RateLimiter::limiter($name);
            if ($factory) {
                $limits[] = $factory($request);
            }
        }

        return $limits;
    }

    public static function clientIp(Request $request): string
    {
        return $request->ip() ?: 'unknown';
    }

    public static function orderIdFromRequest(Request $request): string
    {
        $order = $request->route('order');
        if ($order instanceof TvOrder) {
            return (string) $order->id;
        }

        if (is_numeric($order)) {
            return (string) $order;
        }

        return 'unknown';
    }
}
