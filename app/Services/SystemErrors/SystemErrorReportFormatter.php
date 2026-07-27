<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;

class SystemErrorReportFormatter
{
    public function __construct(
        private readonly SystemErrorGuidance $guidance,
    ) {}

    public function format(SystemError $error): string
    {
        $guide = $this->guidance->for($error);

        $lines = [
            'Agricart System Error Report',
            '',
            '=== آسان وضاحت ===',
            'نوعیت: '.$guide['title'],
            'ذریعہ: '.$guide['bucket_label'],
            'پریشانی: '.$guide['worry_label'],
            'کیا ہوا: '.$guide['summary'],
            'کیا کریں: '.$guide['action'],
            '',
            '=== Plain summary (English) ===',
            'Nature: '.$guide['title_en'],
            'Source type: '.$guide['bucket_label_en'],
            'Worry level: '.$guide['worry_label_en'],
            'What happened: '.$guide['summary_en'],
            'What to do: '.$guide['action_en'],
            '',
            '=== Technical details (for developers) ===',
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

        if ($error->status === SystemError::STATUS_RESOLVED) {
            $lines[] = 'Resolution type: '.($error->resolution_type ?: 'unknown');
            $lines[] = 'Resolution: '.($error->resolution_note ?: 'No resolution note recorded.');
            $lines[] = 'Verified at: '.($error->verified_at?->toIso8601String() ?: 'unknown');
        }

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
