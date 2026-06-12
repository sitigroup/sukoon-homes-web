<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TrustVerificationContentService
{
    public const CACHE_KEY_PUBLIC = 'tv_content_public_v1';

    public const CACHE_TTL_SECONDS = 300;

    public static function seedDefaults(bool $force = false): int
    {
        $count = 0;
        foreach (TrustVerificationContentDefaults::blocks() as $row) {
            $existing = TvContentBlock::query()->where('content_key', $row['content_key'])->first();
            if ($existing && ! $force) {
                continue;
            }

            TvContentBlock::query()->updateOrCreate(
                ['content_key' => $row['content_key']],
                [
                    'group_key' => $row['group_key'],
                    'title' => $row['title'],
                    'type' => $row['type'],
                    'content' => $row['content'] ?? null,
                    'content_json' => $row['content_json'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => $row['sort_order'] ?? 0,
                    'version' => $existing?->version ?? 1,
                ]
            );
            $count++;
        }

        self::bustPublicCache();

        return $count;
    }

    public static function bustPublicCache(): void
    {
        Cache::forget(self::CACHE_KEY_PUBLIC);
    }

    /** @return array{hub: array<string, mixed>, wizard: array<string, mixed>, faq: list<array<string, mixed>>, legal: array<string, mixed>, testimonials: list<array<string, mixed>>, report: array<string, mixed>} */
    public static function publicPayload(): array
    {
        return Cache::remember(self::CACHE_KEY_PUBLIC, self::CACHE_TTL_SECONDS, function () {
            return self::buildPublicPayload();
        });
    }

    /** @return array{hub: array<string, mixed>, wizard: array<string, mixed>, faq: list<array<string, mixed>>, legal: array<string, mixed>, testimonials: list<array<string, mixed>>, report: array<string, mixed>} */
    public static function buildPublicPayload(): array
    {
        $payload = [
            'hub' => [],
            'wizard' => [],
            'faq' => [],
            'legal' => [],
            'testimonials' => [],
            'report' => [],
        ];

        $blocks = TvContentBlock::query()->active()->orderBy('sort_order')->orderBy('id')->get();

        foreach ($blocks as $block) {
            $shortKey = self::shortKey($block->content_key, $block->group_key);
            $value = self::exportBlockValue($block);

            if ($block->group_key === 'faq' && $block->type === TvContentBlock::TYPE_FAQ) {
                $payload['faq'][] = array_merge(
                    ['id' => $block->content_key, 'sort_order' => $block->sort_order],
                    (array) $block->content_json
                );

                continue;
            }

            if ($block->group_key === 'testimonials' && $block->type === TvContentBlock::TYPE_TESTIMONIAL) {
                $payload['testimonials'][] = array_merge(
                    ['id' => $block->content_key, 'sort_order' => $block->sort_order],
                    (array) $block->content_json
                );

                continue;
            }

            if ($block->group_key === 'legal' && $block->type === TvContentBlock::TYPE_LEGAL) {
                $legalKey = str_replace('legal.', '', $block->content_key);
                $payload['legal'][$legalKey] = $block->content_json;

                continue;
            }

            if ($block->group_key === 'report') {
                $payload['report'][$shortKey] = $value;

                continue;
            }

            if (isset($payload[$block->group_key]) && is_array($payload[$block->group_key])) {
                $payload[$block->group_key][$shortKey] = $value;
            }
        }

        usort($payload['faq'], fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        usort($payload['testimonials'], fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $payload;
    }

    public static function shortKey(string $contentKey, string $groupKey): string
    {
        $prefix = $groupKey.'.';
        if (str_starts_with($contentKey, $prefix)) {
            return substr($contentKey, strlen($prefix));
        }

        return $contentKey;
    }

    public static function exportBlockValue(TvContentBlock $block): mixed
    {
        if ($block->type === TvContentBlock::TYPE_JSON && is_array($block->content_json)) {
            return $block->content_json;
        }

        return $block->content;
    }

    public static function getText(string $contentKey, ?string $fallback = null): string
    {
        $block = TvContentBlock::query()->active()->where('content_key', $contentKey)->first();
        if ($block && is_string($block->content) && trim($block->content) !== '') {
            return $block->content;
        }

        $default = TrustVerificationContentDefaults::findDefault($contentKey);

        return $default['content'] ?? $fallback ?? '';
    }

    /** @return array<string, mixed>|null */
    public static function getJson(string $contentKey, ?array $fallback = null): ?array
    {
        $block = TvContentBlock::query()->active()->where('content_key', $contentKey)->first();
        if ($block && is_array($block->content_json)) {
            return $block->content_json;
        }

        $default = TrustVerificationContentDefaults::findDefault($contentKey);

        return $default['content_json'] ?? $fallback;
    }

    /** @return array{consent_text: string, legal_version: string, version: int} */
    public static function activeConsentForOrder(): array
    {
        $block = TvContentBlock::query()
            ->active()
            ->where('content_key', 'wizard.consent_checkbox_text')
            ->first();

        $text = $block?->content ?: TrustVerificationConsentService::CONSENT_TEXT;
        $version = (int) ($block?->version ?? 1);

        return [
            'consent_text' => $text,
            'legal_version' => 'cms-v'.$version,
            'version' => $version,
        ];
    }

    /** @param array<string, mixed> $input */
    public static function updateBlock(TvContentBlock $block, array $input, ?int $adminUserId = null): TvContentBlock
    {
        $type = $block->type;

        if (in_array($type, [TvContentBlock::TYPE_TEXT, TvContentBlock::TYPE_REPORT], true)) {
            $block->content = TrustVerificationContentSanitizer::sanitizeText($input['content'] ?? $block->content);
        } elseif ($type === TvContentBlock::TYPE_HTML) {
            $block->content = TrustVerificationContentSanitizer::sanitizeHtml($input['content'] ?? $block->content);
        } elseif (in_array($type, [TvContentBlock::TYPE_JSON, TvContentBlock::TYPE_FAQ, TvContentBlock::TYPE_LEGAL, TvContentBlock::TYPE_EMAIL, TvContentBlock::TYPE_TESTIMONIAL], true)) {
            $json = $input['content_json'] ?? $block->content_json;
            if (is_string($json)) {
                $decoded = json_decode($json, true);
                $json = is_array($decoded) ? $decoded : $block->content_json;
            }
            $block->content_json = TrustVerificationContentSanitizer::sanitizeJson(is_array($json) ? $json : null, $type);
        }

        if (array_key_exists('title', $input)) {
            $block->title = TrustVerificationContentSanitizer::sanitizeText($input['title']) ?? $block->title;
        }

        if (array_key_exists('sort_order', $input)) {
            $block->sort_order = (int) $input['sort_order'];
        }

        $block->version = (int) $block->version + 1;
        $block->updated_by = $adminUserId;
        $block->save();

        self::bustPublicCache();

        return $block->fresh();
    }

    public static function setPublished(TvContentBlock $block, bool $active, ?int $adminUserId = null): TvContentBlock
    {
        $block->is_active = $active;
        $block->updated_by = $adminUserId;
        $block->save();
        self::bustPublicCache();

        return $block;
    }

    public static function resetBlock(TvContentBlock $block, ?int $adminUserId = null): TvContentBlock
    {
        $default = TrustVerificationContentDefaults::findDefault($block->content_key);
        if (! $default) {
            return $block;
        }

        $block->title = $default['title'];
        $block->type = $default['type'];
        $block->content = $default['content'] ?? null;
        $block->content_json = $default['content_json'] ?? null;
        $block->is_active = $default['is_active'] ?? true;
        $block->sort_order = $default['sort_order'] ?? 0;
        $block->version = (int) $block->version + 1;
        $block->updated_by = $adminUserId;
        $block->save();

        self::bustPublicCache();

        return $block->fresh();
    }

    public static function replacePlaceholders(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        return $template;
    }

    /** @return array{subject: string, body_html: string|null, body_text: string|null} */
    public static function emailTemplate(string $contentKey, array $vars, array $fallback): array
    {
        $json = self::getJson($contentKey, $fallback);
        $subject = self::replacePlaceholders((string) ($json['subject'] ?? $fallback['subject'] ?? ''), $vars);
        $bodyHtml = isset($json['body_html']) ? self::replacePlaceholders((string) $json['body_html'], $vars) : null;
        $bodyText = isset($json['body_text']) ? self::replacePlaceholders((string) $json['body_text'], $vars) : null;

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    public static function logContentAudit(Request $request, string $action, TvContentBlock $block, string $description = ''): void
    {
        TrustVerificationAuditLogService::log([
            'admin_id' => auth()->id(),
            'action' => $action,
            'description' => $description ?: $block->content_key,
            'metadata' => [
                'content_key' => $block->content_key,
                'group_key' => $block->group_key,
                'version' => $block->version,
            ],
        ], $request);
    }
}
