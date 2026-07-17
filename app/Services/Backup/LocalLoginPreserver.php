<?php

namespace App\Services\Backup;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Full restore replaces the database. This service snapshots local login accounts
 * before restore and re-inserts any that are missing afterwards (by email), so
 * local staff/customers keep working while backup accounts remain authoritative
 * when the same email already exists in the restored data.
 */
class LocalLoginPreserver
{
    /**
     * @var array{users: list<array<string, mixed>>, customers: list<array<string, mixed>>}|null
     */
    private ?array $snapshot = null;

    public function capture(): void
    {
        $this->snapshot = [
            'users' => $this->captureUsers(),
            'customers' => $this->captureCustomers(),
        ];
    }

    public function restoreMissing(): int
    {
        if ($this->snapshot === null) {
            return 0;
        }

        $restored = 0;
        $restored += $this->restoreMissingUsers($this->snapshot['users']);
        $restored += $this->restoreMissingCustomers($this->snapshot['customers']);
        $this->snapshot = null;

        return $restored;
    }

    public function ensureBootstrapAdmin(): void
    {
        $email = trim((string) config('backup.bootstrap_admin.email', ''));
        $password = (string) config('backup.bootstrap_admin.password', '');

        if ($email === '' || $password === '') {
            return;
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        if (! Role::query()->where('slug', 'super-admin')->exists()) {
            (new RolesAndPermissionsSeeder)->run();
        }

        $roleId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($roleId === null) {
            return;
        }

        $existing = User::query()->where('email', $email)->first();
        $forceLocal = app()->environment('local')
            && (bool) config('backup.bootstrap_admin.force_password_on_local', true);

        if ($existing instanceof User) {
            if (! $forceLocal) {
                return;
            }

            $existing->forceFill([
                'password' => $password,
                'status' => User::STATUS_ACTIVE,
                'role_id' => $roleId,
            ])->save();

            return;
        }

        User::query()->create([
            'user_number' => app(UserService::class)->nextUserNumber(),
            'name' => (string) config('backup.bootstrap_admin.name', 'Super Admin'),
            'email' => $email,
            'password' => $password,
            'role_id' => $roleId,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function captureUsers(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $rows = [];

        foreach (User::query()->with('role')->get() as $user) {
            $attrs = $user->getAttributes();
            unset($attrs['id']);
            $attrs['role_slug'] = $user->role?->slug;
            $rows[] = $attrs;
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function captureCustomers(): array
    {
        if (! Schema::hasTable('customers')) {
            return [];
        }

        $rows = [];

        foreach (Customer::query()->get() as $customer) {
            $attrs = $customer->getAttributes();
            unset($attrs['id']);
            $rows[] = $attrs;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $users
     */
    private function restoreMissingUsers(array $users): int
    {
        if ($users === [] || ! Schema::hasTable('users')) {
            return 0;
        }

        $count = 0;
        $columns = Schema::getColumnListing('users');

        foreach ($users as $row) {
            $email = (string) ($row['email'] ?? '');

            if ($email === '' || User::query()->where('email', $email)->exists()) {
                continue;
            }

            $roleSlug = $row['role_slug'] ?? null;
            unset($row['role_slug']);

            if (is_string($roleSlug) && $roleSlug !== '' && Schema::hasTable('roles')) {
                $roleId = Role::query()->where('slug', $roleSlug)->value('id');

                if ($roleId === null) {
                    continue;
                }

                $row['role_id'] = $roleId;
            } elseif (array_key_exists('role_id', $row) && $row['role_id'] === null) {
                continue;
            }

            if (empty($row['user_number']) || User::query()->where('user_number', $row['user_number'])->exists()) {
                $row['user_number'] = app(UserService::class)->nextUserNumber();
            }

            $payload = array_intersect_key($row, array_flip($columns));
            $payload['created_at'] = $payload['created_at'] ?? now();
            $payload['updated_at'] = now();

            // Password is already hashed — insert via query builder to avoid re-hashing.
            DB::table('users')->insert($payload);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $customers
     */
    private function restoreMissingCustomers(array $customers): int
    {
        if ($customers === [] || ! Schema::hasTable('customers')) {
            return 0;
        }

        $count = 0;
        $columns = Schema::getColumnListing('customers');

        foreach ($customers as $row) {
            $email = (string) ($row['email'] ?? '');

            if ($email === '') {
                continue;
            }

            if (Customer::query()->where('email', $email)->exists()) {
                continue;
            }

            if (! empty($row['customer_number']) && Customer::query()->where('customer_number', $row['customer_number'])->exists()) {
                $row['customer_number'] = 'LOC-'.strtoupper(substr(md5($email.microtime()), 0, 8));
            }

            $payload = array_intersect_key($row, array_flip($columns));
            $payload['created_at'] = $payload['created_at'] ?? now();
            $payload['updated_at'] = now();

            DB::table('customers')->insert($payload);
            $count++;
        }

        return $count;
    }
}
