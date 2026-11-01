<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalVaultFile;
use App\Models\User;
use App\Services\Security\PrivateDocumentStorage;
use App\Services\Vault\PersonalVaultService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalVaultFileController extends Controller
{
    public function __invoke(
        PersonalVaultFile $file,
        PersonalVaultService $vault,
        PrivateDocumentStorage $documents,
    ): StreamedResponse {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        $vault->assertCanManage($actor, $file);

        $path = ltrim(str_replace('\\', '/', (string) $file->path), '/');
        abort_unless(str_starts_with($path, 'personal-vault/'), 404);
        abort_unless($documents->ensurePrivate($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $downloadName = $file->original_name !== '' ? $file->original_name : basename($path);

        return Storage::disk('local')->response(
            $path,
            $downloadName,
            [
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ],
        );
    }
}
