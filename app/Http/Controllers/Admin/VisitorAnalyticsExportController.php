<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\VisitorAnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorAnalyticsExportController extends Controller
{
    public function __invoke(Request $request, VisitorAnalyticsReportService $reports): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'store', 'visitor-analytics', 'view')),
            403,
        );

        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'device' => ['nullable', 'string', 'max:24'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);
        $from = CarbonImmutable::parse($validated['from'])->startOfDay();
        $to = CarbonImmutable::parse($validated['to'])->endOfDay();

        abort_if($from->diffInDays($to) > 90, 422, 'Analytics export range cannot exceed 90 days.');

        $filters = array_filter([
            'country' => $validated['country'] ?? null,
            'city' => $validated['city'] ?? null,
            'device' => $validated['device'] ?? null,
            'source' => $validated['source'] ?? null,
        ]);
        $rows = $reports->exportRows($from, $to, $filters);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Section', 'Label', 'Page Views', 'Visitors', 'Sessions']);

            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['section'],
                    $row['label'],
                    $row['page_views'],
                    $row['visitors'],
                    $row['sessions'],
                ]);
            }

            fclose($stream);
        }, "visitor-analytics-{$validated['from']}-to-{$validated['to']}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
