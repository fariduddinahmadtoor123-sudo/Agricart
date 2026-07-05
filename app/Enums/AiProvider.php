<?php

namespace App\Enums;

enum AiProvider: string
{
    case OpenRouter = 'openrouter';
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::OpenRouter => __('agricart.ai.providers.openrouter'),
            self::OpenAi => __('agricart.ai.providers.openai'),
            self::Anthropic => __('agricart.ai.providers.anthropic'),
            self::Google => __('agricart.ai.providers.google'),
        };
    }

    public function isEnabled(): bool
    {
        return match ($this) {
            self::OpenRouter => true,
            default => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function selectable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $provider): bool => $provider->isEnabled(),
        ));
    }
}
