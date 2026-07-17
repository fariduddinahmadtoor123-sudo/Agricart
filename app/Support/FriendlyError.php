<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

/**
 * Turns technical exceptions into short, human-friendly messages (EN/UR)
 * for on-screen display. The original exception is always reported to the
 * log so developers keep the full technical details.
 */
class FriendlyError
{
    public static function message(\Throwable $exception): string
    {
        // Validation messages are already written for humans — pass through.
        if ($exception instanceof ValidationException) {
            $first = collect($exception->errors())->flatten()->first();

            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        report($exception);

        $raw = $exception->getMessage();

        if ($exception instanceof DecryptException || self::contains($raw, ['MAC is invalid', 'payload is invalid', 'Unable to decrypt'])) {
            return __('agricart.errors.decrypt');
        }

        if ($exception instanceof ConnectionException || self::contains($raw, ['cURL error', 'Could not resolve host', 'Connection refused', 'Connection timed out', 'timed out', 'SSL certificate'])) {
            return __('agricart.errors.connection');
        }

        if ($exception instanceof QueryException || self::contains($raw, ['SQLSTATE'])) {
            return __('agricart.errors.database');
        }

        if (self::contains($raw, ['Permission denied', 'failed to open stream', 'No space left', 'Disk full'])) {
            return __('agricart.errors.storage');
        }

        return __('agricart.errors.generic');
    }

    /**
     * @param  list<string>  $needles
     */
    private static function contains(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
