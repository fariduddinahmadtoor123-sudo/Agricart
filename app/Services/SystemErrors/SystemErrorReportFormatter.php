<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;

class SystemErrorReportFormatter
{
    public function format(SystemError $error): string
    {
        $lines = [
            'Agricart System Error Report',
            'Reference: '.$error->reference,
            'Status: '.$error->status,
            'Severity: '.$error->severity,
            'Source: '.$error->source,
            'Environment: '.($error->environment ?: 'unknown'),
            'Release: '.($error->release ?: 'unknown'),
            'Exception: '.($error->exception_class ?: 'unknown'),
            'Message: '.$error->message,
            'Location: '.($error->file ?: 'unknown').($error->line ? ':'.$error->line : ''),
            'Request: '.trim(($error->request_method ?: '').' '.($error->request_path ?: 'unknown')),
            'Route: '.($error->route_name ?: 'unknown'),
            'HTTP status: '.($error->http_status ?: 'unknown'),
            'Occurrences: '.number_format($error->occurrences),
            'First seen: '.($error->first_seen_at?->toIso8601String() ?: 'unknown'),
            'Last seen: '.($error->last_seen_at?->toIso8601String() ?: 'unknown'),
        ];

        if (filled($error->context)) {
            $lines[] = 'Context: '.json_encode($error->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (filled($error->trace)) {
            $lines[] = '';
            $lines[] = 'Sanitized stack trace:';
            $lines[] = $error->trace;
        }

        return implode(PHP_EOL, $lines);
    }
}
