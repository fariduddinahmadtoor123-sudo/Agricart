<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Allocate PO/PS/PP style numbers from durable sources (DB max + optional settings),
 * never from Cache alone — Hostinger cache clears / deploys were resetting to 001 and
 * updateOrCreate then overwrote saved/closed rows.
 */
final class ToolsDocumentSequence
{
    /**
     * Highest numeric suffix already used in the given column for this user.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function maxUsed(string $modelClass, string $column, int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $max = 0;

        foreach ($modelClass::query()->where('user_id', $userId)->pluck($column) as $raw) {
            if (preg_match('/(\d+)\s*$/', (string) $raw, $m) === 1) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }

    /**
     * Next free sequence: max(DB used, settings stored, cache) + 1, then persist.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  callable(array<string, mixed>, int): void  $persistSettings  receives settings data + new seq
     */
    public static function allocate(
        string $modelClass,
        string $column,
        int $userId,
        string $cacheKey,
        int $settingsSeq,
        callable $persistSettings,
    ): int {
        return (int) DB::transaction(function () use ($modelClass, $column, $userId, $cacheKey, $settingsSeq, $persistSettings): int {
            $seq = max(
                self::maxUsed($modelClass, $column, $userId),
                $settingsSeq,
                (int) Cache::get($cacheKey, 0),
            ) + 1;

            $persistSettings($seq);
            Cache::forever($cacheKey, $seq);

            return $seq;
        });
    }

    /**
     * Peek without consuming — always at least DB max + 1.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function peek(
        string $modelClass,
        string $column,
        int $userId,
        string $cacheKey,
        int $settingsSeq,
    ): int {
        return max(
            self::maxUsed($modelClass, $column, $userId),
            $settingsSeq,
            (int) Cache::get($cacheKey, 0),
        ) + 1;
    }

    public static function format(string $prefix, int $seq): string
    {
        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
