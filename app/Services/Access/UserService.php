<?php

namespace App\Services\Access;

use App\Models\User;
use App\Services\Identifiers\DocumentCounterService;
use App\Support\PakistaniBanks;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $validated = $this->validate($data, null, $actor);

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
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $data['email'] = $email;
        $existing = $email !== ''
            ? User::query()->where('email', $email)->first()
            : null;

        if ($existing instanceof User) {
            if ($existing->status === User::STATUS_REJECTED) {
                return $this->resubmitRejected($existing, $data);
            }

            if ($existing->status === User::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'email' => [__('agricart.auth.already_pending')],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [__('agricart.auth.email_taken')],
            ]);
        }

        $validated = $this->validatePublicRegistration($data);
        $validated['user_number'] = $this->nextUserNumber();
        $validated['status'] = User::STATUS_PENDING;
        $validated['role_id'] = null;

        return User::query()->create($validated)->fresh(['role']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resubmitRejected(User $user, array $data): User
    {
        if ($user->status !== User::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'email' => [__('agricart.auth.resubmit_not_allowed')],
            ]);
        }

        $validated = $this->validatePublicRegistration($data, $user->id);
        $validated['rejection_reason'] = null;
        $validated['rejected_at'] = null;
        $validated['status'] = User::STATUS_PENDING;
        $validated['role_id'] = null;

        foreach (['profile_photo_path', 'cnic_front_path', 'cnic_back_path'] as $field) {
            if (! filled($validated[$field] ?? null)) {
                unset($validated[$field]);
            }
        }

        $this->replaceStoredFiles($user, $validated);

        $user->update($validated);

        return $user->fresh(['role']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        $validated = $this->validate($data, $user->id, $actor, $user);

        $passwordChanged = filled($validated['password'] ?? null);

        if (! $passwordChanged) {
            unset($validated['password']);
        }

        $this->replaceStoredFiles($user, $validated);

        $user->update($validated);

        if ($passwordChanged) {
            app(SessionInvalidationService::class)->wipeAllForUser($user->fresh() ?? $user);
        }

        return $user->fresh(['role']);
    }

    public function archive(User $user, ?User $actor = null): void
    {
        $actor ??= auth()->user() instanceof User ? auth()->user() : null;

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        if (! $actor->isSuperAdmin() && ! $actor->hasPermission('settings.users.manage')) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        if (app(RoleAssignmentGuard::class)->isOwner($user)) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.users.errors.archive_owner_forbidden')],
            ]);
        }

        if ((int) $actor->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.users.errors.archive_self_forbidden')],
            ]);
        }

        if ($user->trashed()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $timestamp = now()->timestamp;
            $email = mb_strtolower(trim((string) $user->email));
            $archivedEmail = $this->archivedEmailAddress($email, (int) $user->id, $timestamp);

            $user->update([
                'status' => User::STATUS_INACTIVE,
                'email' => $archivedEmail,
            ]);

            app(SessionInvalidationService::class)->wipeAllForUser($user);

            $user->delete();
        });
    }

    private function archivedEmailAddress(string $email, int $userId, int $timestamp): string
    {
        $email = mb_strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return "archived.{$userId}.{$timestamp}.{$email}@invalid.local";
        }

        [$local, $domain] = explode('@', $email, 2);

        return "{$local}+archived-{$userId}-{$timestamp}@{$domain}";
    }

    public function nextUserNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'users.user_number',
            'USR-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('users', 'user_number', 'USR-'),
        );
    }

    /**
     * @param  list<array{contact_person?: string, contact_person_ur?: string, phone_number?: string}>  $rows
     * @return list<array{contact_person: string, contact_person_ur: string, phone_number: string}>
     */
    public function normalizePhoneRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'contact_person' => trim((string) ($row['contact_person'] ?? '')),
                    'contact_person_ur' => trim((string) ($row['contact_person_ur'] ?? '')),
                    'phone_number' => trim((string) ($row['phone_number'] ?? '')),
                ];
            }, $rows),
            static fn (array $row): bool => filled($row['phone_number']),
        ));
    }

    /**
     * @param  list<array{bank_name?: string, account_title?: string, account_title_ur?: string, account_number?: string}>  $rows
     * @return list<array{bank_name: string, account_title: string, account_title_ur: string, account_number: string}>
     */
    public function normalizeBankRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'bank_name' => trim((string) ($row['bank_name'] ?? '')),
                    'account_title' => trim((string) ($row['account_title'] ?? '')),
                    'account_title_ur' => trim((string) ($row['account_title_ur'] ?? '')),
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
    public function validatePublicRegistration(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($exceptId),
            ],
            'address' => ['required', 'string', 'max:2000'],
            'address_ur' => ['required', 'string', 'max:2000'],
            'phone_numbers' => ['required', 'array', 'min:1'],
            'phone_numbers.*.contact_person' => ['required', 'string', 'max:255'],
            'phone_numbers.*.contact_person_ur' => ['required', 'string', 'max:255'],
            'phone_numbers.*.phone_number' => ['required', 'string', 'max:30'],
            'bank_accounts' => ['required', 'array', 'min:1'],
            'bank_accounts.*.bank_name' => [
                'required',
                'string',
                'max:255',
                Rule::in(PakistaniBanks::englishNames()),
            ],
            'bank_accounts.*.account_title' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_title_ur' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:64'],
            'profile_photo_path' => ['required', 'string', 'max:500'],
            'cnic_front_path' => ['required', 'string', 'max:500'],
            'cnic_back_path' => ['required', 'string', 'max:500'],
            'password' => ['required', 'string', Password::default()],
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
     * @param  array<string, mixed>  $validated
     */
    private function replaceStoredFiles(User $user, array $validated): void
    {
        $disks = [
            'profile_photo_path' => 'public',
            'cnic_front_path' => 'local',
            'cnic_back_path' => 'local',
        ];

        foreach ($disks as $field => $disk) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $newPath = $validated[$field] ?? null;
            $oldPath = $user->{$field};

            if (filled($oldPath) && $oldPath !== $newPath) {
                Storage::disk($disk)->delete($oldPath);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null, ?User $actor = null, ?User $target = null): array
    {
        $isCreate = $exceptId === null;
        $actor ??= auth()->user() instanceof User ? auth()->user() : null;

        if ($target === null && $exceptId !== null) {
            $target = User::query()->find($exceptId);
        }

        $rules = [
            'user_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('users', 'user_number')->ignore($exceptId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', 'string', Rule::in(self::statuses())],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($exceptId),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'address_ur' => ['nullable', 'string', 'max:2000'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.contact_person' => ['nullable', 'string', 'max:255'],
            'phone_numbers.*.contact_person_ur' => ['nullable', 'string', 'max:255'],
            'phone_numbers.*.phone_number' => ['nullable', 'string', 'max:30'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(PakistaniBanks::englishNames()),
            ],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_title_ur' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:64'],
            'profile_photo_path' => ['nullable', 'string', 'max:500'],
            'cnic_front_path' => ['nullable', 'string', 'max:500'],
            'cnic_back_path' => ['nullable', 'string', 'max:500'],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', Password::default()],
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

        app(RoleAssignmentGuard::class)->assertCanAssign(
            $actor,
            $target,
            isset($validated['role_id']) ? (int) $validated['role_id'] : null,
        );

        return $validated;
    }
}
