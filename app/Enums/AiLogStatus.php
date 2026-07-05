<?php

namespace App\Enums;

enum AiLogStatus: string
{
    case Success = 'success';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('agricart.ai.logs.statuses.success'),
            self::Error => __('agricart.ai.logs.statuses.error'),
        };
    }
}
