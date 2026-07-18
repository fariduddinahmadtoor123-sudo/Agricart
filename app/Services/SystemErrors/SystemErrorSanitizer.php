<?php

namespace App\Services\SystemErrors;

use Illuminate\Support\Str;

class SystemErrorSanitizer
{
    private const REDACTED = '[REDACTED]';

    public function message(?string $value, int $limit = 10000): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=-]+/i', '$1 '.self::REDACTED, $value) ?? $value;
        $value = preg_replace(
            '/((?:password|passwd|passphrase|secret|token|api[_-]?key|authorization|cookie|session|private[_-]?key|client[_-]?secret)\s*[:=]\s*)(["\'])(.*?)\2/i',
            '$1'.self::REDACTED,
            $value,
        ) ?? $value;
        $value = preg_replace(
            '/\b(password|passwd|passphrase|secret|token|api[_-]?key|authorization|cookie|session|private[_-]?key|client[_-]?secret)\b\s*[:=]\s*[^\s,;&}\]]+/i',
            '$1='.self::REDACTED,
            $value,
        ) ?? $value;
        $value = preg_replace('/([?&](?:password|token|key|secret|signature|code)=)[^&\s]*/i', '$1'.self::REDACTED, $value) ?? $value;
        $value = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', '[EMAIL]', $value) ?? $value;
        $value = preg_replace('/\b(?:\d[ -]?){10,16}\b/', '[NUMBER]', $value) ?? $value;

        if (str_contains($value, 'SQLSTATE')) {
            $value = preg_replace("/'[^']*'/", "'[VALUE]'", $value) ?? $value;
        }

        return Str::limit($value, $limit, '…');
    }

    public function trace(?string $value): ?string
    {
        return $this->message($value, 65535);
    }

    public function requestPath(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $path = explode('?', (string) $value, 2)[0];

        return Str::limit($path, 2000, '…');
    }

    public function file(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = str_replace('\\', '/', (string) $value);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        if (str_starts_with($normalized, $base)) {
            return Str::limit(substr($normalized, strlen($base)), 2000, '…');
        }

        return Str::limit(basename($normalized), 2000, '…');
    }

    /**
     * @return array<string, mixed>
     */
    public function context(array $context, int $depth = 0): array
    {
        if ($depth > 3) {
            return [];
        }

        $sanitized = [];

        foreach (array_slice($context, 0, 30, true) as $key => $value) {
            $key = (string) $key;

            if (preg_match('/password|passwd|secret|token|api[_-]?key|authorization|cookie|session|private[_-]?key/i', $key)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->context($value, $depth + 1);
            } elseif (is_scalar($value) || $value === null) {
                $sanitized[$key] = is_string($value) ? $this->message($value, 2000) : $value;
            } else {
                $sanitized[$key] = '['.get_debug_type($value).']';
            }
        }

        return $sanitized;
    }
}
