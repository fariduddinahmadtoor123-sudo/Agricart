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
}
