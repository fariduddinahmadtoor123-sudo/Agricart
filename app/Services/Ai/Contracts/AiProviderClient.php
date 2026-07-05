<?php

namespace App\Services\Ai\Contracts;

interface AiProviderClient
{
    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function fetchModels(string $apiKey): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $apiKey, string $modelId): array;

    /**
     * @return array{success: bool, message: string, response: string|null}
     */
    public function chat(string $apiKey, string $modelId, string $prompt): array;
}
