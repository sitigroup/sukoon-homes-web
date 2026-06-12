<?php

namespace App\Plugins\Theme\Support;

class ThemeContrast
{
    private const MIN_CONTRAST = 4.5;

    /** Semantic defaults when derived from Sukoon Pure Black Luxury */
    public const SEMANTIC_DEFAULTS = [
        'text_primary' => '#111827',
        'text_secondary' => '#6B7280',
        'text_on_dark' => '#FFFFFF',
        'placeholder' => '#9CA3AF',
        'bg_primary' => '#FFFFFF',
        'bg_dark' => '#111827',
    ];

    public static function deriveSemantic(array $tokens): array
    {
        $textPrimary = $tokens['primary'] ?? self::SEMANTIC_DEFAULTS['text_primary'];
        $textSecondary = $tokens['muted'] ?? self::SEMANTIC_DEFAULTS['text_secondary'];
        $textOnDark = $tokens['white'] ?? self::SEMANTIC_DEFAULTS['text_on_dark'];
        $bgPrimary = $tokens['card'] ?? self::SEMANTIC_DEFAULTS['bg_primary'];
        $bgDark = $tokens['sidebar'] ?? $tokens['primary'] ?? self::SEMANTIC_DEFAULTS['bg_dark'];
        $border = $tokens['border'] ?? '#E5E7EB';

        $link = $tokens['link'] ?? $textPrimary;
        $buttonBg = $tokens['button'] ?? $bgDark;
        $placeholder = $tokens['placeholder'] ?? '#9CA3AF';

        return [
            'text_primary' => self::ensureContrast($textPrimary, $bgPrimary, '#111827', '#FFFFFF'),
            'text_secondary' => self::ensureContrast($textSecondary, $bgPrimary, '#6B7280', '#D1D5DB'),
            'text_on_dark' => self::ensureContrast($textOnDark, $bgDark, '#FFFFFF', '#111827'),
            'placeholder' => self::ensureContrast($placeholder, $bgPrimary, '#9CA3AF', '#D1D5DB'),
            'bg_primary' => $bgPrimary,
            'bg_dark' => $bgDark,
            'border' => $border,
            'link_on_light' => self::ensureContrast($link, $bgPrimary, '#111827', '#000000'),
            'link_on_dark' => self::ensureContrast($link, $bgDark, '#FFFFFF', '#F9FAFB'),
            'button_text' => self::ensureContrast($textOnDark, $buttonBg, '#FFFFFF', '#111827'),
            'button_hover_text' => self::ensureContrast(
                $textOnDark,
                $tokens['hover'] ?? $buttonBg,
                '#FFFFFF',
                '#111827'
            ),
        ];
    }

    public static function semanticCssVariables(array $tokens): array
    {
        $semantic = self::deriveSemantic($tokens);
        $vars = [];

        foreach ($semantic as $key => $value) {
            $vars['--sukoon-' . str_replace('_', '-', $key)] = $value;
        }

        $vars['--sukoon-text-primary'] = $semantic['text_primary'];
        $vars['--sukoon-text-secondary'] = $semantic['text_secondary'];
        $vars['--sukoon-text-on-dark'] = $semantic['text_on_dark'];
        $vars['--sukoon-bg-primary'] = $semantic['bg_primary'];
        $vars['--sukoon-bg-dark'] = $semantic['bg_dark'];
        $vars['--sukoon-border'] = $semantic['border'];
        $vars['--sukoon-placeholder'] = $semantic['placeholder'];

        return $vars;
    }

    public static function ensureContrast(string $foreground, string $background, string $lightFallback, string $darkFallback): string
    {
        $fg = self::normalizeHex($foreground);
        $bg = self::normalizeHex($background);

        if ($fg === $bg || self::contrastRatio($fg, $bg) < self::MIN_CONTRAST) {
            return self::relativeLuminance($bg) > 0.45 ? $lightFallback : $darkFallback;
        }

        return strtoupper($fg);
    }

    public static function contrastRatio(string $hex1, string $hex2): float
    {
        $l1 = self::relativeLuminance($hex1);
        $l2 = self::relativeLuminance($hex2);
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        $rs = self::linearize($r / 255);
        $gs = self::linearize($g / 255);
        $bs = self::linearize($b / 255);

        return 0.2126 * $rs + 0.7152 * $gs + 0.0722 * $bs;
    }

    public static function isDarkBackground(string $hex): bool
    {
        return self::relativeLuminance($hex) < 0.45;
    }

    private static function linearize(float $channel): float
    {
        return $channel <= 0.03928
            ? $channel / 12.92
            : pow(($channel + 0.055) / 1.055, 2.4);
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = self::normalizeHex($hex);
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function normalizeHex(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtoupper($value);
        }

        return '#111827';
    }
}
