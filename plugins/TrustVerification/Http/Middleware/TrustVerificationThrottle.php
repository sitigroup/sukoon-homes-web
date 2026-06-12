<?php

namespace App\Plugins\TrustVerification\Http\Middleware;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationRateLimiterRegistrar;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class TrustVerificationThrottle
{
    /**
     * @param  string  $profile  order-create|document-upload|payment-intent|confirm-payment|webhook-automation|webhook-cashfree
     */
    public function handle(Request $request, Closure $next, string $profile = ''): Response
    {
        if ($profile === '' || ! isset(TrustVerificationRateLimiterRegistrar::PROFILE_LIMITERS[$profile])) {
            return $next($request);
        }

        $retryAfter = 0;

        foreach (TrustVerificationRateLimiterRegistrar::PROFILE_LIMITERS[$profile] as $limiterName) {
            $factory = RateLimiter::limiter($limiterName);
            if (! $factory) {
                continue;
            }

            $limit = $factory($request);
            $key = $limiterName.':'.$limit->key;

            if (RateLimiter::tooManyAttempts($key, $limit->maxAttempts)) {
                $retryAfter = max($retryAfter, RateLimiter::availableIn($key));
            }
        }

        if ($retryAfter > 0) {
            $this->logRateLimitExceeded($request, $profile, $retryAfter);

            return response()->json([
                'error' => true,
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        foreach (TrustVerificationRateLimiterRegistrar::PROFILE_LIMITERS[$profile] as $limiterName) {
            $factory = RateLimiter::limiter($limiterName);
            if (! $factory) {
                continue;
            }

            $limit = $factory($request);
            $key = $limiterName.':'.$limit->key;
            RateLimiter::hit($key, $limit->decayMinutes * 60);
        }

        return $next($request);
    }

    private function logRateLimitExceeded(Request $request, string $profile, int $retryAfter): void
    {
        $order = $request->route('order');
        $orderId = $order instanceof TvOrder ? $order->id : (is_numeric($order) ? (int) $order : null);

        TrustVerificationAuditLogService::log([
            'order_id' => $orderId,
            'customer_id' => $request->user()?->id,
            'action' => TrustVerificationAuditLogService::ACTION_RATE_LIMIT_EXCEEDED,
            'description' => 'Trust Verification rate limit exceeded',
            'metadata' => [
                'profile' => $profile,
                'route' => $request->path(),
                'retry_after' => $retryAfter,
                'ip' => TrustVerificationRateLimiterRegistrar::clientIp($request),
            ],
        ], $request);
    }
}
