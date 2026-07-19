<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;
use Illuminate\Support\Facades\Schema;

class SystemErrorLifecycleService
{
    public function reconcile(): int
    {
        if (! Schema::hasTable('system_errors')) {
            return 0;
        }

        $resolved = 0;

        if ($this->publicStorageIsAvailable()) {
            SystemError::query()
                ->where('status', SystemError::STATUS_OPEN)
                ->where('exception_class', \Error::class)
                ->each(function (SystemError $error) use (&$resolved): void {
                    $command = (string) data_get($error->context, 'command');

                    if (
                        ! str_contains($command, 'storage:link')
                        || ! str_contains($error->message, 'Call to undefined function Illuminate\\Filesystem\\exec()')
                    ) {
                        return;
                    }

                    $this->resolveAutomatically(
                        $error,
                        'environment_verified',
                        'The hosting provider disables PHP exec(), but the public storage link is present and usable. Agricart now uses an exec-free storage link command.',
                    );
                    $resolved++;
                });
        }

        return $resolved;
    }

    public function resolveAutomatically(SystemError $error, string $type, string $note): void
    {
        $error->update([
            'status' => SystemError::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by_id' => null,
            'resolution_type' => $type,
            'resolution_note' => $note,
            'verified_at' => now(),
        ]);
    }

    private function publicStorageIsAvailable(): bool
    {
        $path = public_path('storage');
        $target = storage_path('app/public');
        $resolvedPath = realpath($path);
        $resolvedTarget = realpath($target);

        return is_link($path)
            || (
                is_string($resolvedPath)
                && is_string($resolvedTarget)
                && strcasecmp($resolvedPath, $resolvedTarget) === 0
            );
    }
}
