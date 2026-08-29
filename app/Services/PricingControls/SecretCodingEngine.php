<?php

namespace App\Services\PricingControls;

use App\Models\SecretCodingProfile;

class SecretCodingEngine
{
    public function activeProfile(): ?SecretCodingProfile
    {
        return SecretCodingProfile::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first();
    }

    public function encodePrice(?string $value, ?SecretCodingProfile $profile = null): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $profile ??= $this->activeProfile();

        if ($profile === null) {
            return (string) $value;
        }

        return $this->encodeWithSlots((string) $value, $profile->digit_slots ?? []);
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    public function encodeWithSlots(string $value, array $slots): string
    {
        $map = SecretCodingService::normalizeDigitSlotsStatic($slots);
        $encoded = '';

        foreach (mb_str_split($value) as $char) {
            if (ctype_digit($char) && filled($map[$char] ?? '')) {
                $encoded .= $map[$char];

                continue;
            }

            $encoded .= $char;
        }

        return $encoded;
    }

    public function decodePrice(?string $value, ?SecretCodingProfile $profile = null): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $profile ??= $this->activeProfile();

        if ($profile === null) {
            return preg_replace('/\D+/', '', (string) $value);
        }

        $map = SecretCodingService::normalizeDigitSlotsStatic($profile->digit_slots ?? []);
        $tokens = array_values(array_filter($map, static fn (string $token): bool => filled($token)));

        if ($tokens === []) {
            return preg_replace('/\D+/', '', (string) $value);
        }

        usort($tokens, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $reverse = [];

        foreach ($map as $digit => $token) {
            if (filled($token)) {
                $reverse[$token] = $digit;
            }
        }

        $decoded = '';
        $remaining = (string) $value;

        while ($remaining !== '') {
            $matched = false;

            foreach ($tokens as $token) {
                if (! str_starts_with($remaining, $token)) {
                    continue;
                }

                $decoded .= $reverse[$token] ?? '';
                $remaining = mb_substr($remaining, mb_strlen($token));
                $matched = true;

                break;
            }

            if ($matched) {
                continue;
            }

            $char = mb_substr($remaining, 0, 1);
            $decoded .= ctype_digit($char) ? $char : '';
            $remaining = mb_substr($remaining, 1);
        }

        return $decoded;
    }

    /**
     * Pick the profile whose mapping round-trips every encoded amount.
     *
     * @param  list<string|null>  $encodedValues
     */
    public function profileMatchingEncodedValues(array $encodedValues): ?SecretCodingProfile
    {
        $encodedValues = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $encodedValues,
        ), static fn (string $value): bool => $value !== ''));

        if ($encodedValues === []) {
            return $this->activeProfile();
        }

        $profiles = SecretCodingProfile::query()->orderByDesc('id')->get();

        foreach ($profiles as $profile) {
            $matches = true;

            foreach ($encodedValues as $encoded) {
                if (! $this->encodedValueMatchesProfile($encoded, $profile)) {
                    $matches = false;

                    break;
                }
            }

            if ($matches) {
                return $profile;
            }
        }

        return $this->activeProfile();
    }

    public function encodedValueMatchesProfile(string $value, SecretCodingProfile $profile): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        $stripped = str_replace([',', ' ', '.'], '', $value);

        if ($stripped !== '' && ctype_digit($stripped)) {
            return true;
        }

        $decoded = $this->decodePrice($value, $profile);
        $strippedDecoded = str_replace([',', ' ', '.'], '', $decoded);

        if ($strippedDecoded === '' || ! ctype_digit($strippedDecoded)) {
            return false;
        }

        return $this->encodePrice($strippedDecoded, $profile) === $value;
    }
}
