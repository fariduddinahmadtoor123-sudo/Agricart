<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\SalesChannels\FacebookCatalogFeedService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacebookCatalogFeedController extends Controller
{
    public function __invoke(Request $request, FacebookCatalogFeedService $feed): Response
    {
        if (! $feed->isFeedEnabled()) {
            abort(404);
        }

        if (! $feed->tokenMatches($request->query('token'))) {
            abort(403, 'Invalid Facebook catalog feed token.');
        }

        $xml = $feed->buildXml();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
