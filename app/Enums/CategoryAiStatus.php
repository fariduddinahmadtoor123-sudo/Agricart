<?php

namespace App\Enums;

enum CategoryAiStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Enriched = 'enriched';
    case Failed = 'failed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::None => __('agricart.categories.ai_status.none'),
            self::Pending => __('agricart.categories.ai_status.pending'),
            self::Enriched => __('agricart.categories.ai_status.enriched'),
            self::Failed => __('agricart.categories.ai_status.failed'),
            self::Manual => __('agricart.categories.ai_status.manual'),
        };
    }
}
