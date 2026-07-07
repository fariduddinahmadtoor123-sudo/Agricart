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
}
