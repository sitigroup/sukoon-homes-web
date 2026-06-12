<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Throwable;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CashfreePayment implements PaymentInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $currencyCode;
    private bool $isSandbox;
    private string $baseUrl;

    public function __construct($paymentData)
    {
        $this->clientId = $paymentData['cashfree_app_id'] ?? '';
        $this->clientSecret = $paymentData['cashfree_secret_key'] ?? '';
        $this->currencyCode = $paymentData['cashfree_currency'] ?? 'INR';
        $this->isSandbox = ($paymentData['cashfree_sandbox_mode'] ?? 0) == 1;
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    /**
     * Create Cashfree payment link
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            if (empty($this->clientId) || empty($this->clientSecret)) {
                throw new RuntimeException('Cashfree credentials are missing. Please check cashfree_app_id and cashfree_secret_key.');
            }

            $amount = $this->minimumAmountValidation($this->currencyCode, $amount);
            if (! empty($customMetaData['return_url'])) {
                $return_url = $customMetaData['return_url'];
            } else {
                $return_url = $customMetaData['platform_type'] == 'app'
                    ? route('payment.success')
                    : route('payment.success.web', ['gateway' => 'cashfree']);
            }
            $notify_url = url('/webhook/cashfree');
            if (! empty($customMetaData['link_notes']['trust_verification_order_id'])) {
                $notify_url = url('/api/trust-verification/webhook/cashfree');
            }

            if (!$this->isSandbox) {
                $return_url = str_replace('http://', 'https://', $return_url);
                $notify_url = str_replace('http://', 'https://', $notify_url);
            }

            $paymentTransactionId = $customMetaData['payment_transaction_id'] ?? null;

            /* Reuse an active link when user retries the same pending transaction */
            if ($paymentTransactionId) {
                $existingTxn = PaymentTransaction::find($paymentTransactionId);
                if ($existingTxn && !empty($existingTxn->order_id) && in_array($existingTxn->payment_status, ['pending', 'review'], true)) {
                    $existingLink = $this->fetchPaymentLink($existingTxn->order_id);
                    if ($this->isLinkPayable($existingLink)) {
                        Log::info('Cashfree reusing existing payment link', [
                            'link_id' => $existingTxn->order_id,
                            'payment_transaction_id' => $paymentTransactionId,
                        ]);
                        return $this->buildReturnFromLinkResponse($existingLink, $amount);
                    }
                }
            }

            $linkId = $this->generateUniqueLinkId($paymentTransactionId);

            $customerName = $this->sanitizeCustomerName($customMetaData['user_name'] ?? '');

            $customerPhone = $customMetaData['phone'] ?? '';
            if (empty($customerPhone)) {
                throw new RuntimeException('Customer phone number is required for Cashfree payment. Please update your profile with a valid phone number.');
            }

            $linkData = [
                'link_id' => $linkId,
                'link_amount' => round($amount, 2),
                'link_currency' => $this->currencyCode,
                'link_purpose' => $customMetaData['description'] ?? 'Payment',
                'customer_details' => [
                    'customer_name'  => $customerName,
                    'customer_email' => $customMetaData['email'] ?? '',
                    'customer_phone' => $customerPhone,
                ],
                'link_notify' => [
                    'send_email' => false,
                    'send_sms' => false,
                ],
                'link_meta' => [
                    'return_url' => $return_url,
                    'notify_url' => $notify_url,
                ],
            ];

            if (isset($customMetaData['link_notes'])) {
                $linkData['link_notes'] = $customMetaData['link_notes'];
            }

            Log::info('Cashfree Payment Link Request: ', [
                'url' => $this->baseUrl . '/links',
                'client_id' => $this->clientId,
                'is_sandbox' => $this->isSandbox,
                'link_id' => $linkId,
            ]);

            $response = Http::withHeaders($this->apiHeaders())
                ->post($this->baseUrl . '/links', $linkData);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $statusCode = $response->status();

                if ($statusCode === 400 && str_contains($errorBody, 'Link ID already exists')) {
                    $recovered = $this->recoverExistingLink($linkId, $paymentTransactionId, $amount);
                    if ($recovered !== null) {
                        return $recovered;
                    }
                }

                Log::error('Cashfree createPaymentLink failed', [
                    'status_code' => $statusCode,
                    'response' => $errorBody,
                    'link_id' => $linkId,
                ]);
                throw new RuntimeException("Failed to create Cashfree payment link (Status: {$statusCode}): {$errorBody}");
            }

            $responseData = $response->json();
            Log::info('Cashfree Payment Link Response: ' . json_encode($responseData));

            return $this->buildReturnFromLinkResponse($responseData, $amount, $linkId);

        } catch (Throwable $e) {
            Log::error('Cashfree createPaymentIntent failed: ' . $e->getMessage());
            throw new RuntimeException($e->getMessage());
        }
    }

    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);
        if (!$paymentIntent) {
            throw new RuntimeException('Cashfree payment intent creation returned an empty response.');
        }
        return $this->format($paymentIntent, $amount, $this->currencyCode, $customMetaData);
    }

    public function retrievePaymentIntent($paymentId): array
    {
        return $this->fetchPaymentLink($paymentId) ?? [];
    }

    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent, $paymentUrl = ''): array
    {
        return [
            'id' => $id,
            'payment_url' => $paymentUrl,
        ];
    }

    private function format($paymentIntent, $amount, $currencyCode, $metadata): array
    {
        $id = $paymentIntent['link_id'] ?? $paymentIntent['cf_link_id'] ?? '';
        $status = $paymentIntent['link_status'] ?? 'ACTIVE';
        $paymentUrl = $paymentIntent['payment_url'] ?? $paymentIntent['link_url'] ?? '';

        return $this->formatPaymentIntent($id, $amount, $currencyCode, $status, $metadata, $paymentIntent, $paymentUrl);
    }

    private function generateUniqueLinkId($paymentTransactionId): string
    {
        if ($paymentTransactionId) {
            return 'link_' . $paymentTransactionId . '_' . bin2hex(random_bytes(4));
        }

        return 'link_' . uniqid('', true);
    }

    private function apiHeaders(): array
    {
        return [
            'x-client-id' => $this->clientId,
            'x-client-secret' => $this->clientSecret,
            'x-api-version' => '2023-08-01',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function fetchPaymentLink(string $linkId): ?array
    {
        try {
            $response = Http::withHeaders($this->apiHeaders())
                ->get($this->baseUrl . '/links/' . rawurlencode($linkId));

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Throwable $e) {
            Log::warning('Cashfree fetchPaymentLink failed: ' . $e->getMessage(), ['link_id' => $linkId]);
        }

        return null;
    }

    private function isLinkPayable(?array $data): bool
    {
        if (!$data) {
            return false;
        }

        $status = strtoupper((string) ($data['link_status'] ?? ''));
        $payableStatuses = ['ACTIVE', 'PARTIALLY_PAID'];

        return in_array($status, $payableStatuses, true) && !empty($data['link_url']);
    }

    private function buildReturnFromLinkResponse(array $responseData, $amount, ?string $linkId = null): array
    {
        $paymentUrl = $responseData['link_url'] ?? null;
        if (!$paymentUrl) {
            throw new RuntimeException('Failed to create Cashfree payment link: Missing link_url');
        }

        $resolvedLinkId = $linkId ?? ($responseData['link_id'] ?? null);
        if (!$resolvedLinkId) {
            throw new RuntimeException('Failed to create Cashfree payment link: Missing link_id');
        }

        return [
            'link_id' => $resolvedLinkId,
            'cf_link_id' => $responseData['cf_link_id'] ?? null,
            'payment_url' => $paymentUrl,
            'link_amount' => round($amount, 2),
            'link_currency' => $this->currencyCode,
            'link_status' => $responseData['link_status'] ?? 'ACTIVE',
            'data' => $responseData,
        ];
    }

    /**
     * If Cashfree says link_id exists, fetch and return the existing active link.
     */
    private function recoverExistingLink(string $attemptedLinkId, $paymentTransactionId, $amount): ?array
    {
        $candidates = array_unique(array_filter([
            $attemptedLinkId,
            $paymentTransactionId ? 'link_' . $paymentTransactionId : null,
        ]));

        foreach ($candidates as $linkId) {
            $existing = $this->fetchPaymentLink($linkId);
            if ($this->isLinkPayable($existing)) {
                Log::info('Cashfree recovered existing link after duplicate error', ['link_id' => $linkId]);
                return $this->buildReturnFromLinkResponse($existing, $amount, $linkId);
            }
        }

        return null;
    }

    public function minimumAmountValidation($currency, $amount)
    {
        $minimumAmount = match (strtoupper($currency)) {
            'INR' => 1.00,
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'NZD', 'SGD', 'HKD' => 0.01,
            'JPY' => 1,
            'KRW' => 10,
            'PKR', 'BDT' => 1,
            default => 0.01,
        };

        return max($amount, $minimumAmount);
    }

    private function sanitizeCustomerName($name)
    {
        if (empty($name)) {
            return 'Customer';
        }

        $sanitized = preg_replace('/[^a-zA-Z\s.\'-]/u', '', $name);
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));

        if (empty($sanitized)) {
            return 'Customer';
        }

        return substr($sanitized, 0, 100);
    }
}
