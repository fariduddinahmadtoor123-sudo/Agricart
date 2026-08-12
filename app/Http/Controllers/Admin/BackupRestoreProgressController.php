<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Backup\RestoreProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupRestoreProgressController extends Controller
{
    public function __invoke(Request $request, RestoreProgress $progress): JsonResponse
    {
        return response()->json($progress->snapshotForToken((string) $request->query('token', '')));
    }
}
