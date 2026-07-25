<?php

namespace App\Services\Analytics;

use GeoIp2\Database\Reader;
use Throwable;

class GeoLocationService
{
    private ?Reader $reader = null;

    /**
     * @return array{
     *     country_code: ?string,
     *     country_name: ?string,
     *     region_name: ?string,
     *     region_code: ?string,
     *     city: ?string,
     *     timezone: ?string,
     *     latitude: ?float,
     *     longitude: ?float
     * }
     */
    public function locate(?string $ip): array
    {
        $unknown = [
            'country_code' => null,
            'country_name' => null,
            'region_name' => null,
            'region_code' => null,
            'city' => null,
            'timezone' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        if (! $this->isPublicIp($ip)) {
            return $unknown;
        }

        $database = (string) config('analytics.geoip_database');

        if ($database === '' || ! is_file($database) || ! is_readable($database)) {
            return $unknown;
        }

        try {
            $record = $this->reader()->city((string) $ip);
            $subdivision = $record->mostSpecificSubdivision;
            $latitude = $record->location->latitude;
            $longitude = $record->location->longitude;

            return [
                'country_code' => $this->bounded($record->country->isoCode, 2),
                'country_name' => $this->bounded($record->country->name, 255),
                'region_name' => $this->bounded($subdivision->name, 255),
                'region_code' => $this->bounded($subdivision->isoCode, 16),
                'city' => $this->bounded($record->city->name, 255),
                'timezone' => $this->bounded($record->location->timeZone, 64),
                'latitude' => is_numeric($latitude) ? round((float) $latitude, 7) : null,
                'longitude' => is_numeric($longitude) ? round((float) $longitude, 7) : null,
            ];
        } catch (Throwable) {
            return $unknown;
        }
    }

    public function maskedIp(?string $ip): ?string
    {
        if (! $this->isPublicIp($ip)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', (string) $ip);

            return "{$parts[0]}.{$parts[1]}.{$parts[2]}.xxx";
        }

        $packed = inet_pton((string) $ip);

        if ($packed === false) {
            return null;
        }

        $network = substr($packed, 0, 6).str_repeat("\0", 10);
        $formatted = inet_ntop($network);

        return is_string($formatted) ? $formatted.'/48' : null;
    }

    public function hashIp(?string $ip): ?string
    {
        if (! $this->isPublicIp($ip)) {
            return null;
        }

        return hash_hmac('sha256', (string) $ip, $this->hashKey());
    }

    private function isPublicIp(?string $ip): bool
    {
        return is_string($ip)
            && filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
    }

    private function hashKey(): string
    {
        $key = (string) config('analytics.hash_key');

        return $key !== '' ? $key : (string) config('app.key');
    }

    private function bounded(mixed $value, int $length): ?string
    {
        return is_string($value) && filled($value)
            ? mb_substr(trim($value), 0, $length)
            : null;
    }

    private function reader(): Reader
    {
        if ($this->reader instanceof Reader) {
            return $this->reader;
        }

        $database = (string) config('analytics.geoip_database');

        if ($database === '' || ! is_file($database) || ! is_readable($database)) {
            throw new \RuntimeException('GeoIP database unavailable.');
        }

        return $this->reader = new Reader($database);
    }
}
