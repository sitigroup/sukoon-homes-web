<?php

namespace App\Plugins\Whatsapp\Support;

class WhatsappPhoneHelper
{
    /** Default country calling code when numbers are stored as 10-digit local (India). */
    public const DEFAULT_COUNTRY_CODE = '91';

    public static function digitsOnly(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? $digits : '';
    }

    /**
     * Meta WhatsApp "to" field: digits only, with country code when missing.
     */
    public static function toMetaRecipient(?string $phone, string $defaultCountryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        $digits = self::digitsOnly($phone);
        if ($digits === '') {
            return '';
        }

        if ($defaultCountryCode !== '' && strlen($digits) === 10 && ! str_starts_with($digits, $defaultCountryCode)) {
            return $defaultCountryCode.$digits;
        }

        return $digits;
    }
}
