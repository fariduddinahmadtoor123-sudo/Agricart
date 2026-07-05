<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;

class AiConfiguration extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'api_key',
        'model_id',
        'model_name',
        'is_active',
        'connection_status',
        'last_test_message',
        'last_tested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function isConnected(): bool
    {
        return $this->connection_status === 'connected';
    }

    public function isPaused(): bool
    {
        return $this->connection_status === 'paused';
    }

    public function displayName(): string
    {
        return $this->model_name ?: $this->model_id;
    }
}
