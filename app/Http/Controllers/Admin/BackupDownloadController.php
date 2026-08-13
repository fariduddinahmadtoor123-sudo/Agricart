<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Backup\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function download(string $backupId, BackupService $backups): BinaryFileResponse
    {
        $this->authorizeBackupAccess();

        $entry = $backups->find($backupId);

        abort_if($entry === null, 404);

        return response()->download($backups->absolutePath($entry->filename), $entry->filename);
    }

    public function exportCsv(string $type, CsvExportService $exports): StreamedResponse
    {
        $this->authorizeBackupAccess();

        return match ($type) {
            'products' => $exports->downloadProducts(),
            'categories' => $exports->downloadCategories(),
            default => abort(404),
        };
    }

    private function authorizeBackupAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'backups', 'view')),
            403,
        );
    }
}
