<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvSetting;
use Illuminate\Support\Facades\Schema;

class TrustVerificationSettingsService
{
    public const DOCUMENT_TYPES = [
        'id_front' => 'ID front (Aadhaar / PAN)',
        'id_back' => 'ID back',
        'pan' => 'PAN card',
        'address_proof' => 'Address proof',
        'selfie' => 'Selfie with ID',
    ];

    /** @var array<string, mixed> */
    private static array $defaults = [
        'documents_enabled' => true,
        'documents_required' => false,
        'required_document_types' => ['id_front'],
        'automation_enabled' => true,
        'automation_provider' => 'rules',
        'automation_on_paid' => true,
        'automation_auto_apply' => true,
        'http_api_base_url' => '',
        'http_api_key' => '',
        'http_api_secret' => '',
        'webhook_secret' => '',
        'document_retention_days' => 90,
        'report_retention_days' => 365,
        'delete_cancelled_unpaid_after_days' => 7,
        'order_number_prefix' => 'TV',
        'order_number_format' => 'random',
        'order_number_next_sequence' => 1,
        'order_number_digits' => 6,
        'order_number_separator' => '-',
    ];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        if (! Schema::hasTable('tv_settings')) {
            return self::$defaults;
        }

        $stored = TvSetting::query()->pluck('value', 'key')->all();
        $merged = self::$defaults;

        foreach ($stored as $key => $value) {
            if (! array_key_exists($key, self::$defaults)) {
                continue;
            }

            $boolKeys = [
                'documents_enabled', 'documents_required',
                'automation_enabled', 'automation_on_paid', 'automation_auto_apply',
            ];

            if (in_array($key, $boolKeys, true)) {
                $merged[$key] = in_array($value, ['1', 1, true, 'true'], true);

                continue;
            }

            if ($key === 'required_document_types') {
                $decoded = json_decode((string) $value, true);
                $merged[$key] = is_array($decoded) ? $decoded : self::$defaults[$key];

                continue;
            }

            if (in_array($key, [
                'document_retention_days',
                'report_retention_days',
                'delete_cancelled_unpaid_after_days',
                'order_number_next_sequence',
            ], true)) {
                $merged[$key] = max(1, (int) ($value !== '' ? $value : self::$defaults[$key]));

                continue;
            }

            if ($key === 'order_number_digits') {
                $merged[$key] = max(4, min(12, (int) ($value !== '' ? $value : self::$defaults[$key])));

                continue;
            }

            if ($key === 'order_number_prefix') {
                $merged[$key] = strtoupper((string) ($value !== '' ? $value : self::$defaults[$key]));

                continue;
            }

            $merged[$key] = $value !== '' ? $value : self::$defaults[$key];
        }

        return $merged;
    }

  /** @return array<string, mixed> */
    public static function publicPayload(): array
    {
        $all = self::all();

        return [
            'documents_enabled' => (bool) $all['documents_enabled'],
            'documents_required' => (bool) $all['documents_required'],
            'required_document_types' => array_values((array) ($all['required_document_types'] ?? [])),
            'document_type_labels' => self::DOCUMENT_TYPES,
        ];
    }

    /** @return array<string, mixed> */
    public static function orderNumberPayload(): array
    {
        $all = self::all();

        return [
            'order_number_prefix' => (string) ($all['order_number_prefix'] ?? 'TV'),
            'order_number_format' => (string) ($all['order_number_format'] ?? TrustVerificationOrderNumberService::FORMAT_RANDOM),
            'order_number_next_sequence' => max(1, (int) ($all['order_number_next_sequence'] ?? 1)),
            'order_number_digits' => max(4, min(12, (int) ($all['order_number_digits'] ?? 6))),
            'order_number_separator' => (string) ($all['order_number_separator'] ?? '-'),
            'order_number_preview' => TrustVerificationOrderNumberService::preview(null, 'barmer'),
            'order_number_formats' => TrustVerificationOrderNumberService::FORMAT_LABELS,
        ];
    }

    /** @return array<string, mixed> */
    public static function automationPayload(): array
    {
        $all = self::all();

        return [
            'automation_enabled' => (bool) $all['automation_enabled'],
            'automation_provider' => (string) $all['automation_provider'],
            'automation_on_paid' => (bool) $all['automation_on_paid'],
            'automation_auto_apply' => (bool) $all['automation_auto_apply'],
            'http_api_configured' => self::httpProviderConfigured($all),
            'providers' => TrustVerificationAutomationService::providerOptions(),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        foreach (self::$defaults as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (is_array($value)) {
                $value = json_encode(array_values($value));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } else {
                $value = (string) $value;
            }

            TvSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return $all[$key] ?? $default;
    }

    /** @param array<string, mixed>|null $settings */
    public static function httpProviderConfigured(?array $settings = null): bool
    {
        $settings ??= self::all();

        return trim((string) ($settings['http_api_base_url'] ?? '')) !== ''
            && trim((string) ($settings['http_api_key'] ?? '')) !== '';
    }
}
