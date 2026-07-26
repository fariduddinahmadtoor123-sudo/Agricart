<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

class DatabaseDumper
{
    public function __construct(
        private readonly MysqlCliToolResolver $cliTools,
        private readonly PhpMysqlDumper $phpDumper,
        private readonly MysqlClientCredentialsFile $credentialsFiles,
    ) {}

    public function dumpTo(string $targetPath): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        File::ensureDirectoryExists(dirname($targetPath));

        if ($driver === 'sqlite') {
            $this->dumpSqlite($targetPath, config("database.connections.{$connection}.database"));

            return;
        }

        if ($driver !== 'mysql') {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.unsupported_driver', ['driver' => $driver]),
            ]);
        }

        $this->dumpMysql($targetPath, config("database.connections.{$connection}"));
    }

    private function dumpSqlite(string $targetPath, string $databasePath): void
    {
        if (! is_file($databasePath)) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.database_missing'),
            ]);
        }

        if (! copy($databasePath, $targetPath)) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysql(string $targetPath, array $config): void
    {
        $method = (string) config('backup.mysql_dump_method', 'auto');

        if ($method === 'php') {
            $this->phpDumper->dumpTo($targetPath, $config);

            return;
        }

        $binary = $this->cliTools->resolveMysqldump();

        if ($method === 'cli') {
            if ($binary === null) {
                throw ValidationException::withMessages([
                    'backup' => __('agricart.backups.messages.dump_cli_unavailable'),
                ]);
            }

            $this->dumpMysqlViaCli($targetPath, $config, $binary);
            $this->assertDumpFile($targetPath);

            return;
        }

        if ($binary !== null) {
            try {
                $this->dumpMysqlViaCli($targetPath, $config, $binary);
                $this->assertDumpFile($targetPath);

                return;
            } catch (ValidationException) {
                @unlink($targetPath);
            }
        }

        $this->phpDumper->dumpTo($targetPath, $config);
    }

    private function assertDumpFile(string $targetPath): void
    {
        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysqlViaCli(string $targetPath, array $config, string $binary): void
    {
        $credentialsFile = $this->credentialsFiles->create(
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            'backup',
        );

        try {
            $database = (string) ($config['database'] ?? '');
            $host = (string) ($config['host'] ?? '127.0.0.1');
            $port = (string) ($config['port'] ?? '3306');

            $result = Process::timeout(3600)->run([
                $binary,
                '--defaults-extra-file=' . $credentialsFile,
                '--host=' . $host,
                '--port=' . $port,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--result-file=' . $targetPath,
                $database,
            ]);

            if (! $result->successful()) {
                @unlink($targetPath);

                throw ValidationException::withMessages([
                    'backup' => __('agricart.backups.messages.dump_failed'),
                ]);
            }
        } finally {
            @unlink($credentialsFile);
        }
    }

}
