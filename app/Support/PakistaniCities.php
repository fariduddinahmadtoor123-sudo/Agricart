<?php

namespace App\Support;

class PakistaniCities
{
    public static function english(string $cityKey): string
    {
        if ($cityKey === '') {
            return '';
        }

        $city = config('pakistani_cities.' . $cityKey);

        return is_array($city) ? (string) ($city['en'] ?? '') : '';
    }

    public static function urdu(string $cityKey): string
    {
        if ($cityKey === '') {
            return '';
        }

        $override = config('pakistani_city_ur.' . $cityKey);

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return self::english($cityKey);
    }

    /**
     * @return array<string, string>
     */
    public static function urduMap(): array
    {
        return config('pakistani_city_ur', []);
    }

    public static function guessUrdu(string $english, string $province = ''): string
    {
        return $english;
    }
}
