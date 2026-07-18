<?php

namespace App\Services\SystemErrors;

use Illuminate\Support\Facades\File;
use Throwable;

class SystemLogFileReader
{
    private const MAX_BYTES_PER_FILE = 2_000_000;

    /**
     * @return list<array{key: string, file: string, timestamp: string|null, severity: string, message: string, trace: string}>
     */
    public function recent(int $limit = 100): array
    {
        $limit = max(1, min($limit, 200));
        $files = collect(File::files(storage_path('logs')))
            ->filter(fn (\SplFileInfo $file): bool => (bool) preg_match('/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/', $file->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
            ->take(15);
        $entries = [];

        foreach ($files as $file) {
            foreach ($this->entriesFromFile($file->getPathname()) as $entry) {
                $entries[] = $entry;

                if (count($entries) >= $limit) {
                    break 2;
                }
            }
        }

        usort(
            $entries,
            fn (array $left, array $right): int => strcmp($right['timestamp'] ?? '', $left['timestamp'] ?? ''),
        );

        return array_slice($entries, 0, $limit);
    }

    /**
     * @return list<array{key: string, file: string, timestamp: string|null, severity: string, message: string, trace: string}>
     */
    private function entriesFromFile(string $path): array
    {
        try {
            $size = File::size($path);
            $offset = max(0, $size - self::MAX_BYTES_PER_FILE);
            $contents = file_get_contents($path, false, null, $offset);

            if (! is_string($contents) || $contents === '') {
                return [];
            }

            if ($offset > 0 && ($firstEntry = strpos($contents, "\n[")) !== false) {
                $contents = substr($contents, $firstEntry + 1);
            }

            $blocks = preg_split('/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', $contents) ?: [];
            $entries = [];

            foreach (array_reverse($blocks) as $block) {
                $entry = $this->parseBlock(trim($block), basename($path));

                if ($entry !== null) {
                    $entries[] = $entry;
                }
            }

            return $entries;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{key: string, file: string, timestamp: string|null, severity: string, message: string, trace: string}|null
     */
    private function parseBlock(string $block, string $file): ?array
    {
        if (! preg_match('/^\[(?<timestamp>[^\]]+)\]\s+\S+\.(?<level>[A-Z]+):\s*(?<message>.*)$/s', $block, $matches)) {
            return null;
        }

        $level = strtolower((string) $matches['level']);

        if (! in_array($level, ['warning', 'error', 'critical', 'alert', 'emergency'], true)) {
            return null;
        }

        $sanitizer = app(SystemErrorSanitizer::class);
        $trace = $sanitizer->trace((string) $matches['message']) ?? '';
        $message = trim(strtok($trace, "\n") ?: $trace);

        return [
            'key' => hash('sha256', $file.'|'.$matches['timestamp'].'|'.$message),
            'file' => $file,
            'timestamp' => (string) $matches['timestamp'],
            'severity' => in_array($level, ['critical', 'alert', 'emergency'], true) ? 'critical' : $level,
            'message' => $sanitizer->message($message, 500) ?? '',
            'trace' => $trace,
        ];
    }
}
