<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrationIdentifierAuditCommand extends Command
{
    protected $signature = 'migrations:audit-identifiers {--max=64 : Maximum identifier length}';

    protected $description = 'Report migration index/FK/unique names that exceed MySQL identifier limits';

    public function handle(): int
    {
        $max = (int) $this->option('max');
        $files = File::glob(database_path('migrations/*.php'));
        sort($files);

        $issues = [];

        foreach ($files as $file) {
            $content = File::get($file);
            $basename = basename($file);
            $currentTable = null;

            foreach (explode("\n", $content) as $line) {
                if (preg_match("/Schema::(?:create|table)\s*\(\s*['\"]([^'\"]+)['\"]/", $line, $m)) {
                    $currentTable = $m[1];
                }

                if ($currentTable === null) {
                    continue;
                }

                if (preg_match("/foreignId\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $line, $col) && str_contains($line, 'constrained')) {
                    if (preg_match('/constrained\s*\([^)]*,\s*[^,]+,\s*[\'"]/', $line)) {
                        continue;
                    }
                    $name = "{$currentTable}_{$col[1]}_foreign";
                    if (strlen($name) >= $max) {
                        $issues[] = [$basename, $currentTable, 'foreign', $name, strlen($name)];
                    }
                }

                if (preg_match("/->index\s*\(\s*\[([^\]]+)\]\s*\)/", $line, $m) && ! preg_match("/->index\s*\(\s*\[[^\]]+\]\s*,\s*['\"]/", $line)) {
                    preg_match_all("/['\"]([^'\"]+)['\"]/", $m[1], $cols);
                    $columns = $cols[1] ?? [];
                    if ($columns !== []) {
                        $name = $currentTable . '_' . implode('_', $columns) . '_index';
                        if (strlen($name) >= $max) {
                            $issues[] = [$basename, $currentTable, 'index', $name, strlen($name)];
                        }
                    }
                }

                if (preg_match("/->unique\s*\(\s*\[([^\]]+)\]\s*\)/", $line, $m) && ! preg_match("/->unique\s*\(\s*\[[^\]]+\]\s*,\s*['\"]/", $line)) {
                    preg_match_all("/['\"]([^'\"]+)['\"]/", $m[1], $cols);
                    $columns = $cols[1] ?? [];
                    if ($columns !== []) {
                        $name = $currentTable . '_' . implode('_', $columns) . '_unique';
                        if (strlen($name) >= $max) {
                            $issues[] = [$basename, $currentTable, 'unique', $name, strlen($name)];
                        }
                    }
                }
            }
        }

        if ($issues === []) {
            $this->components->info("No auto-generated identifiers at or above {$max} characters.");

            return self::SUCCESS;
        }

        foreach ($issues as [$file, $table, $type, $name, $len]) {
            $this->line("{$file} [{$table}] {$type} ({$len}): {$name}");
        }

        $this->components->error(count($issues) . ' issue(s) found. Add explicit short names in migrations.');

        return self::FAILURE;
    }
}
