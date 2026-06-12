<?php

namespace App\Plugins\SeoEngine\Services\Content;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SeoEngineGeminiContentProvider implements SeoEngineAiProviderInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function generate(string $prompt, array $options = []): array
    {
        $apiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
        if ($apiKey === '') {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not configured'];
        }

        $endpoint = (string) config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite');
        if (! str_contains($endpoint, ':generateContent')) {
            $endpoint .= ':generateContent';
        }

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ],
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'maxOutputTokens' => 2048,
                        'temperature' => 0.3,
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($status < 200 || $status >= 300) {
                $err = $body['error']['message'] ?? ('HTTP ' . $status);
                Log::warning('SeoEngine Gemini content failed', ['error' => $err]);

                return ['success' => false, 'error' => $err];
            }

            $text = (string) ($body['candidates'][0]['content']['parts'][0]['text'] ?? '');
            if ($text === '') {
                return ['success' => false, 'error' => 'Empty Gemini response'];
            }

            return ['success' => true, 'text' => $text];
        } catch (\Throwable $e) {
            Log::warning('SeoEngine Gemini exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
