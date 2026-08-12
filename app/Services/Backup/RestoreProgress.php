<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;

class RestoreProgress
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array{token?: string, step?: string, status?: string, backup_id?: string, started_at?: string, started_unix?: int, updated_at?: string}|null  $payload
     */
    public function start(string $backupId, string $token): void
    {
        $this->write([
            'token' => $token,
            'backup_id' => $backupId,
            'step' => 'starting',
            'status' => self::STATUS_RUNNING,
            'started_at' => now()->toIso8601String(),
            'started_unix' => time(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function step(string $step, string $status = self::STATUS_RUNNING): void
    {
        $current = $this->readRaw() ?? [];

        $current['step'] = $step;
        $current['status'] = $status;
        $current['updated_at'] = now()->toIso8601String();

        if (! isset($current['started_unix'])) {
            $current['started_unix'] = time();
            $current['started_at'] = now()->toIso8601String();
        }

        $this->write($current);
    }

    /**
     * @return array{active: bool, step?: string, status?: string, started_at?: string, elapsed_seconds?: int}
     */
    public function snapshotForToken(string $token): array
    {
        if (! self::isValidToken($token)) {
            return ['active' => false];
        }

        $payload = $this->readRaw();

        if (! is_array($payload) || ! hash_equals((string) ($payload['token'] ?? ''), $token)) {
            return ['active' => false];
        }

        $startedUnix = (int) ($payload['started_unix'] ?? time());

        return [
            'active' => true,
            'step' => (string) ($payload['step'] ?? 'starting'),
            'status' => (string) ($payload['status'] ?? self::STATUS_RUNNING),
            'started_at' => (string) ($payload['started_at'] ?? ''),
            'elapsed_seconds' => max(0, time() - $startedUnix),
        ];
    }

    public function clear(): void
    {
        if (is_file($this->path())) {
            @unlink($this->path());
        }
    }

    public static function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^[0-9a-fA-F-]{32,36}$/', $token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readRaw(): ?array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(array $payload): void
    {
        $directory = dirname($this->path());
        File::ensureDirectoryExists($directory, 0700);
        File::put($this->path(), json_encode($payload, JSON_UNESCAPED_SLASHES)."\n", true);
        @chmod($this->path(), 0600);
    }

    public function path(): string
    {
        return storage_path('app/private/restore-progress.json');
    }
}
