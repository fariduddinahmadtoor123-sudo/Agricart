<?php

namespace App\Services\Access;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionInvalidationService
{
    /**
     * Wipe all database sessions for an admin/staff user (password reset by another actor).
     */
    public function wipeAllForUser(User $user): void
    {
        $user->forceFill(['remember_token' => null])->saveQuietly();

        if (! $this->databaseSessionsEnabled()) {
            return;
        }

        DB::table($this->sessionsTable())
            ->where('user_id', $user->getKey())
            ->delete();
    }

    /**
     * Wipe other admin sessions, keeping the current browser session.
     */
    public function wipeOthersForUser(User $user, ?string $exceptSessionId = null): void
    {
        $user->forceFill(['remember_token' => null])->saveQuietly();

        if (! $this->databaseSessionsEnabled()) {
            return;
        }

        $query = DB::table($this->sessionsTable())
            ->where('user_id', $user->getKey());

        $exceptSessionId ??= session()->getId();

        if (is_string($exceptSessionId) && $exceptSessionId !== '') {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();
    }

    /**
     * After an authenticated customer password change: keep this device, kill others.
     *
     * Do not call SessionGuard::logoutOtherDevices() here — the password is already
     * updated, so the guard's in-memory user fails Hash::check against the new plain value.
     */
    public function invalidateOtherCustomerDevices(Customer $customer, string $plainPassword): void
    {
        unset($plainPassword);

        $fresh = $customer->fresh() ?? $customer;
        $fresh->setRememberToken(\Illuminate\Support\Str::random(60));
        $fresh->saveQuietly();

        $this->wipeCustomerSessionsByPayload((int) $fresh->getKey());

        if (
            Auth::guard('customer')->check()
            && (int) Auth::guard('customer')->id() === (int) $fresh->getKey()
            && request()->hasSession()
        ) {
            Auth::guard('customer')->setUser($fresh);
            request()->session()->put(
                'password_hash_'.Auth::getDefaultDriver(),
                (string) $fresh->getAuthPassword(),
            );
        }
    }

    /**
     * After a password-reset link (customer may not be logged in): kill all their sessions.
     */
    public function wipeAllForCustomer(Customer $customer): void
    {
        $customer->setRememberToken(\Illuminate\Support\Str::random(60));
        $customer->saveQuietly();

        $this->wipeCustomerSessionsByPayload((int) $customer->getKey());
    }

    private function wipeCustomerSessionsByPayload(int $customerId): void
    {
        if (! $this->databaseSessionsEnabled()) {
            return;
        }

        $table = $this->sessionsTable();
        $except = session()->getId();
        $loginNeedle = 'login_customer_';

        DB::table($table)
            ->orderBy('id')
            ->select(['id', 'payload'])
            ->chunkById(100, function ($rows) use ($table, $except, $loginNeedle, $customerId): void {
                $deleteIds = [];

                foreach ($rows as $row) {
                    if (is_string($except) && $except !== '' && $row->id === $except) {
                        continue;
                    }

                    $haystack = $this->decodeSessionPayload((string) $row->payload);

                    if (! str_contains($haystack, $loginNeedle)) {
                        continue;
                    }

                    // PHP session encode: login_customer_*|i:{id}; — also plain serialize arrays.
                    if (
                        str_contains($haystack, '|i:'.$customerId.';')
                        || str_contains($haystack, ';i:'.$customerId.';')
                        || preg_match('/login_customer_[a-f0-9]+\|i:'.preg_quote((string) $customerId, '/').';/', $haystack)
                    ) {
                        $deleteIds[] = $row->id;
                    }
                }

                if ($deleteIds !== []) {
                    DB::table($table)->whereIn('id', $deleteIds)->delete();
                }
            });
    }

    private function decodeSessionPayload(string $payload): string
    {
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return $payload;
        }

        if (filter_var(config('session.encrypt'), FILTER_VALIDATE_BOOL)) {
            try {
                return decrypt($decoded);
            } catch (\Throwable) {
                return $decoded;
            }
        }

        return $decoded;
    }

    private function databaseSessionsEnabled(): bool
    {
        return config('session.driver') === 'database'
            && Schema::hasTable($this->sessionsTable());
    }

    private function sessionsTable(): string
    {
        return (string) config('session.table', 'sessions');
    }
}
