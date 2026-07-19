<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    /**
     * @param  list<string>  $schemes
     */
    public function __construct(
        private readonly bool $allowRelative = true,
        private readonly array $schemes = ['http', 'https', 'mailto', 'tel'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $fail('The :attribute must be a safe URL.');

            return;
        }

        $value = trim($value);

        if (
            $this->allowRelative
            && str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && ! str_contains($value, '\\')
        ) {
            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (! in_array($scheme, $this->schemes, true)) {
            $fail('The :attribute URL scheme is not allowed.');

            return;
        }

        if (in_array($scheme, ['http', 'https'], true)) {
            $parts = parse_url($value);

            if (
                filter_var($value, FILTER_VALIDATE_URL) === false
                || ! is_array($parts)
                || blank($parts['host'] ?? null)
                || isset($parts['user'])
                || isset($parts['pass'])
            ) {
                $fail('The :attribute must be a valid web URL.');
            }
        }
    }
}
