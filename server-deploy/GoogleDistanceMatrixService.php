<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Services\HelperService;
use Illuminate\Support\Facades\Http;

class GoogleDistanceMatrixService
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

    /**
     * @param  array<int, array{place_id: string, lat: float, lng: float}>  $destinations
     * @return array<string, array{duration_seconds: int, duration_text: string}> keyed by google place_id
     */
    public function fetchDrivingDurations(float $originLat, float $originLng, array $destinations): array
    {
        if (!$this->hasApiKey() || $destinations === []) {
            return [];
        }

        $destParts = [];
        foreach ($destinations as $dest) {
            $pid = (string) ($dest['place_id'] ?? '');
            if ($pid !== '') {
                $destParts[] = 'place_id:' . $pid;
            }
        }

        if ($destParts === []) {
            return [];
        }

        $response = Http::timeout(25)->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $originLat . ',' . $originLng,
            'destinations' => implode('|', $destParts),
            'mode' => 'driving',
            'key' => $this->apiKey,
        ]);

        if (!$response->successful()) {
            return [];
        }

        $json = (array) $response->json();
        $rows = $json['rows'] ?? [];
        if (!is_array($rows) || !isset($rows[0]['elements']) || !is_array($rows[0]['elements'])) {
            return [];
        }

        /** @var array<int, mixed> $elements */
        $elements = $rows[0]['elements'];
        $out = [];

        foreach ($elements as $idx => $element) {
            if (!is_array($element)) {
                continue;
            }

            $status = (string) ($element['status'] ?? '');
            if ($status !== 'OK') {
                continue;
            }

            $duration = $element['duration'] ?? null;
            if (!is_array($duration)) {
                continue;
            }

            $pid = (string) ($destinations[$idx]['place_id'] ?? '');
            if ($pid === '') {
                continue;
            }

            $out[$pid] = [
                'duration_seconds' => (int) ($duration['value'] ?? 0),
                'duration_text' => (string) ($duration['text'] ?? ''),
            ];
        }

        return $out;
    }
}
