<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDocumentController extends Controller
{
    public function __invoke(User $user, string $side): StreamedResponse
    {
        $actor = auth()->user();

        abort_unless(
            $actor instanceof User && (
                $actor->isSuperAdmin()
                || $actor->hasPermission('approvals.view')
                || $actor->hasPermission('approvals.edit')
                || $actor->hasPermission('staff.view')
                || $actor->hasPermission('staff.edit')
                || $actor->hasPermission('settings.users.manage')
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

        $disk = match (true) {
            Storage::disk('local')->exists($path) => 'local',
            Storage::disk('public')->exists($path) => 'public',
            default => null,
        };

        abort_unless($disk !== null, 404);

        return Storage::disk($disk)->response(
            $path,
            basename($path),
            [
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
