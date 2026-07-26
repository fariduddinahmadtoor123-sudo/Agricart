<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Writes a short-lived MySQL defaults-extra-file under storage (not sys temp),
 * with my.cnf-safe escaping and restrictive permissions.
 */
class MysqlClientCredentialsFile
{
    public function create(string $username, string $password, string $errorKey = 'backup'): string
    {
        $failureMessage = $errorKey === 'restore'
            ? __('agricart.backups.messages.restore_failed')
            : __('agricart.backups.messages.dump_failed');

        $directory = storage_path('app/temp/mysql-client');
        File::ensureDirectoryExists($directory);

        $path = tempnam($directory, 'agricart-mysql-');

        if ($path === false) {
            throw ValidationException::withMessages([
                $errorKey => $failureMessage,
            ]);
        }

        $contents = "[client]\n"
            .'user="'.$this->escape($username)."\"\n"
            .'password="'.$this->escape($password)."\"\n";

        if (file_put_contents($path, $contents) === false) {
            @unlink($path);

            throw ValidationException::withMessages([
                $errorKey => $failureMessage,
            ]);
        }

        @chmod($path, 0600);

        return $path;
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value,
        );
    }
}
