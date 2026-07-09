<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Clusters\Settings\BackupsPage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Backup\BackupEntry;
use App\Services\Backup\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BackupUploadController extends Controller
{
    public function store(Request $request, BackupService $backups): JsonResponse|RedirectResponse
    {
        $this->authorizeBackupAccess();

        set_time_limit(0);

        $maxKilobytes = (int) ceil((int) config('backup.max_file_bytes', 0) / 1024);

        try {
            $validated = $request->validate([
                'backup_zip' => ['required', 'file', 'mimes:zip', 'max:'.max(1, $maxKilobytes)],
            ], [], [
                'backup_zip' => __('agricart.backups.upload.field_label'),
            ]);
        } catch (ValidationException $exception) {
            return $this->respond($request, collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.upload_failed'), 'danger', 422);
        }

        $uploaded = $validated['backup_zip'];
        $path = $uploaded->getRealPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return $this->respond($request, __('agricart.backups.messages.upload_missing'), 'danger', 422);
        }

        try {
            $entry = $backups->importFromUpload($path);

            return $this->respond($request, __('agricart.backups.messages.imported', [
                'type' => __('agricart.backups.types.'.$entry->type),
                'size' => $entry->formattedSize(),
            ]), 'success', 200, $entry);
        } catch (ValidationException $exception) {
            return $this->respond($request, collect($exception->errors())->flatten()->first() ?: __('agricart.backups.messages.upload_failed'), 'danger', 422);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->respond($request, __('agricart.backups.messages.upload_failed'), 'danger', 500);
        }
    }

    private function respond(Request $request, string $message, string $type, int $status, ?BackupEntry $entry = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'type' => $type,
                'entry_id' => $entry?->id,
            ], $status);
        }

        return redirect(BackupsPage::getUrl())->with([
            'backup_upload_message' => $message,
            'backup_upload_type' => $type,
        ]);
    }

    private function authorizeBackupAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || $user->hasPermission('settings.backups.manage')),
            403,
        );
    }
}
