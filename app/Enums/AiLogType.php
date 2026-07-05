<?php

namespace App\Enums;

enum AiLogType: string
{
    case ConnectionTest = 'connection_test';
    case Prompt = 'prompt';
    case ModelFetch = 'model_fetch';

    public function label(): string
    {
        return match ($this) {
            self::ConnectionTest => __('agricart.ai.logs.types.connection_test'),
            self::Prompt => __('agricart.ai.logs.types.prompt'),
            self::ModelFetch => __('agricart.ai.logs.types.model_fetch'),
        };
    }
}
