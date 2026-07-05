<?php

namespace App\Models;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_configuration_id',
        'user_id',
        'provider',
        'model_id',
        'model_name',
        'type',
        'status',
        'source',
        'http_status',
        'duration_ms',
        'prompt',
        'response',
        'error_message',
        'metadata',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiLogType::class,
            'status' => AiLogStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AiConfiguration::class, 'ai_configuration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === AiLogStatus::Success;
    }

    public function providerLabel(): string
    {
        if ($this->provider === null) {
            return '—';
        }

        $provider = AiProvider::tryFrom($this->provider);

        return $provider?->label() ?? $this->provider;
    }

    public function modelLabel(): string
    {
        return $this->model_name ?: ($this->model_id ?: '—');
    }
}
