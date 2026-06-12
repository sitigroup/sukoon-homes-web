<?php

namespace App\Plugins\SeoEngine\Services\Content;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SeoEngineClaudeContentProvider implements SeoEngineAiProviderInterface
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
        return 'claude';
    }

    public function generate(string $prompt, array $options = []): array
    {
        $apiKey = (string) ($options['api_key'] ?? env('SEO_AI_API_KEY', ''));
        if ($apiKey === '') {
            return ['success' => false, 'error' => 'SEO_AI_API_KEY not configured'];
        }

        $model = (string) ($options['model'] ?? 'claude-3-5-haiku-20241022');
        $temperature = (float) ($options['temperature'] ?? 0.3);

        try {
            $response = $this->client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'max_tokens' => 2048,
                    'temperature' => $temperature,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($status >= 400) {
                $err = $body['error']['message'] ?? ('HTTP ' . $status);
                Log::warning('SeoEngine Claude content failed', ['error' => $err]);

                return ['success' => false, 'error' => $err];
            }

            $text = '';
            foreach ($body['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }

            if ($text === '') {
                return ['success' => false, 'error' => 'Empty Claude response'];
            }

            return ['success' => true, 'text' => $text];
        } catch (\Throwable $e) {
            Log::warning('SeoEngine Claude exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
