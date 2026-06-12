<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\PaymentTransaction;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPaymentWebhookEvent;
use App\Services\HelperService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrustVerificationCashfreeWebhookService
{
    public const PROVIDER = 'cashfree';

    /** @var array<string, string> */
    private const SUCCESS_EVENTS = [
        'PAYMENT_SUCCESS_WEBHOOK',
        'PAYMENT_LINK_EVENT',
        'PAYMENT_SUCCESS',
        'ORDER_PAID',
        'PAYMENT_COMPLETED',
    ];

    /** @var array<string, string> */
    private const FAILED_EVENTS = [
        'PAYMENT_FAILED_WEBHOOK',
        'LINK_EXPIRED',
        'LINK_CANCELLED',
        'PAYMENT_FAILED',
        'PAYMENT_DECLINED',
        'ORDER_FAILED',
        'PAYMENT_USER_DROPPED',
        'USER_DROPPED',
    ];

    public static function handleHttpWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        try {
            self::verifyCashfreeSignature($request, $payload);
        } catch (WebhookSecurityException $e) {
            self::auditRejected(null, TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED, $e->getMessage(), $request);

            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        try {
            $input = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            self::auditRejected(null, TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED, 'invalid_json', $request);

            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        $payloadHash = self::hashPayload($payload);
        $signatureHash = self::hashSignatureHeader($request->header('x-webhook-signature'));
        $eventId = self::extractEventId($input);
        $eventType = strtoupper((string) ($input['type'] ?? $input['event'] ?? ''));
        $linkId = $input['data']['link_id'] ?? null;

        if (! $linkId) {
            self::auditRejected(null, TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED, 'missing_link_id', $request);

            return response()->json(['error' => 'Missing link_id'], 400);
        }

        $paymentTransaction = PaymentTransaction::query()
            ->where('order_id', $linkId)
            ->where('payment_gateway', 'Cashfree')
            ->first();

        if (! $paymentTransaction) {
            Log::warning('TrustVerification Cashfree webhook: transaction not found', ['link_id' => $linkId]);

            return response()->json(['success' => true], 200);
        }

        $tvOrder = TvOrder::query()
            ->where('payment_transaction_id', $paymentTransaction->id)
            ->first();

        if (! $tvOrder) {
            return response()->json(['success' => true], 200);
        }

        if (self::isDuplicateProcessed($payloadHash, $eventId)) {
            self::auditOrder(
                $tvOrder,
                TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_DUPLICATE,
                'Duplicate Cashfree webhook ignored',
                $request,
                ['payload_hash' => $payloadHash, 'event_id' => $eventId]
            );

            return response()->json(['success' => true, 'duplicate' => true], 200);
        }

        $webhookEvent = self::storeIncomingEvent([
            'event_id' => $eventId,
            'order_id' => $tvOrder->id,
            'payment_transaction_id' => $paymentTransaction->id,
            'signature_hash' => $signatureHash,
            'payload_hash' => $payloadHash,
            'payment_status' => self::resolveTargetPaymentStatus($eventType),
            'raw_payload' => $input,
        ]);

        if ($webhookEvent === null) {
            self::auditOrder(
                $tvOrder,
                TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_DUPLICATE,
                'Duplicate Cashfree webhook ignored (race)',
                $request,
                ['payload_hash' => $payloadHash]
            );

            return response()->json(['success' => true, 'duplicate' => true], 200);
        }

        self::auditOrder(
            $tvOrder,
            TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_RECEIVED,
            'Cashfree webhook received for trust verification order',
            $request,
            ['event_type' => $eventType, 'link_id' => $linkId, 'webhook_event_id' => $webhookEvent->id]
        );

        try {
            self::applyCashfreeEvent($tvOrder, $paymentTransaction, $eventType, $linkId, $webhookEvent, $request);
            $webhookEvent->update(['processed_at' => now()]);
        } catch (WebhookSecurityException $e) {
            $webhookEvent->update([
                'processed_at' => now(),
                'rejected_reason' => substr($e->getMessage(), 0, 120),
            ]);
            self::auditOrder(
                $tvOrder,
                TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED,
                $e->getMessage(),
                $request,
                ['webhook_event_id' => $webhookEvent->id]
            );

            return response()->json(['error' => $e->getMessage()], $e->getStatusCode() >= 400 ? $e->getStatusCode() : 422);
        }

        return response()->json(['success' => true], 200);
    }

    public static function syncOrderFromObserver(TvOrder $order, PaymentTransaction $transaction): TvOrder
    {
        $payloadHash = hash('sha256', implode('|', [
            'observer',
            (string) $transaction->id,
            (string) $transaction->payment_status,
            (string) $transaction->updated_at,
        ]));

        if (self::isDuplicateProcessed($payloadHash, null)) {
            return $order->fresh(['package', 'subject', 'checkItems', 'report']);
        }

        self::storeIncomingEvent([
            'order_id' => $order->id,
            'payment_transaction_id' => $transaction->id,
            'signature_hash' => null,
            'payload_hash' => $payloadHash,
            'payment_status' => (string) $transaction->payment_status,
            'raw_payload' => [
                'source' => 'payment_transaction_observer',
                'payment_transaction_id' => $transaction->id,
                'payment_status' => $transaction->payment_status,
            ],
        ]);

        $synced = TrustVerificationPaymentService::syncOrderFromPaymentTransaction(
            $order,
            $transaction,
            ['source' => 'observer']
        );

        TvPaymentWebhookEvent::query()
            ->where('payload_hash', $payloadHash)
            ->update(['processed_at' => now()]);

        return $synced;
    }

    /**
     * @throws WebhookSecurityException
     */
    public static function verifyCashfreeSignature(Request $request, string $payload): void
    {
        $clientSecret = trim((string) (HelperService::getPaymentDetails('cashfree')['cashfree_secret_key'] ?? ''));

        if ($clientSecret === '') {
            throw new WebhookSecurityException('Cashfree webhook secret is not configured', 503);
        }

        $receivedSignature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');

        if (! $receivedSignature || ! $timestamp) {
            throw new WebhookSecurityException('Missing Cashfree webhook signature headers', 401);
        }

        $signatureData = $timestamp.$payload;
        $calculatedSignature = base64_encode(hash_hmac('sha256', $signatureData, $clientSecret, true));

        if (! hash_equals($calculatedSignature, (string) $receivedSignature)) {
            Log::warning('TrustVerification Cashfree webhook: signature verification failed', [
                'ip' => $request->ip(),
            ]);
            throw new WebhookSecurityException('Invalid signature', 403);
        }
    }

    private static function applyCashfreeEvent(
        TvOrder $order,
        PaymentTransaction $transaction,
        string $eventType,
        string $linkId,
        TvPaymentWebhookEvent $webhookEvent,
        Request $request
    ): void {
        if (in_array($eventType, self::SUCCESS_EVENTS, true)) {
            self::markTransactionSuccess($transaction, $linkId);
            TrustVerificationPaymentService::syncOrderFromPaymentTransaction($order->fresh(), $transaction->fresh(), [
                'source' => 'cashfree_webhook',
                'webhook_event_id' => $webhookEvent->id,
            ]);

            return;
        }

        if (in_array($eventType, self::FAILED_EVENTS, true)) {
            if ($order->payment_status === 'paid') {
                TrustVerificationPaymentService::logDowngradeBlocked($order, $transaction, [
                    'source' => 'cashfree_webhook',
                    'event_type' => $eventType,
                ]);

                return;
            }

            if (! in_array($transaction->payment_status, ['failed'], true)) {
                $transaction->update(['payment_status' => 'failed']);
            }

            TrustVerificationPaymentService::syncOrderFromPaymentTransaction($order->fresh(), $transaction->fresh(), [
                'source' => 'cashfree_webhook',
                'webhook_event_id' => $webhookEvent->id,
            ]);

            return;
        }

        Log::info('TrustVerification Cashfree webhook: unhandled event type', [
            'event_type' => $eventType,
            'order_id' => $order->id,
        ]);
    }

    private static function markTransactionSuccess(PaymentTransaction $transaction, string $linkId): void
    {
        if (in_array(strtolower((string) $transaction->payment_status), ['success', 'succeed'], true)) {
            return;
        }

        $transaction->update([
            'transaction_id' => $linkId,
            'payment_status' => 'success',
        ]);
    }

    private static function resolveTargetPaymentStatus(string $eventType): ?string
    {
        if (in_array($eventType, self::SUCCESS_EVENTS, true)) {
            return 'paid';
        }

        if (in_array($eventType, self::FAILED_EVENTS, true)) {
            return 'failed';
        }

        return null;
    }

    private static function isDuplicateProcessed(string $payloadHash, ?string $eventId): bool
    {
        $query = TvPaymentWebhookEvent::query()->where('payload_hash', $payloadHash);

        if ($eventId) {
            $byEvent = TvPaymentWebhookEvent::query()
                ->where('provider', self::PROVIDER)
                ->where('event_id', $eventId)
                ->whereNotNull('processed_at')
                ->exists();

            if ($byEvent) {
                return true;
            }
        }

        return $query->whereNotNull('processed_at')->whereNull('rejected_reason')->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function storeIncomingEvent(array $data): ?TvPaymentWebhookEvent
    {
        try {
            return TvPaymentWebhookEvent::create([
                'provider' => self::PROVIDER,
                'event_id' => $data['event_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'payment_transaction_id' => $data['payment_transaction_id'] ?? null,
                'signature_hash' => $data['signature_hash'] ?? null,
                'payload_hash' => $data['payload_hash'],
                'payment_status' => $data['payment_status'] ?? null,
                'received_at' => now(),
                'raw_payload' => $data['raw_payload'] ?? null,
            ]);
        } catch (QueryException $e) {
            if (self::isDuplicateKey($e)) {
                return null;
            }

            throw $e;
        }
    }

    private static function isDuplicateKey(QueryException $e): bool
    {
        $code = (string) ($e->errorInfo[1] ?? '');

        return in_array($code, ['1062', '23000'], true) || str_contains(strtolower($e->getMessage()), 'duplicate');
    }

    public static function hashPayload(string $payload): string
    {
        return hash('sha256', $payload);
    }

    public static function hashSignatureHeader(?string $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        return hash('sha256', $signature);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function extractEventId(array $input): ?string
    {
        $candidates = [
            $input['data']['cf_payment_id'] ?? null,
            $input['data']['payment']['cf_payment_id'] ?? null,
            $input['data']['order']['order_id'] ?? null,
            $input['event_id'] ?? null,
            $input['data']['link_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }

    private static function auditRejected(?TvOrder $order, string $action, string $reason, Request $request): void
    {
        TrustVerificationAuditLogService::log([
            'order_id' => $order?->id,
            'action' => $action,
            'description' => 'Cashfree webhook rejected: '.$reason,
            'metadata' => ['reason' => $reason],
        ], $request);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private static function auditOrder(TvOrder $order, string $action, string $description, Request $request, ?array $metadata = null): void
    {
        TrustVerificationAuditLogService::log([
            'order_id' => $order->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ], $request);
    }
}
