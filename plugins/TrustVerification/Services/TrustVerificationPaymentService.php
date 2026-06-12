<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationAutomationService;
use App\Services\HelperService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TrustVerificationPaymentService
{
    /**
     * After Cashfree payment, send the customer back to the public web app (not admin API host).
     */
    public static function cashfreeWebReturnUrl(string $platform = 'web', ?TvOrder $order = null): ?string
    {
        if ($platform !== 'web') {
            return null;
        }

        $base = rtrim((string) env('WEB_URL', 'https://homes.sukoon.group'), '/');
        $url = $base.'/payment/trust-verification-complete?lang=en';

        if ($order) {
            $url .= '&order_id='.(int) $order->id;
        }

        return $url;
    }

    public static function isCashfreeActive(): bool
    {
        $settings = HelperService::getPaymentDetails('cashfree');

        return ! empty($settings)
            && ! empty($settings['cashfree_app_id'] ?? null)
            && ! empty($settings['cashfree_secret_key'] ?? null);
    }

    public static function paymentSettingsPayload(): array
    {
        return [
            'cashfree_active' => self::isCashfreeActive(),
            'offline_fallback' => true,
        ];
    }

    public static function createPaymentIntent(TvOrder $order, Customer $customer, string $paymentMethod = 'cashfree', string $platform = 'web'): array
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            throw new \RuntimeException('Unauthorized');
        }

        if ($order->payment_status === 'paid') {
            throw new \RuntimeException('This order is already paid.');
        }

        if ($paymentMethod !== 'cashfree' || ! self::isCashfreeActive()) {
            throw new \RuntimeException('Cashfree payments are not available.');
        }

        $paymentSettings = HelperService::getPaymentDetails('cashfree');
        if (empty($paymentSettings)) {
            throw new \RuntimeException('Cashfree payments are not available.');
        }
        $paymentSettings['payment_method'] = 'cashfree';

        if (empty($customer->mobile)) {
            throw new \RuntimeException('Please update your phone number in your profile before paying online.');
        }

        return DB::transaction(function () use ($order, $customer, $paymentSettings, $platform) {
            $order->loadMissing('package');

            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $customer->id,
                'amount' => $order->amount,
                'payment_gateway' => 'Cashfree',
                'payment_status' => 'pending',
                'order_id' => null,
                'payment_type' => 'online payment',
            ]);

            $order->update(['payment_transaction_id' => $paymentTransaction->id]);

            $phoneNumber = (! empty($customer->country_code) && ! empty($customer->mobile))
                ? '+'.$customer->country_code.$customer->mobile
                : $customer->mobile;

            $description = sprintf(
                'Trust verification — %s (%s)',
                $order->package?->name ?? 'Package',
                $order->order_number
            );

            $metadata = [
                'payment_transaction_id' => $paymentTransaction->id,
                'user_id' => (string) $customer->id,
                'email' => $customer->email,
                'platform_type' => $platform,
                'description' => $description,
                'user_name' => trim(($customer->name ?? '').' '.($customer->last_name ?? '')),
                'phone' => $phoneNumber,
                'link_notes' => [
                    'trust_verification_order_id' => (string) $order->id,
                    'trust_verification_order_number' => $order->order_number,
                ],
            ];

            if ($webReturnUrl = self::cashfreeWebReturnUrl($platform, $order)) {
                $metadata['return_url'] = $webReturnUrl;
            }

            $paymentIntent = PaymentService::create($paymentSettings)->createAndFormatPaymentIntent(
                round((float) $order->amount, 2),
                $metadata
            );

            $paymentTransaction->update(['order_id' => $paymentIntent['id'] ?? null]);

            return [
                'payment_intent' => [
                    ...$paymentIntent,
                    'payment_transaction_id' => $paymentTransaction->id,
                ],
                'payment_transaction_id' => $paymentTransaction->id,
                'order_id' => $order->id,
            ];
        });
    }

    public static function syncPaymentStatus(TvOrder $order): TvOrder
    {
        if (! $order->payment_transaction_id) {
            return $order;
        }

        $txn = PaymentTransaction::find($order->payment_transaction_id);
        if (! $txn) {
            return $order;
        }

        return self::syncOrderFromPaymentTransaction($order, $txn, ['source' => 'sync']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function syncOrderFromPaymentTransaction(TvOrder $order, PaymentTransaction $txn, array $context = []): TvOrder
    {
        $txnStatus = strtolower((string) $txn->payment_status);
        $txnPaid = in_array($txnStatus, ['success', 'succeed'], true);
        $txnFailed = $txnStatus === 'failed';

        if ($order->payment_status === 'paid' && ! $txnPaid) {
            self::logDowngradeBlocked($order, $txn, $context);

            return $order->fresh(['package', 'subject', 'checkItems', 'report']);
        }

        if ($txnPaid) {
            if ((int) $order->payment_transaction_id !== (int) $txn->id) {
                TrustVerificationAuditLogService::log([
                    'order_id' => $order->id,
                    'action' => TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED,
                    'description' => 'Payment transaction reference mismatch',
                    'metadata' => array_merge($context, [
                        'expected_payment_transaction_id' => $order->payment_transaction_id,
                        'received_payment_transaction_id' => $txn->id,
                    ]),
                ]);

                return $order->fresh(['package', 'subject', 'checkItems', 'report']);
            }

            if (! self::amountsMatch($order, $txn)) {
                TrustVerificationAuditLogService::log([
                    'order_id' => $order->id,
                    'action' => TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_REJECTED,
                    'description' => 'Payment amount mismatch',
                    'metadata' => array_merge($context, [
                        'order_amount' => $order->amount,
                        'transaction_amount' => $txn->amount,
                    ]),
                ]);

                return $order->fresh(['package', 'subject', 'checkItems', 'report']);
            }

            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
                TrustVerificationAuditLogService::log([
                    'order_id' => $order->id,
                    'action' => TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_MARKED_PAID,
                    'description' => 'Trust verification order marked paid from payment transaction',
                    'metadata' => $context,
                ]);
                TrustVerificationAutomationService::maybeRunOnPaid($order->fresh());
            }

            return $order->fresh(['package', 'subject', 'checkItems', 'report']);
        }

        if ($txnFailed && $order->payment_status === 'pending') {
            $order->update(['payment_status' => 'failed']);
        }

        return $order->fresh(['package', 'subject', 'checkItems', 'report']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function logDowngradeBlocked(TvOrder $order, PaymentTransaction $txn, array $context = []): void
    {
        TrustVerificationAuditLogService::log([
            'order_id' => $order->id,
            'action' => TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_DOWNGRADE_BLOCKED,
            'description' => 'Blocked payment downgrade on paid trust verification order',
            'metadata' => array_merge($context, [
                'order_payment_status' => $order->payment_status,
                'transaction_payment_status' => $txn->payment_status,
            ]),
        ]);
    }

    public static function amountsMatch(TvOrder $order, PaymentTransaction $txn): bool
    {
        return abs((float) $order->amount - (float) $txn->amount) < 0.01;
    }

    public static function confirmPayment(TvOrder $order, Customer $customer, ?int $paymentTransactionId = null): TvOrder
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            throw new \RuntimeException('Unauthorized');
        }

        if ($paymentTransactionId && (int) $order->payment_transaction_id !== (int) $paymentTransactionId) {
            throw new \RuntimeException('Payment transaction mismatch.');
        }

        return self::syncPaymentStatus($order);
    }

    /**
     * Sync payment_status on TV orders from linked payment_transactions.
     *
     * @return array{message: string, updated: int, total: int}
     */
    public static function reconcilePendingPayments(bool $dryRun = false): array
    {
        $query = TvOrder::query()
            ->whereNotNull('payment_transaction_id')
            ->whereIn('payment_status', ['pending', 'failed']);

        $total = (clone $query)->count();

        if ($total === 0) {
            return [
                'message' => 'No trust verification orders need payment reconciliation.',
                'updated' => 0,
                'total' => 0,
            ];
        }

        $updated = 0;
        $lines = [];

        $query->orderBy('id')->chunkById(50, function ($orders) use ($dryRun, &$updated, &$lines) {
            foreach ($orders as $order) {
                $before = $order->payment_status;

                if ($dryRun) {
                    $after = self::previewPaymentStatus($order);
                } else {
                    $after = self::syncPaymentStatus($order)->payment_status;
                }

                if ($after !== $before) {
                    $lines[] = "{$order->order_number}: {$before} → {$after}";
                    $updated++;
                }
            }
        });

        $prefix = $dryRun ? 'Dry run complete' : 'Done';
        $message = "{$prefix} — {$updated} of {$total} order(s) would change.";
        if ($dryRun && $updated > 0) {
            $message = "[dry-run] Would reconcile {$total} order(s); {$updated} would change.\n".implode("\n", $lines);
        } elseif (! $dryRun && $updated > 0) {
            $message = "Reconciled {$updated} of {$total} order(s).\n".implode("\n", $lines);
        } elseif ($updated === 0) {
            $message = ($dryRun ? 'Dry run: ' : '')."Checked {$total} order(s); none needed updating.";
        }

        return [
            'message' => $message,
            'updated' => $updated,
            'total' => $total,
        ];
    }

    public static function previewPaymentStatus(TvOrder $order): string
    {
        if ($order->payment_status === 'paid') {
            return 'paid';
        }

        if (! $order->payment_transaction_id) {
            return $order->payment_status;
        }

        $txn = PaymentTransaction::find($order->payment_transaction_id);
        if (! $txn) {
            return $order->payment_status;
        }

        if (in_array(strtolower((string) $txn->payment_status), ['success', 'succeed'], true)) {
            if (! self::amountsMatch($order, $txn) || (int) $order->payment_transaction_id !== (int) $txn->id) {
                return $order->payment_status;
            }

            return 'paid';
        }

        if ($txn->payment_status === 'failed' && $order->payment_status === 'pending') {
            return 'failed';
        }

        return $order->payment_status;
    }
}
