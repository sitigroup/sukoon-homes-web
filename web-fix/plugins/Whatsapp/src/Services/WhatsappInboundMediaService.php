<?php

namespace App\Plugins\Whatsapp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class WhatsappInboundMediaService
{
    public function __construct(private readonly MetaGraphClient $meta) {}

    public function storeFromMetaId(string $mediaId): ?string
    {
        $settings = $this->meta->settings();
        if (! $settings?->access_token) {
            return null;
        }

        $metaUrl = sprintf(
            'https://graph.facebook.com/%s/%s',
            $this->meta->apiVersion(),
            $mediaId
        );

        $info = Http::timeout(20)
            ->withToken($settings->access_token)
            ->get($metaUrl);

        if (! $info->successful()) {
            Log::warning('whatsapp.media_meta_lookup_failed', [
                'media_id' => $mediaId,
                'status' => $info->status(),
            ]);

            return null;
        }

        $downloadUrl = $info->json('url');
        $mime = (string) ($info->json('mime_type') ?? 'application/octet-stream');
        if (! $downloadUrl) {
            return null;
        }

        $binary = Http::timeout(60)
            ->withToken($settings->access_token)
            ->get($downloadUrl);

        if (! $binary->successful()) {
            Log::warning('whatsapp.media_download_failed', [
                'media_id' => $mediaId,
                'status' => $binary->status(),
            ]);

            return null;
        }

        $path = 'wa-media/' . date('Y/m') . '/' . $mediaId . '.' . $this->extensionForMime($mime);
        Storage::disk('local')->put($path, $binary->body());

        return $path;
    }

    public function storeFromUploadedFile(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $path = 'wa-media/'.date('Y/m').'/out-'.uniqid('', true).'.'.$this->extensionForMime($mime);
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function extensionForMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/jpeg') => 'jpg',
            str_starts_with($mime, 'image/png') => 'png',
            str_starts_with($mime, 'image/webp') => 'webp',
            str_starts_with($mime, 'image/') => 'jpg',
            str_starts_with($mime, 'video/mp4') => 'mp4',
            str_starts_with($mime, 'audio/ogg') => 'ogg',
            str_starts_with($mime, 'audio/') => 'bin',
            $mime === 'application/pdf' => 'pdf',
            default => 'bin',
        };
    }
}
