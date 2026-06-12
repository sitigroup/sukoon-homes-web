<?php

namespace App\Plugins\Theme\Support;

class ThemePresets
{
    public const TOKEN_KEYS = [
        'primary',
        'secondary',
        'accent',
        'sidebar',
        'card',
        'border',
        'button',
        'hover',
        'link',
        'muted',
        'white',
        'section',
    ];

    public static function all(): array
    {
        return [
            'sukoon-pure-black-luxury' => self::sukoonPureBlackLuxury(),
            'modern-white' => self::modernWhite(),
            'dark-premium' => self::darkPremium(),
        ];
    }

    public static function get(string $slug): ?array
    {
        if ($slug === 'sukoon-luxury-black') {
            return self::sukoonPureBlackLuxury();
        }

        return self::all()[$slug] ?? null;
    }

    public static function sukoonPureBlackLuxury(): array
    {
        return [
            'slug' => 'sukoon-pure-black-luxury',
            'name' => 'Sukoon Pure Black Luxury',
            'description' => 'Apple-minimal monochrome — pure black, white, and graphite. Default Sukoon premium.',
            'tokens' => [
                'primary' => '#111827',
                'secondary' => '#1F2937',
                'accent' => '#000000',
                'sidebar' => '#111827',
                'card' => '#FFFFFF',
                'border' => '#E5E7EB',
                'button' => '#111827',
                'hover' => '#000000',
                'link' => '#111827',
                'muted' => '#6B7280',
                'white' => '#FFFFFF',
                'section' => '#F9FAFB',
            ],
            'typography' => 'Manrope, system-ui, -apple-system, sans-serif',
            'border_radius' => '16px',
            'shadow_intensity' => 'soft',
        ];
    }

    /** @deprecated Use sukoonPureBlackLuxury() — alias for legacy slug in DB */
    public static function sukoonLuxuryBlack(): array
    {
        return self::sukoonPureBlackLuxury();
    }

    public static function modernWhite(): array
    {
        return [
            'slug' => 'modern-white',
            'name' => 'Modern White',
            'description' => 'Clean Apple-inspired white with soft graphite accents.',
            'tokens' => [
                'primary' => '#111827',
                'secondary' => '#4B5563',
                'accent' => '#111827',
                'sidebar' => '#F9FAFB',
                'card' => '#FFFFFF',
                'border' => '#E5E7EB',
                'button' => '#111827',
                'hover' => '#000000',
                'link' => '#111827',
                'muted' => '#6B7280',
                'white' => '#FFFFFF',
                'section' => '#F3F4F6',
            ],
            'typography' => 'Manrope, system-ui, -apple-system, sans-serif',
            'border_radius' => '14px',
            'shadow_intensity' => 'soft',
        ];
    }

    public static function darkPremium(): array
    {
        return [
            'slug' => 'dark-premium',
            'name' => 'Dark Premium',
            'description' => 'Deep charcoal surfaces with crisp white type — no color accents.',
            'tokens' => [
                'primary' => '#0F172A',
                'secondary' => '#1E293B',
                'accent' => '#FFFFFF',
                'sidebar' => '#020617',
                'card' => '#1E293B',
                'border' => '#334155',
                'button' => '#F8FAFC',
                'hover' => '#FFFFFF',
                'link' => '#F8FAFC',
                'muted' => '#94A3B8',
                'white' => '#F8FAFC',
                'section' => '#0F172A',
            ],
            'typography' => 'Manrope, system-ui, -apple-system, sans-serif',
            'border_radius' => '16px',
            'shadow_intensity' => 'strong',
        ];
    }

    public static function defaultPreset(): array
    {
        $slug = config('sukoon-theme.default_preset', 'sukoon-pure-black-luxury');

        return self::get($slug) ?? self::sukoonPureBlackLuxury();
    }
}
