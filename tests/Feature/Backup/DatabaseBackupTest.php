<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupService;
use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\MysqlCliToolResolver;
use App\Services\Backup\PhpMysqlDumper;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    public function test_database_backup_zip_is_created_on_sqlite(): void
    {
        $dbPath = storage_path('app/phpunit-db-backup.sqlite');

        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $dbPath,
        ]);

        Artisan::call('migrate', ['--force' => true]);

        $service = app(BackupService::class);
        $entry = null;

        try {
            $entry = $service->create(BackupService::TYPE_DATABASE);

            $this->assertNotSame('', $entry->id);
            $this->assertSame(BackupService::TYPE_DATABASE, $entry->type);
            $this->assertGreaterThan(0, $entry->sizeBytes);
            $this->assertFileExists($service->absolutePath($entry->filename));
        } finally {
            if ($entry !== null) {
                $service->delete($entry->id);
            }
            @unlink($dbPath);
        }
    }

    public function test_mysql_dump_falls_back_to_php_when_cli_is_unavailable(): void
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            config([
                'database.default' => 'mysql',
                'database.connections.mysql' => [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'database' => 'test',
                    'username' => 'root',
                    'password' => '',
                ],
            ]);
        }

        $phpDumper = $this->createMock(PhpMysqlDumper::class);
        $phpDumper->expects($this->once())
            ->method('dumpTo')
            ->willReturnCallback(function (string $path, array $config): void {
                file_put_contents($path, "-- test\n");
            });

        $cli = $this->createMock(MysqlCliToolResolver::class);
        $cli->method('resolveMysqldump')->willReturn(null);

        $dumper = new DatabaseDumper($cli, $phpDumper, new \App\Services\Backup\MysqlClientCredentialsFile);
        $target = storage_path('app/backup-php-fallback-test.sql');

        try {
            $dumper->dumpTo($target);
            $this->assertFileExists($target);
            $this->assertStringContainsString('test', (string) file_get_contents($target));
        } finally {
            @unlink($target);
        }
    }
}
