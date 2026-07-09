<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Access\BootstrapSuperAdminService;
use Illuminate\Console\Command;

class RepairBootstrapOwnerCommand extends Command
{
    protected $signature = 'agricart:repair-bootstrap-owner {email? : User email to promote when no Super Admin exists}';

    protected $description = 'Assign Super Admin to the system owner when no super admin user exists yet';

    public function handle(BootstrapSuperAdminService $bootstrap): int
    {
        if ($bootstrap->hasSuperAdminUser()) {
            $this->components->info('A Super Admin user already exists. Nothing to do.');

            return self::SUCCESS;
        }

        $email = $this->argument('email');
        $user = $bootstrap->repairOwner(is_string($email) ? $email : null);

        if ($user === null) {
            $candidate = filled($email)
                ? User::query()->where('email', $email)->first()
                : User::query()->orderBy('id')->first();

            if ($candidate instanceof User) {
                $user = $bootstrap->assignBootstrapOwnerIfEligible($candidate);
                if ($user->isSuperAdmin()) {
                    $this->components->info("Super Admin assigned to {$user->email} (user #{$user->id}).");

                    return self::SUCCESS;
                }
            }

            $this->components->error('No user found to promote. Register first or pass an email address.');

            return self::FAILURE;
        }

        $this->components->info("Super Admin assigned to {$user->email} (user #{$user->id}).");

        return self::SUCCESS;
    }
}
