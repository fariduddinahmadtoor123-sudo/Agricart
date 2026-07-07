<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\Backup\BackupEntry;
use App\Services\Backup\BackupService;
use App\Services\Backup\RestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ManageBackups extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $uploadFile = null;

    public ?string $flashMessage = null;

    public string $flashType = 'success';

    public bool $busy = false;

    public ?string $restoreBackupId = null;

    public string $restoreConfirm = '';

    public bool $restoreSafetyBackup = true;

    public function createFull(): void
    {
        $this->runBackup(BackupService::TYPE_FULL);
    }

    public function createDatabase(): void
    {
        $this->runBackup(BackupService::TYPE_DATABASE);
    }

    public function uploadBackup(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $maxKilobytes = (int) ceil((int) config('backup.max_file_bytes', 0) / 1024);

        $this->validate([
            'uploadFile' => ['required', 'file', 'mimes:zip', 'max:' . max(1, $maxKilobytes)],
        ], [], [
            'uploadFile' => __('agricart.backups.upload.field_label'),
        ]);

        $this->busy = true;
        $this->flashMessage = null;

        try {
            $entry = app(BackupService::class)->importFromUpload(
                (string) $this->uploadFile->getRealPath(),
            );

            $this->uploadFile = null;
            $this->flash(__('agricart.backups.messages.imported', [
                'type' => __('agricart.backups.types.' . $entry->type),
                'size' => $entry->formattedSize(),
            ]), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.upload_failed'), 'danger');
        } catch (\Throwable $exception) {
            $this->flash($exception->getMessage(), 'danger');
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
    }

    public function confirmRestore(): void
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
            app(RestoreService::class)->restore($this->restoreBackupId, $this->restoreSafetyBackup);
            $this->restoreBackupId = null;
            $this->restoreConfirm = '';
            $this->flash(__('agricart.backups.messages.restored'), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.restore_failed'), 'danger');
        } catch (\Throwable $exception) {
            $this->flash($exception->getMessage(), 'danger');
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

    public function render(): View
    {
        $service = app(BackupService::class);

        return view('livewire.settings.manage-backups', [
            'backups' => $service->list(),
            'canManage' => $this->canManage(),
            'totalSize' => BackupEntry::formatBytes($service->totalSizeBytes()),
            'retentionCount' => (int) config('backup.retention_count', 10),
            'maxFileSize' => BackupEntry::formatBytes((int) config('backup.max_file_bytes')),
            'productsExportUrl' => route('admin.backups.export', ['type' => 'products']),
            'categoriesExportUrl' => route('admin.backups.export', ['type' => 'categories']),
        ]);
    }

    private function runBackup(string $type): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->busy = true;
        $this->flashMessage = null;

        try {
            $entry = app(BackupService::class)->create($type);
            $this->flash(__('agricart.backups.messages.created', [
                'type' => __('agricart.backups.types.' . $entry->type),
                'size' => $entry->formattedSize(),
            ]), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.create_failed'), 'danger');
        } catch (\Throwable $exception) {
            $this->flash($exception->getMessage(), 'danger');
        } finally {
            $this->busy = false;
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.backups.manage'));
    }

    private function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }
}
