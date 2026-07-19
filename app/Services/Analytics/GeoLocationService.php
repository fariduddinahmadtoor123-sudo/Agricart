<?php

namespace App\Services\Analytics;

use GeoIp2\Database\Reader;
use Throwable;

class GeoLocationService
{
    /**
     * @return array{country_code: ?string, country_name: ?string, city: ?string}
     */
    public function locate(?string $ip): array
    {
        $unknown = [
            'country_code' => null,
            'country_name' => null,
            'city' => null,
        ];

        if (! $this->isPublicIp($ip)) {
            return $unknown;
        }

        $database = (string) config('analytics.geoip_database');

        if ($database === '' || ! is_file($database) || ! is_readable($database)) {
            return $unknown;
        }

        try {
            $record = (new Reader($database))->city((string) $ip);

            return [
                'country_code' => $this->bounded($record->country->isoCode, 2),
                'country_name' => $this->bounded($record->country->name, 255),
                'city' => $this->bounded($record->city->name, 255),
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
}
