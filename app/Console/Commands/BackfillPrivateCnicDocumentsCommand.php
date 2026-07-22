<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\PrivateDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillPrivateCnicDocumentsCommand extends Command
{
    protected $signature = 'users:backfill-private-cnic
                            {--dry-run : Report changes without writing or deleting files}
                            {--delete-public : Delete a legacy public original only after verifying the private copy}';

    protected $description = 'Copy legacy public staff CNIC documents to private storage and optionally remove verified originals';

    public function handle(PrivateDocumentStorage $documents): int
    {
        $copied = 0;
        $verified = 0;
        $deleted = 0;
        $missing = 0;
        $failed = 0;

        User::query()
            ->where(function ($query): void {
                $query->whereNotNull('cnic_front_path')->orWhereNotNull('cnic_back_path');
            })
            ->select(['id', 'cnic_front_path', 'cnic_back_path'])
            ->chunkById(100, function ($users) use ($documents, &$copied, &$verified, &$deleted, &$missing, &$failed): void {
                foreach ($users as $user) {
                    foreach (['cnic_front_path', 'cnic_back_path'] as $field) {
                        $path = $user->{$field};

                        if (! is_string($path) || ! str_starts_with($path, 'users/cnic/')) {
                            continue;
                        }

                        $private = Storage::disk('local');
                        $public = Storage::disk('public');
                        $privateExists = $private->exists($path);
                        $publicExists = $public->exists($path);

                        if (! $publicExists) {
                            if (! $privateExists) {
                                $missing++;
                                $this->warn("Missing CNIC for user {$user->id} ({$field}): {$path}");
                            }

                            continue;
                        }

                        if (! $privateExists) {
                            if ($this->option('dry-run')) {
                                $this->line("Would copy user {$user->id} ({$field}): {$path}");
                                $copied++;

                                continue;
                            }

                            if (! $documents->ensurePrivate($path, deletePublicWhenVerified: false)) {
                                $failed++;
                                $this->error("Failed to copy user {$user->id} ({$field}): {$path}");

                                continue;
                            }

                            $copied++;
                        }

                        if (! $documents->copiesMatch($path)) {
                            $failed++;
                            $this->error("Private copy verification failed for user {$user->id} ({$field}): {$path}");

                            continue;
                        }

                        $verified++;

                        if ($this->option('delete-public')) {
                            if ($this->option('dry-run')) {
                                $this->line("Would delete verified public CNIC for user {$user->id} ({$field}): {$path}");
                            } elseif ($public->delete($path)) {
                                $deleted++;
                            } else {
                                $failed++;
                                $this->error("Failed to delete verified public CNIC for user {$user->id} ({$field}): {$path}");
                            }
                        }
                    }
                }
            });

        $this->info(
            ($this->option('dry-run') ? 'Dry run: ' : '')
            ."{$copied} copied, {$verified} verified, {$deleted} public original(s) deleted, {$missing} missing, {$failed} failed."
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
