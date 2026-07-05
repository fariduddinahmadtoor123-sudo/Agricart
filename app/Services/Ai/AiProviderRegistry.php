<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Models\AiConfiguration;
use App\Services\Ai\Contracts\AiProviderClient;
use InvalidArgumentException;

class AiProviderRegistry
{
    public function client(AiProvider $provider): AiProviderClient
    {
        return match ($provider) {
            AiProvider::OpenRouter => app(OpenRouterClient::class),
            default => throw new InvalidArgumentException('Provider [' . $provider->value . '] is not enabled yet.'),
        };
    }

    /**
     * @return list<array{value: string, label: string, enabled: bool}>
     */
    public function providerOptions(): array
    {
        return array_map(
            fn (AiProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
                'enabled' => $provider->isEnabled(),
            ],
            AiProvider::cases(),
        );
    }
}
