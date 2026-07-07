<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $validated = $this->validate($data);

        if (empty($validated['status'] ?? null)) {
            $validated['status'] = User::STATUS_PENDING;
        }

        if (! filled($validated['user_number'] ?? null)) {
            $validated['user_number'] = $this->nextUserNumber();
        }

        return User::query()->create($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerPublic(array $data): User
    {
        $validated = $this->validatePublicRegistration($data);
        $validated['status'] = User::STATUS_PENDING;
        $validated['role_id'] = null;
        $validated['user_number'] = $this->nextUserNumber();

        return User::query()->create($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $validated = $this->validate($data, $user->id);

        if (empty($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return $user->fresh(['role']);
    }

    public function nextUserNumber(): string
    {
        $next = (int) User::query()->max('id') + 1;

        do {
            $number = 'USR-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (User::query()->where('user_number', $number)->exists());

        return $number;
    }

    /**
     * @param  list<array{contact_person?: string, phone_number?: string}>  $rows
     * @return list<array{contact_person: string, phone_number: string}>
     */
    public function normalizePhoneRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'contact_person' => trim((string) ($row['contact_person'] ?? '')),
                    'phone_number' => trim((string) ($row['phone_number'] ?? '')),
                ];
            }, $rows),
            static fn (array $row): bool => filled($row['phone_number']),
        ));
    }

    /**
     * @param  list<array{bank_name?: string, account_title?: string, account_number?: string}>  $rows
     * @return list<array{bank_name: string, account_title: string, account_number: string}>
     */
    public function normalizeBankRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'bank_name' => trim((string) ($row['bank_name'] ?? '')),
                    'account_title' => trim((string) ($row['account_title'] ?? '')),
                    'account_number' => trim((string) ($row['account_number'] ?? '')),
                ];
            }, $rows),
            static fn (array $row): bool => filled($row['bank_name'])
                || filled($row['account_title'])
                || filled($row['account_number']),
        ));
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            User::STATUS_PENDING,
            User::STATUS_ACTIVE,
            User::STATUS_INACTIVE,
            User::STATUS_REJECTED,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validatePublicRegistration(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.contact_person' => ['nullable', 'string', 'max:255'],
            'phone_numbers.*.phone_number' => ['nullable', 'string', 'max:30'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:64'],
            'profile_photo_path' => ['nullable', 'string', 'max:500'],
            'cnic_front_path' => ['nullable', 'string', 'max:500'],
            'cnic_back_path' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));
        $validated['phone_numbers'] = $validated['phone_numbers'] ?? [];
        $validated['bank_accounts'] = $validated['bank_accounts'] ?? [];

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $isCreate = $exceptId === null;

        $rules = [
            'user_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('users', 'user_number')->ignore($exceptId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', 'string', Rule::in(self::statuses())],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($exceptId),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.contact_person' => ['nullable', 'string', 'max:255'],
            'phone_numbers.*.phone_number' => ['nullable', 'string', 'max:30'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:64'],
            'profile_photo_path' => ['nullable', 'string', 'max:500'],
            'cnic_front_path' => ['nullable', 'string', 'max:500'],
            'cnic_back_path' => ['nullable', 'string', 'max:500'],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));
        $validated['phone_numbers'] = $validated['phone_numbers'] ?? [];
        $validated['bank_accounts'] = $validated['bank_accounts'] ?? [];

        if ($isCreate && empty($validated['password'])) {
            throw ValidationException::withMessages([
                'password' => [__('validation.required', ['attribute' => 'password'])],
            ]);
        }

        return $validated;
    }
}
