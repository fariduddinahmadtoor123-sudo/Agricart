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

            $buffer = '';

            while (($chunk = fread($handle, 8192)) !== false && $chunk !== '') {
                $buffer .= $chunk;

                foreach ($this->extractCompleteStatements($buffer) as $statement) {
                    $this->executeStatement($connection, $statement);
                }
            }

            if (trim($buffer) !== '') {
                $this->executeStatement($connection, $buffer);
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

    /**
     * Split SQL by statement-terminating semicolons, ignoring those inside
     * quotes / backticks and respecting backslash escapes.
     *
     * @return list<string>
     */
    private function extractCompleteStatements(string &$buffer): array
    {
        $statements = [];
        $length = strlen($buffer);
        $quote = null;
        $start = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $buffer[$i];

            if ($quote !== null) {
                if ($char === '\\' && $quote !== '`') {
                    $i++;

                    continue;
                }

                if ($char === $quote) {
                    // MySQL doubled quote escape: '' or "" or ``
                    if ($i + 1 < $length && $buffer[$i + 1] === $quote) {
                        $i++;

                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;

                continue;
            }

            if ($char === '-' && $i + 1 < $length && $buffer[$i + 1] === '-') {
                // Line comment — skip to newline (outside quotes only)
                $newline = strpos($buffer, "\n", $i);

                if ($newline === false) {
                    break;
                }

                $i = $newline;

                continue;
            }

            if ($char === '/' && $i + 1 < $length && $buffer[$i + 1] === '*') {
                $end = strpos($buffer, '*/', $i + 2);

                if ($end === false) {
                    break;
                }

                $i = $end + 1;

                continue;
            }

            if ($char === ';') {
                $statements[] = substr($buffer, $start, $i - $start + 1);
                $start = $i + 1;
            }
        }

        $buffer = $start < $length ? substr($buffer, $start) : '';

        return $statements;
    }

    private function executeStatement(\Illuminate\Database\Connection $connection, string $statement): void
    {
        $sql = trim($statement);

        if ($sql === '' || str_starts_with($sql, '--')) {
            return;
        }

        // Skip mysqldump DELIMITER directives (CLI-only); PHP PDO cannot honor them.
        if (preg_match('/^DELIMITER\b/i', $sql) === 1) {
            return;
        }

        $connection->unprepared($sql);
    }
}
