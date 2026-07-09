<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

class DatabaseImporter
{
    public function __construct(
        private readonly MysqlCliToolResolver $cliTools,
        private readonly PhpMysqlImporter $phpImporter,
    ) {}

    public function importFrom(string $sourcePath): void
    {
        if (! is_file($sourcePath)) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.backup_file_missing'),
            ]);
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $this->importSqlite($sourcePath, config("database.connections.{$connection}.database"));

            return;
        }

        if ($driver !== 'mysql') {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.unsupported_driver', ['driver' => $driver]),
            ]);
        }

        $this->importMysql($sourcePath, config("database.connections.{$connection}"));
    }

    private function importSqlite(string $sourcePath, string $databasePath): void
    {
        File::ensureDirectoryExists(dirname($databasePath));

        if (! copy($sourcePath, $databasePath)) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.restore_failed'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function importMysql(string $sourcePath, array $config): void
    {
        $method = (string) config('backup.mysql_import_method', 'auto');

        if ($method === 'php') {
            $this->phpImporter->importFrom($sourcePath);

            return;
        }

        $binary = $this->cliTools->resolveMysql();

        if ($method === 'cli' || $binary !== null) {
            if ($binary === null) {
                throw ValidationException::withMessages([
                    'restore' => __('agricart.backups.messages.restore_cli_unavailable'),
                ]);
            }

            try {
                $this->importMysqlViaCli($sourcePath, $config, $binary);

                return;
            } catch (ValidationException $exception) {
                if ($method === 'cli') {
                    throw $exception;
                }
            }
        }

        if ($method === 'cli') {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.restore_failed'),
            ]);
        }

        $this->phpImporter->importFrom($sourcePath);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function importMysqlViaCli(string $sourcePath, array $config, string $binary): void
    {
        $credentialsFile = $this->writeMysqlCredentialsFile(
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
        );

        try {
            $database = (string) ($config['database'] ?? '');
            $host = (string) ($config['host'] ?? '127.0.0.1');
            $port = (string) ($config['port'] ?? '3306');

            $result = Process::timeout(3600)
                ->input(fopen($sourcePath, 'rb'))
                ->run([
                    $binary,
                    '--defaults-extra-file=' . $credentialsFile,
                    '--host=' . $host,
                    '--port=' . $port,
                    $database,
                ]);

            if (! $result->successful()) {
                throw ValidationException::withMessages([
                    'restore' => __('agricart.backups.messages.restore_failed'),
                ]);
            }
        } finally {
            @unlink($credentialsFile);
        }
    }

    private function writeMysqlCredentialsFile(string $username, string $password): string
    {
        $path = tempnam(sys_get_temp_dir(), 'agricart-mysql-');

        if ($path === false) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.restore_failed'),
            ]);
        }

        $contents = "[client]\nuser=\"{$username}\"\npassword=\"{$password}\"\n";
        file_put_contents($path, $contents);

        return $path;
    }
}
