<?php

namespace App\Plugins\TrustVerification\Services\Automation;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpVerificationProvider implements VerificationProviderInterface
{
    public function key(): string
    {
        return 'http';
    }

    public function label(): string
    {
        return 'HTTP API (IDfy / AuthBridge style)';
    }

    public function run(TvOrder $order, array $settings): array
    {
        $baseUrl = rtrim(trim((string) ($settings['http_api_base_url'] ?? '')), '/');
        $apiKey = trim((string) ($settings['http_api_key'] ?? ''));
        $apiSecret = trim((string) ($settings['http_api_secret'] ?? ''));

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('HTTP provider is not configured (base URL and API key required).');
        }

        $order->loadMissing(['subject', 'checkItems', 'documents', 'package']);

        $payload = [
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'city_slug' => $order->city_slug,
            'package' => $order->package?->slug,
            'subject' => TrustVerificationService::formatOrder($order)['subject'] ?? null,
            'check_keys' => $order->checkItems->where('status', '!=', 'na')->pluck('check_key')->values()->all(),
            'documents_uploaded' => $order->documents->pluck('doc_type')->values()->all(),
        ];

        $response = Http::timeout(30)
            ->withHeaders(array_filter([
                'Authorization' => 'Bearer '.$apiKey,
                'X-Api-Key' => $apiKey,
                'X-Api-Secret' => $apiSecret ?: null,
            ]))
            ->post($baseUrl.'/verify', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Vendor API error: HTTP '.$response->status().' — '.$response->body());
        }

        $body = $response->json();
        $results = $body['results'] ?? $body['data']['results'] ?? [];

        if (! is_array($results)) {
            throw new \RuntimeException('Vendor API returned invalid results payload.');
        }

        $normalized = [];
        foreach ($results as $row) {
            if (empty($row['check_key']) || empty($row['status'])) {
                continue;
            }
            $status = strtolower((string) $row['status']);
            if (! in_array($status, ['pending', 'pass', 'fail', 'na'], true)) {
                continue;
            }
            $normalized[] = [
                'check_key' => (string) $row['check_key'],
                'status' => $status,
                'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            ];
        }

        return $normalized;
    }
}
