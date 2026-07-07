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
}
