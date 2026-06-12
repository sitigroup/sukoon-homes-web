<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WaSetting extends Model
{
    protected $table = 'wa_settings';

    protected $guarded = [];

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setAppSecretAttribute(?string $value): void
    {
        $this->attributes['app_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAppSecretAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

