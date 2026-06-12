<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaSetting;
use Illuminate\Support\Facades\Http;

class MetaGraphClient
{
    public function apiVersion(): string
    {
        return 'v20.0';
    }

    public function settings(): ?WaSetting
    {
        return WaSetting::query()->latest('id')->first();
    }

    public function testConnection(): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->phone_number_id || ! $settings->access_token) {
            return ['ok' => false, 'message' => __('whatsapp::whatsapp.missing_settings')];
        }

        try {
            $url = sprintf('https://graph.facebook.com/%s/%s', $this->apiVersion(), $settings->phone_number_id);
            $response = Http::timeout(20)
                ->withToken($settings->access_token)
                ->get($url, ['fields' => 'id,display_phone_number,verified_name']);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchTemplates(): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->waba_id || ! $settings->access_token) {
            return [];
        }

        $url = sprintf('https://graph.facebook.com/%s/%s/message_templates', $this->apiVersion(), $settings->waba_id);

        $response = Http::timeout(30)
            ->withToken($settings->access_token)
            ->get($url, ['limit' => 200]);

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }

    public function sendTemplate(string $phone, string $templateName, string $lang, array $variables = []): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->phone_number_id || ! $settings->access_token) {
            return ['ok' => false, 'error' => 'settings_missing'];
        }

        $components = [];
        if (! empty($variables)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], $variables),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $lang],
                'components' => $components,
            ],
        ];

        try {
            $url = sprintf('https://graph.facebook.com/%s/%s/messages', $this->apiVersion(), $settings->phone_number_id);
            $response = Http::timeout(30)
                ->withToken($settings->access_token)
                ->post($url, $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendText(string $phone, string $text): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->phone_number_id || ! $settings->access_token) {
            return ['ok' => false, 'error' => 'settings_missing'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

        try {
            $url = sprintf('https://graph.facebook.com/%s/%s/messages', $this->apiVersion(), $settings->phone_number_id);
            $response = Http::timeout(30)
                ->withToken($settings->access_token)
                ->post($url, $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function uploadMediaFromPath(string $absolutePath, string $mime): ?string
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->phone_number_id || ! $settings->access_token || ! is_file($absolutePath)) {
            return null;
        }

        try {
            $url = sprintf('https://graph.facebook.com/%s/%s/media', $this->apiVersion(), $settings->phone_number_id);
            $response = Http::timeout(60)
                ->withToken($settings->access_token)
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('id');
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public function sendImage(string $phone, string $mediaId, ?string $caption = null): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->phone_number_id || ! $settings->access_token) {
            return ['ok' => false, 'error' => 'settings_missing'];
        }

        $image = ['id' => $mediaId];
        if ($caption !== null && trim($caption) !== '') {
            $image['caption'] = trim($caption);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'image',
            'image' => $image,
        ];

        try {
            $url = sprintf('https://graph.facebook.com/%s/%s/messages', $this->apiVersion(), $settings->phone_number_id);
            $response = Http::timeout(30)
                ->withToken($settings->access_token)
                ->post($url, $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

