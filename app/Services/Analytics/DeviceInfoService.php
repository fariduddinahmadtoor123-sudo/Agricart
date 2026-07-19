<?php

namespace App\Services\Analytics;

use DeviceDetector\DeviceDetector;

class DeviceInfoService
{
    /**
     * @return array{is_bot: bool, device_type: string, browser: ?string, operating_system: ?string}
     */
    public function detect(?string $userAgent): array
    {
        $userAgent = mb_substr(trim((string) $userAgent), 0, 1000);

        if ($userAgent === '') {
            return [
                'is_bot' => false,
                'device_type' => 'unknown',
                'browser' => null,
                'operating_system' => null,
            ];
        }

        $detector = new DeviceDetector($userAgent);
        $detector->parse();

        if ($detector->isBot()) {
            return [
                'is_bot' => true,
                'device_type' => 'bot',
                'browser' => null,
                'operating_system' => null,
            ];
        }

        $client = $detector->getClient();
        $os = $detector->getOs();

        return [
            'is_bot' => false,
            'device_type' => match (true) {
                $detector->isTablet() => 'tablet',
                $detector->isMobile() => 'mobile',
                $detector->isDesktop() => 'desktop',
                default => 'unknown',
            },
            'browser' => $this->nameAndVersion($client),
            'operating_system' => $this->nameAndVersion($os),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    private function nameAndVersion(?array $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $name = trim((string) ($value['name'] ?? ''));
        $version = trim((string) ($value['version'] ?? ''));
        $label = trim($name.' '.$version);

        return $label !== '' ? mb_substr($label, 0, 80) : null;
    }
}
