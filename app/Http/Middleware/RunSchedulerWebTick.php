<?php

namespace App\Http\Middleware;

use App\Services\Deployment\SchedulerTickService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RunSchedulerWebTick
{
    public function __construct(
        private readonly SchedulerTickService $scheduler,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $this->scheduler->enabled()
            && ! $request->isMethod('OPTIONS')
            && ! $request->routeIs('internal.schedule-run')
            && ! $request->is('up')
        ) {
            defer(fn () => $this->scheduler->tick());
        }

        return $response;
    }
}
