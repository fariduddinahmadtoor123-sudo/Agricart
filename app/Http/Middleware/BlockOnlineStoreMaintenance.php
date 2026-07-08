<?php

namespace App\Http\Middleware;

use App\Services\SalesChannels\SalesChannelService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockOnlineStoreMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $service = app(SalesChannelService::class);

        if (! $service->isOnlineStoreInMaintenance()) {
            return $next($request);
        }

        return response()->view(
            'store.maintenance',
            $service->maintenancePageData(),
            Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
