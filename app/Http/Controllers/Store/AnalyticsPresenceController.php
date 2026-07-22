<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSession;
use App\Services\Analytics\AnalyticsTrafficFilter;
use App\Services\Analytics\VisitorAnalyticsTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsPresenceController extends Controller
{
    public function update(Request $request, VisitorAnalyticsTracker $tracker, AnalyticsTrafficFilter $trafficFilter): Response
    {
        if (! config('analytics.enabled') || $trafficFilter->shouldSkip($request)) {
            return response()->noContent();
        }

        $token = $request->cookie((string) config('analytics.session_cookie'));

        if (! is_string($token) || preg_match('/\A[A-Za-z0-9_-]{32,128}\z/', $token) !== 1) {
            return response()->noContent();
        }

        AnalyticsSession::query()
            ->where('token_hash', $tracker->tokenHash($token))
            ->where('last_seen_at', '>=', now()->subHours(2))
            ->where('last_seen_at', '<=', now()->subSeconds(45))
            ->update(['last_seen_at' => now()]);

        return response()->noContent();
    }
}
