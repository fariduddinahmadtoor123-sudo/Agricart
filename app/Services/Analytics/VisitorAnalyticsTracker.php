<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class VisitorAnalyticsTracker
{
    public function __construct(
        private readonly GeoLocationService $geoLocation,
        private readonly DeviceInfoService $devices,
        private readonly TrafficSourceService $trafficSources,
    ) {}

    /**
     * @return array{visitor_token: string, session_token: string}
     */
    public function identity(Request $request): array
    {
        return [
            'visitor_token' => $this->validToken(
                $request->cookie((string) config('analytics.visitor_cookie')),
            ) ?? Str::random(64),
            'session_token' => $this->validToken(
                $request->cookie((string) config('analytics.session_cookie')),
            ) ?? Str::random(64),
        ];
    }

    /**
     * @param  array{visitor_token: string, session_token: string}  $identity
     * @param  array{page_type: ?string, entity_id: ?int, entity_slug: ?string}  $context
     * @return array<string, mixed>
     */
    public function payload(
        Request $request,
        array $identity,
        array $context,
        string $routeName,
        int $responseStatus,
        int $durationMs,
    ): array {
        $device = $this->devices->detect($request->userAgent());
        $source = $this->trafficSources->fromRequest($request);

        return [
            ...$identity,
            ...$context,
            ...$device,
            ...$source,
            'ip' => $request->ip(),
            'route_name' => mb_substr($routeName, 0, 120),
            'response_status' => $responseStatus,
            'duration_ms' => max(0, min($durationMs, 3_600_000)),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(array $payload): void
    {
        try {
            $geo = $this->geoLocation->locate($payload['ip'] ?? null);
            $now = now();

            DB::transaction(function () use ($payload, $geo, $now): void {
                $visitor = AnalyticsVisitor::query()->firstOrCreate(
                    ['token_hash' => $this->tokenHash((string) $payload['visitor_token'])],
                    [
                        'first_seen_at' => $now,
                        'last_seen_at' => $now,
                        'first_country_code' => $geo['country_code'],
                        'first_country_name' => $geo['country_name'],
                        'first_city' => $geo['city'],
                        'first_region_name' => $geo['region_name'],
                        'first_timezone' => $geo['timezone'],
                        'first_device_type' => $payload['device_type'] ?? 'unknown',
                    ],
                );
                $visitor->forceFill(['last_seen_at' => $now])->save();

                $session = AnalyticsSession::query()->firstOrCreate(
                    ['token_hash' => $this->tokenHash((string) $payload['session_token'])],
                    [
                        'visitor_id' => $visitor->id,
                        'started_at' => $now,
                        'last_seen_at' => $now,
                        'landing_route' => $payload['route_name'],
                        'country_code' => $geo['country_code'],
                        'country_name' => $geo['country_name'],
                        'city' => $geo['city'],
                        'region_name' => $geo['region_name'],
                        'region_code' => $geo['region_code'],
                        'timezone' => $geo['timezone'],
                        'latitude' => $geo['latitude'],
                        'longitude' => $geo['longitude'],
                        'masked_ip' => $this->geoLocation->maskedIp($payload['ip'] ?? null),
                        'ip_hash' => $this->geoLocation->hashIp($payload['ip'] ?? null),
                        'device_type' => $payload['device_type'] ?? 'unknown',
                        'browser' => $payload['browser'] ?? null,
                        'operating_system' => $payload['operating_system'] ?? null,
                        'referrer_host' => $payload['referrer_host'] ?? null,
                        'traffic_source' => $payload['source'] ?? 'direct',
                        'utm_source' => $payload['utm_source'] ?? null,
                        'utm_medium' => $payload['utm_medium'] ?? null,
                        'utm_campaign' => $payload['utm_campaign'] ?? null,
                    ],
                );
                $session->forceFill([
                    'visitor_id' => $visitor->id,
                    'last_seen_at' => $now,
                ])->save();
                $session->increment('page_views');

                if (
                    ($payload['page_type'] ?? null) === 'checkout_success'
                    && (int) $session->conversions < 1
                ) {
                    $session->increment('conversions');
                }

                AnalyticsPageView::query()->create([
                    'visitor_id' => $visitor->id,
                    'session_id' => $session->id,
                    'occurred_at' => $now,
                    'route_name' => $payload['route_name'],
                    'page_type' => $payload['page_type'] ?? 'other',
                    'entity_id' => $payload['entity_id'] ?? null,
                    'entity_slug' => $payload['entity_slug'] ?? null,
                    'response_status' => $payload['response_status'] ?? 200,
                    'duration_ms' => $payload['duration_ms'] ?? null,
                    'referrer_host' => $payload['referrer_host'] ?? null,
                    'traffic_source' => $payload['source'] ?? 'direct',
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function tokenHash(string $token): string
    {
        $key = (string) config('analytics.hash_key');

        return hash_hmac('sha256', $token, $key !== '' ? $key : (string) config('app.key'));
    }

    private function validToken(mixed $token): ?string
    {
        return is_string($token)
            && preg_match('/\A[A-Za-z0-9_-]{32,128}\z/', $token) === 1
                ? $token
                : null;
    }
}
