<?php

namespace App\Services\FxLearn;

use App\Models\FxLearnApiCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FinnhubCredentialService
{
    public const PROVIDER = 'finnhub';

    public function active(): ?FxLearnApiCredential
    {
        $row = FxLearnApiCredential::query()
            ->where('provider', self::PROVIDER)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($row === null || ! filled($row->api_key)) {
            return null;
        }

        return $row;
    }

    public function resolveToken(): ?string
    {
        $active = $this->active();

        if ($active !== null) {
            return $active->api_key;
        }

        $env = trim((string) config('services.finnhub.key', ''));

        return $env !== '' ? $env : null;
    }

    public function hasToken(): bool
    {
        return $this->resolveToken() !== null;
    }

    /**
     * @return array{
     *     has_token: bool,
     *     source: 'database'|'env'|'none',
     *     connected: bool,
     *     limited: bool,
     *     masked: string|null,
     *     message: string|null,
     *     last_tested_at: string|null
     * }
     */
    public function statusPayload(): array
    {
        $active = $this->active();

        if ($active !== null) {
            $status = (string) $active->connection_status;

            return [
                'has_token' => true,
                'source' => 'database',
                'connected' => in_array($status, ['connected', 'limited'], true),
                'limited' => $status === 'limited',
                'masked' => $active->maskedKey(),
                'message' => $active->last_test_message,
                'last_tested_at' => $active->last_tested_at?->timezone(agricart_display_timezone())->format('Y-m-d H:i'),
            ];
        }

        if (trim((string) config('services.finnhub.key', '')) !== '') {
            return [
                'has_token' => true,
                'source' => 'env',
                'connected' => true,
                'limited' => false,
                'masked' => '••••env',
                'message' => __('agricart.pages.fx-learn.finnhub_using_env'),
                'last_tested_at' => null,
            ];
        }

        return [
            'has_token' => false,
            'source' => 'none',
            'connected' => false,
            'limited' => false,
            'masked' => null,
            'message' => null,
            'last_tested_at' => null,
        ];
    }

    /**
     * @return array{ok: bool, message: string, status?: string}
     */
    public function saveAndTest(string $apiKey, ?string $label = null): array
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            return ['ok' => false, 'message' => __('agricart.pages.fx-learn.finnhub_key_required')];
        }

        $probe = $this->probe($apiKey);
        $status = (string) ($probe['status'] ?? ($probe['ok'] ? 'connected' : 'failed'));

        DB::transaction(function () use ($apiKey, $label, $probe, $status): void {
            FxLearnApiCredential::query()
                ->where('provider', self::PROVIDER)
                ->update(['is_active' => false]);

            FxLearnApiCredential::query()->create([
                'provider' => self::PROVIDER,
                'label' => filled(trim((string) $label)) ? trim((string) $label) : 'Finnhub',
                'api_key' => $apiKey,
                'key_hint' => substr($apiKey, -4),
                'is_active' => true,
                'connection_status' => $status,
                'last_test_message' => $probe['message'],
                'last_tested_at' => now(),
            ]);
        });

        return [
            'ok' => $probe['ok'],
            'message' => $probe['ok']
                ? ($status === 'limited'
                    ? __('agricart.pages.fx-learn.finnhub_saved_limited')
                    : __('agricart.pages.fx-learn.finnhub_saved_ok'))
                : $probe['message'],
            'status' => $status,
        ];
    }

    /**
     * @return array{ok: bool, message: string, status?: string}
     */
    public function testActive(): array
    {
        $token = $this->resolveToken();

        if ($token === null) {
            return ['ok' => false, 'message' => __('agricart.pages.fx-learn.finnhub_no_key')];
        }

        $probe = $this->probe($token);
        $active = $this->active();
        $status = (string) ($probe['status'] ?? ($probe['ok'] ? 'connected' : 'failed'));

        if ($active !== null) {
            $active->forceFill([
                'connection_status' => $status,
                'last_test_message' => $probe['message'],
                'last_tested_at' => now(),
            ])->save();
        }

        return $probe;
    }

    public function deleteActive(): array
    {
        $active = $this->active();

        if ($active === null) {
            return ['ok' => false, 'message' => __('agricart.pages.fx-learn.finnhub_no_key')];
        }

        $active->delete();

        return ['ok' => true, 'message' => __('agricart.pages.fx-learn.finnhub_deleted')];
    }

    /**
     * Validate the API key on a free endpoint, then check Economic Calendar access.
     *
     * @return array{ok: bool, message: string, status: 'connected'|'limited'|'failed'}
     */
    public function probe(string $token): array
    {
        try {
            $timeout = max(5, (int) config('services.finnhub.timeout', 25));
            $http = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders(['X-Finnhub-Token' => $token]);

            // 1) Prove the key itself is valid (free endpoint).
            $quote = $http->get('https://finnhub.io/api/v1/quote', [
                'symbol' => 'AAPL',
            ]);

            if (in_array($quote->status(), [401, 403], true)) {
                return [
                    'ok' => false,
                    'status' => 'failed',
                    'message' => __('agricart.pages.fx-learn.finnhub_auth_failed'),
                ];
            }

            if (! $quote->successful()) {
                return [
                    'ok' => false,
                    'status' => 'failed',
                    'message' => __('agricart.pages.fx-learn.finnhub_http_failed', ['status' => $quote->status()]),
                ];
            }

            // 2) Economic Calendar is a premium endpoint on Finnhub.
            $from = now('UTC')->toDateString();
            $to = now('UTC')->toDateString();
            $calendar = $http->get('https://finnhub.io/api/v1/calendar/economic', [
                'from' => $from,
                'to' => $to,
            ]);

            if (in_array($calendar->status(), [401, 403], true)) {
                $body = strtolower((string) $calendar->body());

                if (str_contains($body, "don't have access") || str_contains($body, 'do not have access')) {
                    return [
                        'ok' => true,
                        'status' => 'limited',
                        'message' => __('agricart.pages.fx-learn.finnhub_calendar_premium'),
                    ];
                }

                return [
                    'ok' => false,
                    'status' => 'failed',
                    'message' => __('agricart.pages.fx-learn.finnhub_auth_failed'),
                ];
            }

            if (! $calendar->successful()) {
                return [
                    'ok' => true,
                    'status' => 'limited',
                    'message' => __('agricart.pages.fx-learn.finnhub_calendar_http', ['status' => $calendar->status()]),
                ];
            }

            $json = $calendar->json();

            if (! is_array($json) || ! array_key_exists('economicCalendar', $json)) {
                return [
                    'ok' => true,
                    'status' => 'limited',
                    'message' => __('agricart.pages.fx-learn.finnhub_bad_payload'),
                ];
            }

            $count = is_array($json['economicCalendar']) ? count($json['economicCalendar']) : 0;

            return [
                'ok' => true,
                'status' => 'connected',
                'message' => __('agricart.pages.fx-learn.finnhub_connected', ['count' => $count]),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'status' => 'failed',
                'message' => __('agricart.pages.fx-learn.finnhub_probe_error', ['error' => $e->getMessage()]),
            ];
        }
    }
}
