<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvContentBlock;

class TrustVerificationContentSanitizer
{
    /** @return array<string, mixed>|null */
    public static function sanitizeJson(?array $data, string $type): ?array
    {
        if ($data === null) {
            return null;
        }

        return match ($type) {
            TvContentBlock::TYPE_FAQ => self::sanitizeFaqJson($data),
            TvContentBlock::TYPE_TESTIMONIAL => self::sanitizeTestimonialJson($data),
            TvContentBlock::TYPE_LEGAL => self::sanitizeLegalJson($data),
            TvContentBlock::TYPE_EMAIL => self::sanitizeEmailJson($data),
            default => $data,
        };
    }

    public static function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html) ?? $html;
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/javascript:/i', '', $html) ?? $html;

        return trim($html);
    }

    public static function sanitizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = strip_tags($text);

        return trim($text);
    }

    /** @param array<string, mixed> $data */
    private static function sanitizeFaqJson(array $data): array
    {
        return [
            'question' => self::sanitizeText((string) ($data['question'] ?? '')),
            'answer' => self::sanitizeHtml((string) ($data['answer'] ?? '')) ?? self::sanitizeText((string) ($data['answer'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function sanitizeTestimonialJson(array $data): array
    {
        return [
            'customer_name' => self::sanitizeText((string) ($data['customer_name'] ?? '')),
            'rating' => max(1, min(5, (int) ($data['rating'] ?? 5))),
            'review' => self::sanitizeText((string) ($data['review'] ?? '')),
            'city' => self::sanitizeText((string) ($data['city'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function sanitizeLegalJson(array $data): array
    {
        $out = [
            'title' => self::sanitizeText((string) ($data['title'] ?? '')),
            'description' => self::sanitizeText((string) ($data['description'] ?? '')),
            'sections' => [],
        ];

        foreach ((array) ($data['sections'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }
            $entry = [
                'heading' => self::sanitizeText((string) ($section['heading'] ?? '')),
                'paragraphs' => [],
                'bullets' => [],
            ];
            foreach ((array) ($section['paragraphs'] ?? []) as $p) {
                $clean = self::sanitizeText((string) $p);
                if ($clean !== '') {
                    $entry['paragraphs'][] = $clean;
                }
            }
            foreach ((array) ($section['bullets'] ?? []) as $b) {
                $clean = self::sanitizeText((string) $b);
                if ($clean !== '') {
                    $entry['bullets'][] = $clean;
                }
            }
            if ($entry['heading'] !== '' || $entry['paragraphs'] || $entry['bullets']) {
                $out['sections'][] = $entry;
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $data */
    private static function sanitizeEmailJson(array $data): array
    {
        return [
            'subject' => self::sanitizeText((string) ($data['subject'] ?? '')),
            'body_html' => self::sanitizeHtml((string) ($data['body_html'] ?? '')),
            'body_text' => self::sanitizeText((string) ($data['body_text'] ?? '')),
        ];
    }
}
