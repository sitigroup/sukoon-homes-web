<?php

namespace App\Plugins\Whatsapp\Support;

use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaTemplate;

class WaTemplateRenderHelper
{
    public static function bodyTextFromComponents(mixed $components): string
    {
        if (! is_array($components)) {
            return '';
        }

        foreach ($components as $comp) {
            if (strtoupper((string) ($comp['type'] ?? '')) === 'BODY') {
                return trim((string) ($comp['text'] ?? ''));
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>|array<string, string>  $variables
     */
    public static function substitute(string $bodyText, array $variables): string
    {
        if ($bodyText === '') {
            return '';
        }

        $indexed = [];
        $i = 1;
        foreach (array_values($variables) as $value) {
            $indexed[$i] = (string) $value;
            $i++;
        }

        return (string) preg_replace_callback('/\{\{(\d+)\}\}/', function (array $m) use ($indexed) {
            $n = (int) $m[1];

            return $indexed[$n] ?? $m[0];
        }, $bodyText);
    }

    public static function resolveTemplateForMessage(WaMessage $message): ?WaTemplate
    {
        $key = (string) ($message->template_key ?? '');
        if ($key === '') {
            return null;
        }

        $template = WaTemplate::query()->where('internal_key', $key)->first();
        if ($template) {
            return $template;
        }

        $map = WaEventMap::query()->where('event_key', $key)->first();
        if ($map && $map->template_id) {
            return WaTemplate::query()->find($map->template_id);
        }

        return WaTemplate::query()->where('meta_template_name', $key)->first();
    }

    public static function renderForMessage(WaMessage $message): string
    {
        if ($message->type !== 'template') {
            $body = trim((string) ($message->body ?? ''));
            if ($body !== '') {
                return $body;
            }
            if (filled($message->media_url)) {
                return '';
            }

            return '-';
        }

        $raw = (string) ($message->body ?? '');
        $vars = json_decode($raw, true);
        if (! is_array($vars)) {
            $vars = $raw !== '' ? [$raw] : [];
        }

        $template = self::resolveTemplateForMessage($message);
        $bodyText = self::bodyTextFromComponents($template?->components_json);

        if ($bodyText !== '') {
            return self::substitute($bodyText, $vars);
        }

        $label = (string) ($message->template_key ?: __('whatsapp::whatsapp.msg_type_template'));
        if ($vars === []) {
            return $label;
        }

        return $label.' · '.implode(', ', array_map('strval', array_values($vars)));
    }
}
