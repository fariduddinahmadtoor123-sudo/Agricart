<?php

namespace App\Services\Ai;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Models\AiConfiguration;
use App\Models\AiLog;
use Illuminate\Support\Facades\Auth;

class AiLogService
{
    /**
     * @param  array{
     *     ai_configuration_id?: int|null,
     *     provider?: string|null,
     *     model_id?: string|null,
     *     model_name?: string|null,
     *     type: AiLogType,
     *     status: AiLogStatus,
     *     source: string,
     *     http_status?: int|null,
     *     duration_ms?: int|null,
     *     prompt?: string|null,
     *     response?: string|null,
     *     error_message?: string|null,
     *     metadata?: array<string, mixed>|null,
     * }  $data
     */
    public function record(array $data): AiLog
    {
        return AiLog::query()->create([
            'ai_configuration_id' => $data['ai_configuration_id'] ?? null,
            'user_id' => Auth::id(),
            'provider' => $data['provider'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'model_name' => $data['model_name'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'source' => $data['source'],
            'http_status' => $data['http_status'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'prompt' => $data['prompt'] ?? null,
            'response' => $data['response'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function fromConfiguration(
        ?AiConfiguration $configuration,
        AiLogType $type,
        string $source,
        bool $success,
        ?string $prompt = null,
        ?string $response = null,
        ?string $errorMessage = null,
        ?int $httpStatus = null,
        ?int $durationMs = null,
        ?array $metadata = null,
    ): AiLog {
        return $this->record([
            'ai_configuration_id' => $configuration?->id,
            'provider' => $configuration?->provider->value,
            'model_id' => $configuration?->model_id,
            'model_name' => $configuration?->displayName(),
            'type' => $type,
            'status' => $success ? AiLogStatus::Success : AiLogStatus::Error,
            'source' => $source,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'prompt' => $prompt,
            'response' => $response,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }
}
