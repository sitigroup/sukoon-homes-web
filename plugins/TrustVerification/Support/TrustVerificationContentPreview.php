<?php

namespace App\Plugins\TrustVerification\Support;

use App\Plugins\TrustVerification\Models\TvContentBlock;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use Illuminate\Support\Str;

/** Human-readable previews for admin CMS UI (no API/DB). */
class TrustVerificationContentPreview
{
    public static function headline(TvContentBlock $block): string
    {
        return $block->title ?: Str::headline(str_replace('.', ' ', $block->content_key));
    }

    public static function snippet(TvContentBlock $block, int $limit = 160): string
    {
        $text = match ($block->type) {
            TvContentBlock::TYPE_FAQ => (string) ($block->content_json['question'] ?? ''),
            TvContentBlock::TYPE_TESTIMONIAL => trim(
                ($block->content_json['customer_name'] ?? 'Customer')
                .' — '
                .($block->content_json['review'] ?? '')
            ),
            TvContentBlock::TYPE_EMAIL => (string) ($block->content_json['subject'] ?? ''),
            TvContentBlock::TYPE_LEGAL => (string) ($block->content_json['title'] ?? $block->title),
            TvContentBlock::TYPE_JSON => is_array($block->content_json)
                ? json_encode($block->content_json, JSON_UNESCAPED_UNICODE)
                : '',
            default => (string) ($block->content ?? ''),
        };

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?: '');

        return $text === '' ? '—' : Str::limit($text, $limit);
    }

    public static function formatted(TvContentBlock $block): string
    {
        $value = TrustVerificationContentService::exportBlockValue($block);

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—';
        }

        return (string) ($value ?? '—');
    }

    public static function statusLabel(TvContentBlock $block): string
    {
        return $block->is_active ? 'Active' : 'Draft';
    }

    public static function statusClass(TvContentBlock $block): string
    {
        return $block->is_active ? 'tv-cms-status--active' : 'tv-cms-status--draft';
    }
}
