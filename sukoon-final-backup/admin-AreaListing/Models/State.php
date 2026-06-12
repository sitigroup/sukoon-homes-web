<?php

namespace App\Plugins\AreaListing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class State extends Model
{
    protected $table = 'area_listing_states';

    protected $fillable = ['name', 'slug', 'country', 'sort_order', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function cities()
    {
        return $this->hasMany(City::class, 'state_id');
    }

    protected static function booted(): void
    {
        static::saving(function (State $state) {
            $state->name = self::clean($state->name);
            $state->country = self::clean($state->country ?: 'India');
            $state->slug = $state->slug ?: Str::slug($state->name);
        });
    }

    private static function clean($value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $value === '' ? '' : Str::title(Str::lower($value));
    }
}