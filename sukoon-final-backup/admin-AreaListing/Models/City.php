<?php

namespace App\Plugins\AreaListing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    protected $table = 'area_listing_cities';

    protected $fillable = ['state_id', 'name', 'normalized_name', 'slug', 'state', 'country', 'sort_order', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function stateModel()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'city_id');
    }

    protected static function booted(): void
    {
        static::saving(function (City $city) {
            $city->name = self::clean($city->name);
            $city->normalized_name = self::normalized($city->name);
            $city->state = self::clean($city->state);
            $city->country = self::clean($city->country ?: 'India');
            $city->slug = $city->slug ?: Str::slug($city->name);
        });
    }

    private static function clean($value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $value === '' ? '' : Str::title(Str::lower($value));
    }

    private static function normalized($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }
}
