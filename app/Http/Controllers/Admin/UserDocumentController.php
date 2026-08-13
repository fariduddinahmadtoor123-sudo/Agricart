<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\PrivateDocumentStorage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDocumentController extends Controller
{
    public function __invoke(User $user, string $side, PrivateDocumentStorage $documents): StreamedResponse
    {
        $actor = auth()->user();

        abort_unless(
            $actor instanceof User && (
                $actor->isSuperAdmin()
                || $actor->hasPermission('approvals.view')
                || $actor->hasPermission('approvals.edit')
                || \App\Services\Access\ModulePageAccess::can($actor, 'staff', 'staff', 'view')
                || \App\Services\Access\ModulePageAccess::can($actor, 'staff', 'staff', 'edit')
                || \App\Services\Access\ModulePageAccess::can($actor, 'settings', 'users', 'view')
            ),
            403,
        );

        $field = match ($side) {
            'front' => 'cnic_front_path',
            'back' => 'cnic_back_path',
            default => abort(404),
        };
        $path = $user->{$field};

        abort_unless(is_string($path) && str_starts_with($path, 'users/cnic/'), 404);
        abort_unless($documents->ensurePrivate($path), 404);

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ],
        );
    }
}
