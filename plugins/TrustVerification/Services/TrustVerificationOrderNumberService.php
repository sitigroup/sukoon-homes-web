<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TrustVerificationOrderNumberService
{
    public const FORMAT_RANDOM = 'random';

    public const FORMAT_SEQUENTIAL = 'sequential';

    public const FORMAT_YEAR_SEQUENCE = 'year_sequence';

    public const FORMAT_CITY_SEQUENCE = 'city_sequence';

    /** @var array<string, string> */
    public const FORMAT_LABELS = [
        self::FORMAT_RANDOM => 'Random code (TV-ABCDEFGH)',
        self::FORMAT_SEQUENTIAL => 'Sequential (TV-000001)',
        self::FORMAT_YEAR_SEQUENCE => 'Year + sequence (TV-2026-000001)',
        self::FORMAT_CITY_SEQUENCE => 'City + sequence (TV-BMR-000001)',
    ];

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        return [
            'order_number_prefix' => strtoupper((string) TrustVerificationSettingsService::get('order_number_prefix', 'TV')),
            'order_number_format' => (string) TrustVerificationSettingsService::get('order_number_format', self::FORMAT_RANDOM),
            'order_number_next_sequence' => max(1, (int) TrustVerificationSettingsService::get('order_number_next_sequence', 1)),
            'order_number_digits' => max(4, min(12, (int) TrustVerificationSettingsService::get('order_number_digits', 6))),
            'order_number_separator' => (string) TrustVerificationSettingsService::get('order_number_separator', '-'),
        ];
    }

    public static function normalizePrefix(string $prefix): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?? '');

        if ($normalized === '' || strlen($normalized) > 8) {
            throw new InvalidArgumentException('Order prefix must be 1–8 uppercase letters or numbers.');
        }

        if (! preg_match('/^[A-Z0-9]+$/', $normalized)) {
            throw new InvalidArgumentException('Order prefix may only contain letters and numbers.');
        }

        return $normalized;
    }

    public static function cityCodeFromSlug(?string $citySlug): string
    {
        $slug = preg_replace('/[^a-z]/', '', strtolower(trim((string) $citySlug)));
        if ($slug === '') {
            return 'GEN';
        }

        $consonants = preg_replace('/[aeiou]/', '', $slug) ?? '';
        $code = strtoupper(substr($consonants !== '' ? $consonants : $slug, 0, 3));

        return str_pad($code, 3, 'X');
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public static function preview(?array $settings = null, ?string $citySlug = 'barmer', ?int $sequence = null): string
    {
        $config = $settings ?? self::settings();
        $seq = max(1, (int) ($sequence ?? $config['order_number_next_sequence'] ?? 1));

        return self::composeNumber($config, $seq, $citySlug, true);
    }

    public static function generate(?string $citySlug = null): string
    {
        return DB::transaction(function () use ($citySlug) {
            $config = self::settings();
            $format = $config['order_number_format'];

            if ($format === self::FORMAT_RANDOM) {
                do {
                    $number = self::composeNumber($config, 0, $citySlug, true);
                } while (TvOrder::where('order_number', $number)->exists());

                return $number;
            }

            $setting = TvSetting::query()
                ->where('key', 'order_number_next_sequence')
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                TvSetting::query()->create([
                    'key' => 'order_number_next_sequence',
                    'value' => (string) max(1, (int) $config['order_number_next_sequence']),
                ]);
                $setting = TvSetting::query()
                    ->where('key', 'order_number_next_sequence')
                    ->lockForUpdate()
                    ->first();
            }

            $seq = max(1, (int) $setting->value);
            $attempts = 0;

            do {
                $number = self::composeNumber($config, $seq, $citySlug, false);
                $exists = TvOrder::where('order_number', $number)->exists();

                if ($exists) {
                    $seq++;
                    $attempts++;
                }
            } while ($exists && $attempts < 100);

            if ($exists) {
                throw new \RuntimeException('Unable to allocate a unique verification order number.');
            }

            $setting->value = (string) ($seq + 1);
            $setting->save();

            return $number;
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function composeNumber(array $config, int $sequence, ?string $citySlug, bool $previewRandom): string
    {
        $prefix = self::normalizePrefix((string) ($config['order_number_prefix'] ?? 'TV'));
        $sep = self::separator($config['order_number_separator'] ?? '-');
        $digits = max(4, min(12, (int) ($config['order_number_digits'] ?? 6)));
        $format = (string) ($config['order_number_format'] ?? self::FORMAT_RANDOM);
        $seq = max(1, $sequence);
        $padded = str_pad((string) $seq, $digits, '0', STR_PAD_LEFT);

        return match ($format) {
            self::FORMAT_SEQUENTIAL => $prefix.$sep.$padded,
            self::FORMAT_YEAR_SEQUENCE => $prefix.$sep.date('Y').$sep.$padded,
            self::FORMAT_CITY_SEQUENCE => $prefix.$sep.self::cityCodeFromSlug($citySlug).$sep.$padded,
            default => $prefix.$sep.($previewRandom ? strtoupper(Str::random(8)) : strtoupper(Str::random(8))),
        };
    }

    private static function separator(mixed $value): string
    {
        $sep = (string) $value;

        return $sep !== '' ? substr($sep, 0, 1) : '-';
    }
}
