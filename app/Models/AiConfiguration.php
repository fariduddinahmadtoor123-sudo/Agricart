<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * Encrypt/decrypt with graceful handling when APP_KEY no longer matches
     * (e.g. after restoring a backup from another environment).
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return $this->decryptApiKey($value);
            },
            set: function (?string $value): string {
                return Crypt::encrypt((string) ($value ?? ''));
            },
        );
    }

    public function hasUsableApiKey(): bool
    {
        return filled($this->api_key);
    }

    /**
     * True when a ciphertext exists but cannot be decrypted with the current APP_KEY.
     */
    public function hasCorruptApiKey(): bool
    {
        $raw = $this->getRawOriginal('api_key');

        if (! is_string($raw) || $raw === '') {
            return false;
        }

        return $this->decryptApiKey($raw) === null;
    }

    /**
     * Read both the current serialized encryption format and legacy
     * encryptString() values without allowing corrupt data to break the UI.
     */
    private function decryptApiKey(string $value): ?string
    {
        try {
            $plain = Crypt::decrypt($value);
        } catch (\Throwable) {
            try {
                $plain = Crypt::decryptString($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return is_string($plain) && $plain !== '' ? $plain : null;
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
