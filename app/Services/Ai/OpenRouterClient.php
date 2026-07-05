<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Services\Ai\Contracts\AiProviderClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenRouterClient implements AiProviderClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function fetchModels(string $apiKey): array
    {
        $response = Http::withHeaders($this->headers($apiKey))
            ->timeout(20)
            ->get(self::BASE_URL . '/models');

        $response->throw();

        return collect($response->json('data', []))
            ->map(fn (array $model): array => [
                'id' => (string) ($model['id'] ?? ''),
                'name' => (string) ($model['name'] ?? $model['id'] ?? ''),
                'description' => filled($model['description'] ?? null)
                    ? Str::limit(strip_tags((string) $model['description']), 120)
                    : null,
            ])
            ->filter(fn (array $model): bool => filled($model['id']))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function testConnection(string $apiKey, string $modelId): array
    {
        try {
            $response = Http::withHeaders($this->headers($apiKey))
                ->timeout(25)
                ->post(self::BASE_URL . '/chat/completions', [
                    'model' => $modelId,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Reply with exactly: OK',
                        ],
                    ],
                    'max_tokens' => 8,
                    'temperature' => 0,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                    'http_status' => $response->status(),
                ];
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            return [
                'success' => true,
                'message' => filled($content)
                    ? __('agricart.ai.messages.connected_reply', ['reply' => Str::limit($content, 80)])
                    : __('agricart.ai.messages.connected_ok'),
                'response' => $content,
                'http_status' => $response->status(),
            ];
        } catch (RequestException $exception) {
            return [
                'success' => false,
                'message' => $this->extractErrorMessage(
                    $exception->response?->json(),
                    $exception->response?->status() ?? 500,
                ),
                'http_status' => $exception->response?->status(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'http_status' => null,
            ];
        }
    }

    public function chat(string $apiKey, string $modelId, string $prompt): array
    {
        try {
            $response = Http::withHeaders($this->headers($apiKey))
                ->timeout(45)
                ->post(self::BASE_URL . '/chat/completions', [
                    'model' => $modelId,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 300,
                    'temperature' => 0.4,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                    'response' => null,
                    'http_status' => $response->status(),
                ];
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            if (! filled($content)) {
                return [
                    'success' => false,
                    'message' => __('agricart.ai.messages.empty_response'),
                    'response' => null,
                    'http_status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'message' => __('agricart.ai.messages.response_received'),
                'response' => $content,
                'http_status' => $response->status(),
            ];
        } catch (RequestException $exception) {
            return [
                'success' => false,
                'message' => $this->extractErrorMessage(
                    $exception->response?->json(),
                    $exception->response?->status() ?? 500,
                ),
                'response' => null,
                'http_status' => $exception->response?->status(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => config('app.url', 'http://agricart.test'),
            'X-Title' => config('app.name', 'Agricart'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractErrorMessage(?array $payload, int $status): string
    {
        $message = data_get($payload, 'error.message')
            ?? data_get($payload, 'message')
            ?? __('agricart.ai.messages.request_failed', ['status' => $status]);

        return (string) $message;
    }
}
