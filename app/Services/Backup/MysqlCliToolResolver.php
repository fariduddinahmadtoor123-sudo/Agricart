<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class MysqlCliToolResolver
{
    /**
     * @var list<string>
     */
    private const MYSQLDUMP_CANDIDATES = [
        'mysqldump',
        'mariadb-dump',
    ];

    /**
     * @var list<string>
     */
    private const MYSQL_CANDIDATES = [
        'mysql',
        'mariadb',
    ];

    public function resolveMysqldump(): ?string
    {
        return $this->resolve(
            (string) config('backup.mysqldump', ''),
            self::MYSQLDUMP_CANDIDATES,
        );
    }

    public function resolveMysql(): ?string
    {
        return $this->resolve(
            (string) config('backup.mysql', ''),
            self::MYSQL_CANDIDATES,
        );
    }

    /**
     * @param  list<string>  $pathCandidates
     */
    private function resolve(string $configured, array $pathCandidates): ?string
    {
        $configured = trim($configured);

        if ($configured !== '' && $this->isRunnable($configured)) {
            return $configured;
        }

        foreach ($pathCandidates as $candidate) {
            if ($configured !== '' && $candidate !== $configured) {
                continue;
            }

            $located = $this->locateOnPath($candidate);

            if ($located !== null) {
                return $located;
            }
        }

        return null;
    }

    private function isRunnable(string $binary): bool
    {
        if ($this->isFilesystemPath($binary)) {
            return is_file($binary) && is_readable($binary);
        }

        return $this->locateOnPath($binary) !== null;
    }

    private function locateOnPath(string $binary): ?string
    {
        if ($this->isFilesystemPath($binary)) {
            return is_file($binary) ? $binary : null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::timeout(15)->run(['where', $binary]);

            if (! $result->successful()) {
                return null;
            }

            foreach (preg_split('/\R/', trim($result->output())) ?: [] as $line) {
                $line = trim($line);

                if ($line !== '' && is_file($line)) {
                    return $line;
                }
            }

            return null;
        }

        $result = Process::timeout(15)->run(['sh', '-c', 'command -v '.escapeshellarg($binary).' 2>/dev/null']);

        if (! $result->successful()) {
            return null;
        }

        $path = trim($result->output());

        return $path !== '' && is_file($path) ? $path : $binary;
    }

    private function isFilesystemPath(string $binary): bool
    {
        return str_contains($binary, '/') || str_contains($binary, '\\');
    }
}
