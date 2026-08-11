<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Search\AdminGlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopbarSearchController extends Controller
{
    public function suggest(Request $request, AdminGlobalSearchService $search): JsonResponse
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canUseAdmin(), 403);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) > 80) {
            $term = mb_substr($term, 0, 80);
        }

        if (mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'groups' => [],
            ]);
        }

        return response()->json($search->search($term, $user));
    }
}
