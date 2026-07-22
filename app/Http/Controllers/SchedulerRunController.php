<?php

namespace App\Http\Controllers;

use App\Services\Deployment\SchedulerTickService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SchedulerRunController extends Controller
{
    public function __invoke(Request $request, SchedulerTickService $scheduler): SymfonyResponse
    {
        $expected = (string) config('deployment.scheduler_token');

        if ($expected === '' || ! hash_equals($expected, (string) $request->query('token', ''))) {
            abort(404);
        }

        Artisan::call('schedule:run');

        return response(
            trim(Artisan::output()) ?: 'schedule:run complete',
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
