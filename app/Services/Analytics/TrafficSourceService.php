<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;

class TrafficSourceService
{
    /**
     * @return array{
     *     source: string,
     *     referrer_host: ?string,
     *     utm_source: ?string,
     *     utm_medium: ?string,
     *     utm_campaign: ?string
     * }
     */
    public function fromRequest(Request $request): array
    {
        $utmSource = $this->bounded($request->query('utm_source'));
        $utmMedium = $this->bounded($request->query('utm_medium'));
        $utmCampaign = $this->bounded($request->query('utm_campaign'));
        $referrerHost = $this->referrerHost($request);

        return [
            'source' => $this->normalize($utmSource, $utmMedium, $referrerHost),
            'referrer_host' => $referrerHost,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
        ];
    }

    private function normalize(?string $utmSource, ?string $utmMedium, ?string $host): string
    {
        $source = mb_strtolower((string) $utmSource);
        $medium = mb_strtolower((string) $utmMedium);
        $haystack = trim($source.' '.mb_strtolower((string) $host));

        return match (true) {
            str_contains($haystack, 'google') => 'google',
            str_contains($haystack, 'facebook') || str_contains($haystack, 'fb.com') => 'facebook',
            str_contains($haystack, 'instagram') => 'instagram',
            str_contains($haystack, 'whatsapp') || str_contains($haystack, 'wa.me') => 'whatsapp',
            str_contains($haystack, 'youtube') || str_contains($haystack, 'youtu.be') => 'youtube',
            str_contains($haystack, 'bing') => 'bing',
            str_contains($medium, 'email') => 'email',
            $utmSource !== null => 'campaign',
            $host !== null => 'referral',
            default => 'direct',
        };
    }

    private function referrerHost(Request $request): ?string
    {
        $referrer = trim((string) $request->headers->get('referer'));

        if ($referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        $requestHost = $request->getHost();

        if (! is_string($host) || $host === '' || strcasecmp($host, $requestHost) === 0) {
            return null;
        }

        return mb_substr(mb_strtolower($host), 0, 255);
    }

    private function bounded(mixed $value): ?string
    {
        return is_string($value) && filled($value)
            ? mb_substr(trim($value), 0, 255)
            : null;
    }
}
