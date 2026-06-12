<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Services\HelperService;
use Illuminate\Support\Facades\Http;

class GoogleNearbySearchService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) HelperService::getSettingData('place_api_key');
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey !== '';
    }

    public function maskedApiKey(): string
    {
        if ($this->apiKey === '') {
            return '';
        }

        $len = strlen($this->apiKey);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return substr($this->apiKey, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($this->apiKey, -4);
    }

    public function testApiKey(): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'message' => 'Google Places API key is not configured.'];
        }

        $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'key' => $this->apiKey,
            'location' => '28.6139,77.2090',
            'radius' => 1000,
            'type' => 'hospital',
        ]);

        if (!$response->successful()) {
            return ['ok' => false, 'message' => 'Google Places API request failed.'];
        }

        $json = (array) $response->json();
        $status = (string) ($json['status'] ?? 'UNKNOWN');

        if ($status === 'OK' || $status === 'ZERO_RESULTS') {
            return ['ok' => true, 'message' => 'Google Places API key is working.'];
        }

        return [
            'ok' => false,
            'message' => 'Google Places API status: ' . $status . (isset($json['error_message']) ? ' — ' . $json['error_message'] : ''),
        ];
    }

    public function search(float $latitude, float $longitude, int $radiusM, string $type, int $maxResults = 5): array
    {
        if (!$this->hasApiKey()) {
            return [];
        }

        $response = Http::timeout(20)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'key' => $this->apiKey,
            'location' => $latitude . ',' . $longitude,
            'radius' => max(100, $radiusM),
            'type' => $type,
        ]);

        if (!$response->successful()) {
            return [];
        }

        $json = (array) $response->json();
        if (($json['status'] ?? null) !== 'OK' || empty($json['results'])) {
            return [];
        }

        return array_slice((array) $json['results'], 0, max(1, min(20, $maxResults)));
    }
}
