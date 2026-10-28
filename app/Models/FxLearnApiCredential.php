<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class FxLearnApiCredential extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'label',
        'api_key',
        'key_hint',
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
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

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
            },
            set: fn (?string $value): string => Crypt::encrypt((string) ($value ?? '')),
        );
    }

    public function isConnected(): bool
    {
        return in_array($this->connection_status, ['connected', 'limited'], true);
    }

    public function hasCalendarAccess(): bool
    {
        return $this->connection_status === 'connected';
    }

    public function maskedKey(): string
    {
        $hint = trim((string) ($this->key_hint ?? ''));

        return $hint !== '' ? '••••'.$hint : '••••';
    }
}
