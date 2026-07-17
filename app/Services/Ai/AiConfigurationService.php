<?php

namespace App\Services\Ai;

use App\Enums\AiLogType;
use App\Enums\AiProvider;
use App\Models\AiConfiguration;
use Illuminate\Support\Facades\DB;

class AiConfigurationService
{
    public function __construct(
        private readonly AiProviderRegistry $registry,
        private readonly AiLogService $logger,
    ) {}

    public function active(): ?AiConfiguration
    {
        $configuration = AiConfiguration::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($configuration === null) {
            return null;
        }

        if ($configuration->hasCorruptApiKey()) {
            $this->markApiKeyUnreadable($configuration);

            return null;
        }

        if (! $configuration->hasUsableApiKey()) {
            return null;
        }

        return $configuration;
    }

    /**
     * After restoring a backup from another environment, ciphertext may not
     * decrypt with this machine's APP_KEY. Clear those keys so the UI asks
     * the user to paste the API key again instead of throwing "MAC is invalid".
     */
    public function sanitizeUnreadableApiKeys(): int
    {
        $cleared = 0;

        foreach (AiConfiguration::query()->get() as $configuration) {
            if (! $configuration->hasCorruptApiKey()) {
                continue;
            }

            $this->markApiKeyUnreadable($configuration);
            $cleared++;
        }

        return $cleared;
    }

    private function markApiKeyUnreadable(AiConfiguration $configuration): void
    {
        $configuration->forceFill([
            'api_key' => '',
            'is_active' => false,
            'connection_status' => 'failed',
            'last_test_message' => __('agricart.ai.messages.api_key_reenter'),
            'last_tested_at' => now(),
        ])->save();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AiConfiguration>
     */
    public function all()
    {
        return AiConfiguration::query()
            ->latest('id')
            ->get();
    }

    /**
     * @param  array{provider: string, api_key: string, model_id: string, model_name?: string|null}  $data
     */
    public function save(array $data): AiConfiguration
    {
        $provider = AiProvider::from($data['provider']);

        return DB::transaction(function () use ($data, $provider): AiConfiguration {
            AiConfiguration::query()->update(['is_active' => false]);

            $configuration = AiConfiguration::query()->create([
                'provider' => $provider,
                'api_key' => $data['api_key'],
                'model_id' => $data['model_id'],
                'model_name' => $data['model_name'] ?? null,
                'is_active' => true,
                'connection_status' => 'untested',
                'last_test_message' => null,
                'last_tested_at' => null,
            ]);

            $this->applyConnectionTest($configuration, 'settings_save');

            return $configuration->fresh();
        });
    }

    public function testConfiguration(AiConfiguration $configuration, string $source = 'settings_manager'): AiConfiguration
    {
        $this->applyConnectionTest($configuration, $source);

        return $configuration->fresh();
    }

    public function pause(AiConfiguration $configuration): AiConfiguration
    {
        $configuration->update([
            'connection_status' => 'paused',
            'last_test_message' => __('agricart.ai.messages.paused_notice'),
        ]);

        return $configuration->fresh();
    }

    public function resume(AiConfiguration $configuration): AiConfiguration
    {
        $this->applyConnectionTest($configuration, 'settings_manager');

        return $configuration->fresh();
    }

    public function delete(AiConfiguration $configuration): void
    {
        $configuration->delete();
    }

    /**
     * @return array{success: bool, message: string, response: string|null, http_status?: int|null}
     */
    public function prompt(AiConfiguration $configuration, string $prompt, string $source = 'settings_manager'): array
    {
        if ($configuration->isPaused()) {
            $result = [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
                'response' => null,
            ];

            $this->logger->fromConfiguration(
                $configuration,
                AiLogType::Prompt,
                $source,
                false,
                $prompt,
                null,
                $result['message'],
            );

            return $result;
        }

        $start = microtime(true);
        $client = $this->registry->client($configuration->provider);
        $result = $client->chat(
            $configuration->api_key,
            $configuration->model_id,
            $prompt,
        );
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->logger->fromConfiguration(
            $configuration,
            AiLogType::Prompt,
            $source,
            $result['success'],
            $prompt,
            $result['response'] ?? null,
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );

        return $result;
    }

    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function fetchModels(AiProvider $provider, string $apiKey, string $source = 'settings_form'): array
    {
        $start = microtime(true);

        try {
            $models = $this->registry->client($provider)->fetchModels($apiKey);
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->logger->record([
                'provider' => $provider->value,
                'type' => AiLogType::ModelFetch,
                'status' => \App\Enums\AiLogStatus::Success,
                'source' => $source,
                'duration_ms' => $durationMs,
                'metadata' => ['model_count' => count($models)],
            ]);

            return $models;
        } catch (\Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->logger->record([
                'provider' => $provider->value,
                'type' => AiLogType::ModelFetch,
                'status' => \App\Enums\AiLogStatus::Error,
                'source' => $source,
                'duration_ms' => $durationMs,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array{success: bool, message: string, response?: string|null, http_status?: int|null}
     */
    public function testDraftConnection(
        AiProvider $provider,
        string $apiKey,
        string $modelId,
        ?string $modelName = null,
        string $source = 'settings_form',
    ): array {
        $start = microtime(true);
        $result = $this->registry->client($provider)->testConnection($apiKey, $modelId);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->logger->record([
            'provider' => $provider->value,
            'model_id' => $modelId,
            'model_name' => $modelName,
            'type' => AiLogType::ConnectionTest,
            'status' => $result['success'] ? \App\Enums\AiLogStatus::Success : \App\Enums\AiLogStatus::Error,
            'source' => $source,
            'http_status' => $result['http_status'] ?? null,
            'duration_ms' => $durationMs,
            'prompt' => 'Reply with exactly: OK',
            'response' => $result['response'] ?? null,
            'error_message' => $result['success'] ? null : $result['message'],
        ]);

        return $result;
    }

    private function applyConnectionTest(AiConfiguration $configuration, string $source): void
    {
        $start = microtime(true);
        $client = $this->registry->client($configuration->provider);

        $result = $client->testConnection(
            $configuration->api_key,
            $configuration->model_id,
        );

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->logger->fromConfiguration(
            $configuration,
            AiLogType::ConnectionTest,
            $source,
            $result['success'],
            'Reply with exactly: OK',
            $result['response'] ?? null,
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );

        $configuration->update([
            'connection_status' => $result['success'] ? 'connected' : 'failed',
            'last_test_message' => $result['message'],
            'last_tested_at' => now(),
        ]);
    }
}
