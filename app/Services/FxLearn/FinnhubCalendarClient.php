<?php

namespace App\Services\FxLearn;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinnhubCalendarClient
{
    public function __construct(
        protected FinnhubCredentialService $credentials,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     events: list<array<string, mixed>>,
     *     error: string|null,
     *     meta: array{
     *         from: string,
     *         to: string,
     *         http_status: int|null,
     *         has_token: bool,
     *         token_source: string,
     *         raw_count: int
     *     }
     * }
     */
    public function fetchRange(string $fromYmd, string $toYmd): array
    {
        $status = $this->credentials->statusPayload();
        $token = $this->credentials->resolveToken();

        $meta = [
            'from' => $fromYmd,
            'to' => $toYmd,
            'http_status' => null,
            'has_token' => $token !== null,
            'token_source' => $status['source'],
            'raw_count' => 0,
        ];

        if ($token === null) {
            Log::warning('fx-learn.finnhub.no_token', $meta);

            return [
                'ok' => false,
                'events' => [],
                'error' => __('agricart.pages.fx-learn.finnhub_no_key'),
                'meta' => $meta,
            ];
        }

        try {
            $timeout = max(5, (int) config('services.finnhub.timeout', 25));
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders(['X-Finnhub-Token' => $token])
                ->get('https://finnhub.io/api/v1/calendar/economic', [
                    'from' => $fromYmd,
                    'to' => $toYmd,
                ]);

            $meta['http_status'] = $response->status();

            if (in_array($response->status(), [401, 403], true)) {
                Log::warning('fx-learn.finnhub.auth', array_merge($meta, [
                    'body' => substr((string) $response->body(), 0, 200),
                ]));

                $body = strtolower((string) $response->body());
                $error = (str_contains($body, "don't have access") || str_contains($body, 'do not have access'))
                    ? __('agricart.pages.fx-learn.finnhub_calendar_premium')
                    : __('agricart.pages.fx-learn.finnhub_auth_failed');

                return [
                    'ok' => false,
                    'events' => [],
                    'error' => $error,
                    'meta' => $meta,
                ];
            }

            if (! $response->successful()) {
                Log::warning('fx-learn.finnhub.http', array_merge($meta, [
                    'body' => substr((string) $response->body(), 0, 400),
                ]));

                return [
                    'ok' => false,
                    'events' => [],
                    'error' => __('agricart.pages.fx-learn.finnhub_http_failed', ['status' => $response->status()]),
                    'meta' => $meta,
                ];
            }

            $json = $response->json();
            $rows = is_array($json) ? ($json['economicCalendar'] ?? null) : null;

            if (! is_array($rows)) {
                Log::warning('fx-learn.finnhub.bad_payload', $meta);

                return [
                    'ok' => false,
                    'events' => [],
                    'error' => __('agricart.pages.fx-learn.finnhub_bad_payload'),
                    'meta' => $meta,
                ];
            }

            $meta['raw_count'] = count($rows);

            Log::info('fx-learn.finnhub.ok', $meta);

            return [
                'ok' => true,
                'events' => $rows,
                'error' => null,
                'meta' => $meta,
            ];
        } catch (Throwable $e) {
            Log::error('fx-learn.finnhub.exception', array_merge($meta, [
                'message' => $e->getMessage(),
            ]));

            return [
                'ok' => false,
                'events' => [],
                'error' => __('agricart.pages.fx-learn.finnhub_probe_error', ['error' => $e->getMessage()]),
                'meta' => $meta,
            ];
        }
    }
}
