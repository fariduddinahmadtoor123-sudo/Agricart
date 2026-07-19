<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;
use App\Models\SystemErrorRead;
use App\Models\User;
use App\Services\Notifications\TopbarNotificationService;
use Illuminate\Support\Facades\Schema;

class SystemErrorReadService
{
    public function markRead(SystemError $error, User $user): void
    {
        if (! Schema::hasTable('system_error_reads')) {
            return;
        }

        SystemErrorRead::query()->updateOrCreate(
            [
                'system_error_id' => $error->id,
                'user_id' => $user->id,
            ],
            [
                'occurrences_seen' => $error->occurrences,
                'read_at' => now(),
            ],
        );

        TopbarNotificationService::invalidateSystemErrors();
    }

    public function markCurrentCriticalErrorsRead(User $user): int
    {
        if (! Schema::hasTable('system_error_reads')) {
            return 0;
        }

        $count = 0;

        SystemError::query()
            ->where('status', SystemError::STATUS_OPEN)
            ->where('severity', SystemError::SEVERITY_CRITICAL)
            ->orderBy('id')
            ->chunkById(100, function ($errors) use ($user, &$count): void {
                foreach ($errors as $error) {
                    $this->markRead($error, $user);
                    $count++;
                }
            });

        return $count;
    }
}
