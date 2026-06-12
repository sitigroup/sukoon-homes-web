<?php

namespace App\Plugins\Whatsapp\Support;

use App\Plugins\Whatsapp\Models\WaTemplate;

class WaTemplateVariableHelper
{
    /**
     * Highest {{n}} index in BODY component (Meta localizable_params count).
     */
    public static function bodyVariableCount(mixed $templateOrComponents): int
    {
        $components = $templateOrComponents instanceof WaTemplate
            ? $templateOrComponents->components_json
            : $templateOrComponents;

        if (! is_array($components)) {
            return 0;
        }

        $max = 0;
        foreach ($components as $comp) {
            if (strtoupper((string) ($comp['type'] ?? '')) !== 'BODY') {
                continue;
            }
            $text = (string) ($comp['text'] ?? '');
            if (preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
                foreach ($matches[1] as $index) {
                    $max = max($max, (int) $index);
                }
            }
        }

        return $max;
    }

    public static function bodyPreview(mixed $templateOrComponents, int $maxLen = 120): string
    {
        $components = $templateOrComponents instanceof WaTemplate
            ? $templateOrComponents->components_json
            : $templateOrComponents;

        if (! is_array($components)) {
            return '';
        }

        foreach ($components as $comp) {
            if (strtoupper((string) ($comp['type'] ?? '')) !== 'BODY') {
                continue;
            }
            $text = trim((string) ($comp['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            return strlen($text) > $maxLen ? substr($text, 0, $maxLen).'…' : $text;
        }

        return '';
    }

    /**
     * Pad or trim to exactly N BODY variables for Meta API.
     *
     * @param  array<int, string>  $variables
     * @return array<int, string>
     */
    public static function normalizeForTemplate(WaTemplate $template, array $variables): array
    {
        $expected = self::bodyVariableCount($template);
        if ($expected <= 0) {
            return [];
        }

        $normalized = [];
        for ($i = 1; $i <= $expected; $i++) {
            $normalized[] = trim((string) ($variables[$i - 1] ?? ''));
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    public static function collectFromValidated(array $validated, int $expectedCount): array
    {
        $vars = [];
        for ($i = 1; $i <= $expectedCount; $i++) {
            $vars[] = trim((string) ($validated["var_{$i}"] ?? ''));
        }

        return $vars;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRulesForCount(int $expectedCount): array
    {
        $rules = [];
        for ($i = 1; $i <= $expectedCount; $i++) {
            $rules["var_{$i}"] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }
}
