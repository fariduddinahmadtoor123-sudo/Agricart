<?php

namespace App\Services\PricingControls;

use App\Models\SecretCodingProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SecretCodingService
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    /** @var list<string> */
    public const DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * @return array<string, string>
     */
    public static function emptyDigitSlots(): array
    {
        return array_fill_keys(self::DIGITS, '');
    }

    public function create(array $data): SecretCodingProfile
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): SecretCodingProfile {
            if (! filled($validated['profile_number'] ?? null)) {
                $validated['profile_number'] = $this->nextProfileNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();
            $validated['digit_slots'] = $this->normalizeDigitSlots($validated['digit_slots'] ?? []);

            return SecretCodingProfile::query()->create($validated);
        });
    }

    public function update(SecretCodingProfile $record, array $data): SecretCodingProfile
    {
        $validated = $this->validate($data, $record->id);
        $validated['digit_slots'] = $this->normalizeDigitSlots($validated['digit_slots'] ?? []);

        $record->update($validated);

        return $record->fresh(['creator']);
    }

    public function delete(SecretCodingProfile $record): void
    {
        $record->delete();
    }

    public function nextProfileNumber(): string
    {
        $next = (int) SecretCodingProfile::query()->max('id') + 1;

        do {
            $number = 'SCP-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (SecretCodingProfile::query()->where('profile_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $slots
     * @return array<string, string>
     */
    public function normalizeDigitSlots(array $slots): array
    {
        $normalized = self::emptyDigitSlots();

        foreach (self::DIGITS as $digit) {
            $normalized[$digit] = trim((string) ($slots[$digit] ?? ''));
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        return validator($data, [
            'profile_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('secret_coding_profiles', 'profile_number')->ignore($ignoreId),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'digit_slots' => ['nullable', 'array'],
            'digit_slots.*' => ['nullable', 'string', 'max:32'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();
    }
}
