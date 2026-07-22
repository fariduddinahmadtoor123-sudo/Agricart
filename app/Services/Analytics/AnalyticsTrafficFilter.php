<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;

class AnalyticsTrafficFilter
{
    public function shouldSkip(Request $request): bool
    {
        if (auth('web')->check()) {
            return true;
        }

        if (
            $request->headers->get('Sec-GPC') === '1'
            || $request->headers->get('DNT') === '1'
        ) {
            return true;
        }

        return $this->isExcludedIp($request->ip());
    }

    public function isExcludedIp(?string $ip): bool
    {
        if (! is_string($ip) || $ip === '') {
            return false;
        }

        foreach (config('analytics.exclude_ips', []) as $rule) {
            if (! is_string($rule) || $rule === '') {
                continue;
            }

            if ($rule === $ip || str_starts_with($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    public function geoIpDatabaseReady(): bool
    {
        $database = (string) config('analytics.geoip_database');

        return $database !== '' && is_file($database) && is_readable($database);
    }
}
