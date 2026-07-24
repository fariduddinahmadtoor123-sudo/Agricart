<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SetUserPasswordCommand extends Command
{
    protected $signature = 'agricart:set-user-password
        {email : User email address}
        {--password= : New password (min 8 characters)}
        {--generate : Generate a random temporary password}';

    protected $description = 'Set an admin/staff user password and clear their other sessions';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user instanceof User) {
            $this->components->error("No user found for {$email}.");

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            return self::FAILURE;
        }

        // Plain value — User `hashed` cast hashes once.
        $user->forceFill([
            'password' => $password,
            'remember_token' => null,
        ])->save();

        if (
            (config('session.driver') === 'database')
            && Schema::hasTable((string) config('session.table', 'sessions'))
        ) {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }

        $freshHash = (string) $user->fresh()?->getRawOriginal('password');

        if ($freshHash === '' || ! Hash::check($password, $freshHash)) {
            $this->components->error('Password was saved but Hash::check failed. Aborting.');

            return self::FAILURE;
        }

        $this->components->info("Password updated for {$user->email} (user #{$user->id}).");

        if ($this->option('generate')) {
            $this->components->warn("Temporary password: {$password}");
        }

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        if ($this->option('generate')) {
            return 'Agri-'.strtoupper(bin2hex(random_bytes(4))).'!'.random_int(10, 99);
        }

        $password = (string) ($this->option('password') ?: '');

        if ($password === '' && $this->input->isInteractive()) {
            $password = (string) $this->secret('New password');
        }

        if (strlen($password) < 8) {
            $this->components->error('Password must be at least 8 characters. Pass --password=... or --generate.');

            return null;
        }

        return $password;
    }
}
