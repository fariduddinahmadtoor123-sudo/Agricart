<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhpMysqlImporter
{
    public function importFrom(string $sourcePath): void
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.backup_file_missing'),
            ]);
        }

        $connection = DB::connection();
        $handle = fopen($sourcePath, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.restore_failed'),
            ]);
        }

        try {
            $connection->unprepared('SET FOREIGN_KEY_CHECKS=0');

            $statement = '';

            while (($line = fgets($handle)) !== false) {
                $trimmed = ltrim($line);

                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }

                $statement .= $line;

                if (! preg_match('/;\s*$/', rtrim($line))) {
                    continue;
                }

                $this->executeStatement($connection, $statement);
                $statement = '';
            }

            if (trim($statement) !== '') {
                $this->executeStatement($connection, $statement);
            }

            $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.restore_failed'),
            ]);
        } finally {
            fclose($handle);
        }
    }

    private function executeStatement(\Illuminate\Database\Connection $connection, string $statement): void
    {
        $sql = trim($statement);

        if ($sql === '') {
            return;
        }

        $connection->unprepared($sql);
    }
}
