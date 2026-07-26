<?php

namespace Tests\Unit\Backup;

use App\Services\Backup\MysqlClientCredentialsFile;
use Tests\TestCase;

class MysqlClientCredentialsFileTest extends TestCase
{
    public function test_writes_under_storage_with_escaped_password(): void
    {
        $path = null;

        try {
            $path = (new MysqlClientCredentialsFile)->create('root', 'p@"ss\\word', 'backup');

            $this->assertStringContainsString(
                str_replace('\\', '/', storage_path('app/temp/mysql-client')),
                str_replace('\\', '/', $path),
            );
            $this->assertFileExists($path);

            $contents = (string) file_get_contents($path);
            $this->assertStringContainsString('user="root"', $contents);
            $this->assertStringContainsString('password="p@\\"ss\\\\word"', $contents);
        } finally {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
