<?php

namespace App\Services\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDO;

class PhpMysqlDumper
{
    private const INSERT_BATCH_ROWS = 100;

    /**
     * @param  array<string, mixed>  $config
     */
    public function dumpTo(string $targetPath, array $config): void
    {
        $connection = DB::connection();
        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }

        $handle = fopen($targetPath, 'wb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }

        try {
            $this->writeLine($handle, '-- Agricart database export (PHP / PDO)');
            $this->writeLine($handle, '-- Generated: '.now()->toIso8601String());
            $this->writeLine($handle, 'SET FOREIGN_KEY_CHECKS=0;');
            $this->writeLine($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
            $this->writeLine($handle, 'SET NAMES utf8mb4;');
            $this->writeLine($handle, 'SET time_zone = "+00:00";');
            $this->writeLine($handle, '');

            foreach ($this->tableNames($connection, $database) as $table) {
                $this->dumpTable($handle, $connection, $table);
            }

            $this->writeLine($handle, 'SET FOREIGN_KEY_CHECKS=1;');
        } finally {
            fclose($handle);
        }

        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function tableNames(Connection $connection, string $database): array
    {
        $rows = $connection->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
            [$database, 'BASE TABLE'],
        );

        $tables = [];

        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');

            if ($name !== '') {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    private function dumpTable($handle, Connection $connection, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($table);

        $createRow = $connection->selectOne('SHOW CREATE TABLE '.$quotedTable);
        $createSql = null;

        if ($createRow !== null) {
            $createSql = $createRow->{'Create Table'} ?? $createRow->{'Create View'} ?? null;
        }

        if (! is_string($createSql) || $createSql === '') {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.dump_failed'),
            ]);
        }

        $this->writeLine($handle, 'DROP TABLE IF EXISTS '.$quotedTable.';');
        $this->writeLine($handle, $createSql.';');
        $this->writeLine($handle, '');

        $pdo = $connection->getPdo();
        $statement = $pdo->query('SELECT * FROM '.$quotedTable);

        if ($statement === false) {
            return;
        }

        $columns = null;
        $batch = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($columns === null) {
                $columns = array_keys($row);

                if ($columns === []) {
                    break;
                }
            }

            $batch[] = $row;

            if (count($batch) >= self::INSERT_BATCH_ROWS) {
                $this->writeInsertBatch($handle, $quotedTable, $columns, $batch, $pdo);
                $batch = [];
            }
        }

        if ($columns !== null && $batch !== []) {
            $this->writeInsertBatch($handle, $quotedTable, $columns, $batch, $pdo);
        }

        $this->writeLine($handle, '');
    }

    /**
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeInsertBatch($handle, string $quotedTable, array $columns, array $rows, PDO $pdo): void
    {
        $columnList = implode(', ', array_map(fn (string $col): string => $this->quoteIdentifier($col), $columns));
        $valueGroups = [];

        foreach ($rows as $row) {
            $values = [];

            foreach ($columns as $column) {
                $values[] = $this->quoteValue($pdo, $row[$column] ?? null);
            }

            $valueGroups[] = '('.implode(', ', $values).')';
        }

        $this->writeLine($handle, 'INSERT INTO '.$quotedTable.' ('.$columnList.') VALUES '.implode(', ', $valueGroups).';');
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $pdo->quote($value);
        }

        return $pdo->quote((string) json_encode($value));
    }

    /**
     * @param  resource  $handle
     */
    private function writeLine($handle, string $line): void
    {
        fwrite($handle, $line."\n");
    }
}
