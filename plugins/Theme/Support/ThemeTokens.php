<?php

namespace App\Plugins\Theme\Support;

class ThemeTokens
{
    public static function shadowValue(string $intensity): string
    {
        return match ($intensity) {
            'soft' => '0 2px 12px rgba(15, 23, 42, 0.05)',
            'strong' => '0 8px 32px rgba(15, 23, 42, 0.14)',
            default => '0 4px 20px rgba(15, 23, 42, 0.08)',
        };
    }

    public static function normalize(array $input): array
    {
        $defaults = ThemePresets::defaultPreset();

        $tokens = [];
        foreach (ThemePresets::TOKEN_KEYS as $key) {
            $value = $input['tokens'][$key] ?? $defaults['tokens'][$key] ?? '#000000';
            $tokens[$key] = self::normalizeColor((string) $value);
        }

        $typography = trim((string) ($input['typography'] ?? $defaults['typography']));
        $borderRadius = self::normalizeRadius((string) ($input['border_radius'] ?? $defaults['border_radius']));
        $shadowIntensity = self::normalizeShadowIntensity((string) ($input['shadow_intensity'] ?? $defaults['shadow_intensity']));

        return [
            'tokens' => $tokens,
            'typography' => $typography !== '' ? $typography : $defaults['typography'],
            'border_radius' => $borderRadius,
            'shadow_intensity' => $shadowIntensity,
            'shadow' => self::shadowValue($shadowIntensity),
            'preset_slug' => isset($input['preset_slug']) ? (string) $input['preset_slug'] : null,
        ];
    }

    public static function cssVariables(array $payload): array
    {
        $normalized = self::normalize($payload);
        $vars = [];

        foreach ($normalized['tokens'] as $key => $value) {
            $vars['--sukoon-' . str_replace('_', '-', $key)] = $value;
        }

        $vars = array_merge($vars, ThemeContrast::semanticCssVariables($normalized['tokens']));

        $vars['--sukoon-font-family'] = $normalized['typography'];
        $vars['--sukoon-radius'] = $normalized['border_radius'];
        $vars['--sukoon-shadow'] = $normalized['shadow'];
        $vars['--primary-color'] = $normalized['tokens']['primary'];
        $vars['--sukoon-accent'] = $normalized['tokens']['accent'];
        $vars['--sukoon-link'] = $vars['--sukoon-link-on-light'] ?? $normalized['tokens']['link'];

        return $vars;
    }

    public static function cssBlock(array $payload): string
    {
        $lines = [];
        foreach (self::cssVariables($payload) as $name => $value) {
            $lines[] = sprintf('%s: %s;', $name, $value);
        }

        return ':root { ' . implode(' ', $lines) . ' }';
    }

    private static function normalizeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtoupper($value);
        }

        return '#1F2937';
    }

    private static function normalizeRadius(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d+(\.\d+)?(px|rem)$/', $value)) {
            return $value;
        }

        return '16px';
    }

    private static function normalizeShadowIntensity(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['soft', 'medium', 'strong'], true) ? $value : 'medium';
    }
}
