<?php

namespace App\Services\Backup;

readonly class BackupEntry
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $filename,
        public int $sizeBytes,
        public string $createdAt,
        public int $filesCount = 0,
        public array $meta = [],
    ) {}

    public function formattedSize(): string
    {
        return self::formatBytes($this->sizeBytes);
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    public static function phpRequestBodyLimitBytes(): int
    {
        return min(
            self::parseIniSize(ini_get('upload_max_filesize')),
            self::parseIniSize(ini_get('post_max_size')),
        );
    }

    public static function effectiveUploadLimitBytes(): int
    {
        $configured = (int) config('backup.max_file_bytes', 0);

        if ($configured <= 0) {
            return self::phpRequestBodyLimitBytes();
        }

        return min($configured, self::phpRequestBodyLimitBytes());
    }

    public static function parseIniSize(string|false $value): int
    {
        if (! is_string($value) || $value === '') {
            return 0;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        if ($unit === 'g') {
            return (int) round($number * 1024 * 1024 * 1024);
        }

        if ($unit === 'm') {
            return (int) round($number * 1024 * 1024);
        }

        if ($unit === 'k') {
            return (int) round($number * 1024);
        }

        return (int) round((float) $value);
    }
}
