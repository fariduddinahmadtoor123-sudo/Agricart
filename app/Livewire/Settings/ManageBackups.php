<?php

namespace App\Livewire\Settings;

use App\Models\BackupScheduleSetting;
use App\Models\User;
use App\Services\Backup\BackupEntry;
use App\Services\Backup\BackupScheduleRegistrar;
use App\Services\Backup\BackupService;
use App\Services\Backup\RestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ManageBackups extends Component
{
    public ?string $flashMessage = null;

    public string $flashType = 'success';

    public bool $busy = false;

    public ?string $restoreBackupId = null;

    public string $restoreConfirm = '';

    public bool $restoreSafetyBackup = true;

    public bool $preserveLocalLogins = true;

    public bool $scheduleEnabled = true;

    public string $scheduleType = BackupScheduleSetting::TYPE_FULL;

    public string $scheduleFrequency = BackupScheduleSetting::FREQUENCY_WEEKLY;

    public string $scheduleTime = '02:00';

    public int $scheduleWeekday = 0;

    public bool $scheduleDatabaseDailyEnabled = false;

    public string $scheduleDatabaseDailyTime = '01:30';

    public function mount(BackupScheduleRegistrar $scheduler): void
    {
        if (session()->has('backup_upload_message')) {
            $this->flashMessage = (string) session('backup_upload_message');
            $this->flashType = (string) session('backup_upload_type', 'success');
        }

        $this->preserveLocalLogins = (bool) config('backup.preserve_local_logins', true);
        $this->hydrateScheduleForm($scheduler->resolved());
    }

    public function saveSchedule(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $validated = $this->validate([
            'scheduleEnabled' => ['boolean'],
            'scheduleType' => ['required', Rule::in(BackupScheduleSetting::TYPES)],
            'scheduleFrequency' => ['required', Rule::in(BackupScheduleSetting::FREQUENCIES)],
            'scheduleTime' => ['required', 'date_format:H:i'],
            'scheduleWeekday' => ['required', 'integer', 'between:0,6'],
            'scheduleDatabaseDailyEnabled' => ['boolean'],
            'scheduleDatabaseDailyTime' => ['required', 'date_format:H:i'],
        ]);

        $row = BackupScheduleSetting::current();
        $row->fill([
            'enabled' => (bool) $validated['scheduleEnabled'],
            'type' => $validated['scheduleType'],
            'frequency' => $validated['scheduleFrequency'],
            'time' => $validated['scheduleTime'],
            'weekday' => (int) $validated['scheduleWeekday'],
            'database_daily_enabled' => (bool) $validated['scheduleDatabaseDailyEnabled'],
            'database_daily_time' => $validated['scheduleDatabaseDailyTime'],
        ])->save();

        $this->hydrateScheduleForm(app(BackupScheduleRegistrar::class)->resolved());
        $this->flash(__('agricart.backups.messages.schedule_saved'), 'success');
    }

    public function createFull(): void
    {
        $this->runBackup(BackupService::TYPE_FULL);
    }

    public function createDatabase(): void
    {
        $this->runBackup(BackupService::TYPE_DATABASE);
    }

    public function importIncoming(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->busy = true;
        $this->flashMessage = null;

        try {
            $results = app(BackupService::class)->importIncomingArchives();

            if ($results === []) {
                $this->flash(__('agricart.backups.messages.incoming_empty'), 'warning');

                return;
            }

            $failed = array_filter($results, fn (array $row): bool => ! $row['ok']);
            $succeeded = array_filter($results, fn (array $row): bool => $row['ok']);

            if ($succeeded !== [] && $failed === []) {
                $this->flash(__('agricart.backups.messages.incoming_batch_ok', [
                    'count' => count($succeeded),
                ]), 'success');

                return;
            }

            if ($succeeded === [] && $failed !== []) {
                $this->flash($failed[array_key_first($failed)]['message'], 'danger');

                return;
            }

            $this->flash(__('agricart.backups.messages.incoming_batch_mixed', [
                'ok' => count($succeeded),
                'failed' => count($failed),
            ]), 'warning');
        } catch (\Throwable $exception) {
            report($exception);
            $this->flash(__('agricart.backups.messages.upload_failed'), 'danger');
        } finally {
            $this->busy = false;
        }
    }

    public function openRestore(string $backupId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->restoreBackupId = $backupId;
        $this->restoreConfirm = '';
        $this->restoreSafetyBackup = true;
        $this->preserveLocalLogins = (bool) config('backup.preserve_local_logins', true);
    }

    public function confirmRestore(string $progressToken = ''): void
    {
        if (! $this->canManage() || $this->restoreBackupId === null) {
            return;
        }

        if (mb_strtoupper(trim($this->restoreConfirm)) !== 'RESTORE') {
            $this->flash(__('agricart.backups.messages.restore_confirm_invalid'), 'danger');

            return;
        }

        $this->busy = true;

        try {
            app(RestoreService::class)->restore(
                $this->restoreBackupId,
                $this->restoreSafetyBackup,
                $this->preserveLocalLogins,
                $progressToken !== '' ? $progressToken : null,
            );
            $this->restoreBackupId = null;
            $this->restoreConfirm = '';

            $merged = (int) session()->pull('backup_restore_merged_logins', 0);
            $replaced = (int) session()->pull('backup_restore_replaced_logins', 0);
            $clearedAi = (int) session()->pull('backup_restore_cleared_ai_keys', 0);
            $message = __('agricart.backups.messages.restored');

            if ($merged > 0) {
                $message .= ' '.__('agricart.backups.messages.restored_logins_merged', ['count' => $merged]);
            }
            if ($replaced > 0) {
                $message .= ' '.__('agricart.backups.messages.restored_logins_replaced', ['count' => $replaced]);
            }

            if ($clearedAi > 0) {
                $message .= ' '.__('agricart.backups.messages.restored_ai_keys_cleared', ['count' => $clearedAi]);
            }

            $bootstrapEmail = (string) config('backup.bootstrap_admin.email', '');

            if ($bootstrapEmail !== '') {
                $message .= ' '.__('agricart.backups.messages.restored_bootstrap_admin', [
                    'email' => $bootstrapEmail,
                ]);
            }

            $this->flash($message, $clearedAi > 0 ? 'warning' : 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.restore_failed'), 'danger');
        } catch (\Throwable $exception) {
            $this->flash(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->busy = false;
        }
    }

    public function cancelRestore(): void
    {
        $this->restoreBackupId = null;
        $this->restoreConfirm = '';
    }

    public function delete(string $backupId): void
    {
        if (! $this->canManage()) {
            return;
        }

        app(BackupService::class)->delete($backupId);
        $this->flash(__('agricart.backups.messages.deleted'), 'success');
    }

    public function render(BackupScheduleRegistrar $scheduler): View
    {
        $service = app(BackupService::class);
        $resolved = $scheduler->resolved();

        return view('livewire.settings.manage-backups', [
            'backups' => $service->list(),
            'canManage' => $this->canManage(),
            'scheduleSummary' => $resolved['summary'],
            'hostCronCommand' => $scheduler->hostCronCommand(),
            'schedulerConfigured' => $scheduler->schedulerLooksConfigured(),
            'totalSize' => BackupEntry::formatBytes($service->totalSizeBytes()),
            'retentionCount' => (int) config('backup.retention_count', 10),
            'maxFileSize' => BackupEntry::formatBytes((int) config('backup.max_file_bytes')),
            'effectiveUploadLimit' => BackupEntry::formatBytes(BackupEntry::effectiveUploadLimitBytes()),
            'phpUploadLimit' => BackupEntry::formatBytes(BackupEntry::phpRequestBodyLimitBytes()),
            'uploadLimitBelowBackupMax' => BackupEntry::effectiveUploadLimitBytes() < (int) config('backup.max_file_bytes'),
            'uploadUrl' => route('admin.backups.upload'),
            'incomingPath' => app(BackupService::class)->incomingDirectoryAbsolute(),
            'databaseDriver' => config('database.connections.'.config('database.default').'.driver'),
            'productsExportUrl' => route('admin.backups.export', ['type' => 'products']),
            'categoriesExportUrl' => route('admin.backups.export', ['type' => 'categories']),
        ]);
    }

    /**
     * @param  array{
     *     enabled: bool,
     *     type: string,
     *     frequency: string,
     *     time: string,
     *     weekday: int,
     *     database_daily_enabled: bool,
     *     database_daily_time: string
     * }  $resolved
     */
    private function hydrateScheduleForm(array $resolved): void
    {
        $this->scheduleEnabled = $resolved['enabled'];
        $this->scheduleType = $resolved['type'];
        $this->scheduleFrequency = $resolved['frequency'];
        $this->scheduleTime = $resolved['time'];
        $this->scheduleWeekday = $resolved['weekday'];
        $this->scheduleDatabaseDailyEnabled = $resolved['database_daily_enabled'];
        $this->scheduleDatabaseDailyTime = $resolved['database_daily_time'];
    }

    private function runBackup(string $type): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->busy = true;
        $this->flashMessage = null;

        try {
            $entry = app(BackupService::class)->create($type, ['source' => 'manual']);
            $this->flash(__('agricart.backups.messages.created', [
                'type' => __('agricart.backups.types.' . $entry->type),
                'size' => $entry->formattedSize(),
            ]), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.create_failed'), 'danger');
        } catch (\Throwable $exception) {
            $this->flash(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->busy = false;
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'backups', 'edit') || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'backups', 'create'));
    }

    private function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }
}
