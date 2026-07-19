<?php

namespace App\Services\Identifiers;

use Illuminate\Support\Facades\DB;

class DocumentCounterService
{
    public function next(
        string $key,
        string $prefix,
        int $padding = 5,
        ?callable $existingMaximum = null,
    ): string {
        $value = DB::transaction(function () use ($key, $existingMaximum): int {
            DB::table('document_counters')->insertOrIgnore([
                'key' => $key,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('document_counters')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $current = (int) ($counter->value ?? 0);
            $existing = $existingMaximum === null ? 0 : max(0, (int) $existingMaximum());
            $next = max($current, $existing) + 1;

            DB::table('document_counters')->where('key', $key)->update([
                'value' => $next,
                'updated_at' => now(),
            ]);

            return $next;
        }, 5);

        return $prefix.str_pad((string) $value, $padding, '0', STR_PAD_LEFT);
    }

    public function nextInteger(string $key, ?callable $existingMaximum = null): int
    {
        return (int) $this->next($key, '', 1, $existingMaximum);
    }

    public static function maximumSuffix(string $table, string $column, string $prefix): int
    {
        return DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->pluck($column)
            ->reduce(static function (int $maximum, mixed $value) use ($prefix): int {
                $suffix = substr((string) $value, strlen($prefix));

                return ctype_digit($suffix) ? max($maximum, (int) $suffix) : $maximum;
            }, 0);
    }
}
