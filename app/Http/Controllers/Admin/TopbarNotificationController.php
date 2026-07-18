<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notifications\TopbarNotificationService;
use Illuminate\Http\JsonResponse;

class TopbarNotificationController extends Controller
{
    public function __invoke(TopbarNotificationService $notifications): JsonResponse
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canUseAdmin(), 403);

        return response()->json($notifications->summary());
    }
}
