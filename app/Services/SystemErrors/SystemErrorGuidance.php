<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;

class SystemErrorGuidance
{
    /**
     * Plain-language guidance for non-technical staff.
     * Always includes Urdu + English so staff can understand without switching locale.
     *
     * @return array{
     *     bucket: string,
     *     worry: string,
     *     bucket_label: string,
     *     worry_label: string,
     *     bucket_label_en: string,
     *     worry_label_en: string,
     *     title: string,
     *     summary: string,
     *     action: string,
     *     title_en: string,
     *     summary_en: string,
     *     action_en: string,
     *     pattern: string
     * }
     */
    public function for(SystemError $error): array
    {
        $pattern = $this->detectPattern($error);
        $bucket = $this->bucketFor($pattern, $error);
        $worry = $this->worryFor($pattern, $error, $bucket);

        return [
            'bucket' => $bucket,
            'worry' => $worry,
            'bucket_label' => __('agricart.system_errors.guidance.buckets.'.$bucket, [], 'ur'),
            'worry_label' => __('agricart.system_errors.guidance.worry_levels.'.$worry, [], 'ur'),
            'bucket_label_en' => __('agricart.system_errors.guidance.buckets.'.$bucket, [], 'en'),
            'worry_label_en' => __('agricart.system_errors.guidance.worry_levels.'.$worry, [], 'en'),
            'title' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.title', [], 'ur'),
            'summary' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.summary', [], 'ur'),
            'action' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.action', [], 'ur'),
            'title_en' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.title', [], 'en'),
            'summary_en' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.summary', [], 'en'),
            'action_en' => __('agricart.system_errors.guidance.patterns.'.$pattern.'.action', [], 'en'),
            'pattern' => $pattern,
        ];
    }

    /**
     * @return array{bucket: string, worry: string, title: string, summary: string, action: string}
     */
    public function forFileEntry(array $entry): array
    {
        $severity = (string) ($entry['severity'] ?? 'error');
        $message = (string) ($entry['message'] ?? '');
        $fake = new SystemError([
            'severity' => in_array($severity, [
                SystemError::SEVERITY_WARNING,
                SystemError::SEVERITY_ERROR,
                SystemError::SEVERITY_CRITICAL,
            ], true) ? $severity : SystemError::SEVERITY_ERROR,
            'message' => $message,
            'exception_class' => null,
            'source' => 'log',
            'environment' => app()->environment(),
        ]);

        return $this->for($fake);
    }

    private function detectPattern(SystemError $error): string
    {
        $haystack = strtolower(trim(implode(' ', array_filter([
            $error->exception_class,
            $error->message,
            is_array($error->context) ? json_encode($error->context) : null,
        ]))));

        if ($haystack === '') {
            return 'generic';
        }

        if (
            str_contains($haystack, 'incomplete object')
            || str_contains($haystack, 'unserialize')
            || (str_contains($haystack, 'eloquent') && str_contains($haystack, 'collection'))
        ) {
            return 'permission_cache';
        }

        if (
            str_contains($haystack, 'sqlstate')
            || str_contains($haystack, 'connection refused')
            || str_contains($haystack, 'too many connections')
            || str_contains($haystack, 'server has gone away')
            || str_contains($haystack, 'access denied for user')
        ) {
            return 'database';
        }

        if (
            str_contains($haystack, 'no space left')
            || str_contains($haystack, 'disk quota')
            || str_contains($haystack, 'allowed memory size')
            || str_contains($haystack, 'maximum execution time')
            || str_contains($haystack, 'permission denied')
            || str_contains($haystack, 'failed to open stream')
        ) {
            return 'hosting_resources';
        }

        if (
            str_contains($haystack, 'curl error')
            || str_contains($haystack, 'connection timed out')
            || str_contains($haystack, 'ssl')
            || str_contains($haystack, 'could not resolve host')
            || str_contains($haystack, 'openrouter')
            || str_contains($haystack, 'smtp')
        ) {
            return 'external_service';
        }

        if (
            str_contains($haystack, 'csrf')
            || str_contains($haystack, '419')
            || str_contains($haystack, 'token mismatch')
        ) {
            return 'session_csrf';
        }

        if (
            (str_contains($haystack, 'class') && str_contains($haystack, 'not found'))
            || str_contains($haystack, 'undefined method')
            || str_contains($haystack, 'undefined variable')
            || str_contains($haystack, 'syntax error')
        ) {
            return 'application_bug';
        }

        return 'generic';
    }

    private function bucketFor(string $pattern, SystemError $error): string
    {
        return match ($pattern) {
            'hosting_resources', 'database' => 'hosting',
            'external_service' => 'external',
            'permission_cache', 'application_bug', 'session_csrf' => 'agricart',
            default => filled($error->file) && str_starts_with((string) $error->file, 'app/')
                ? 'agricart'
                : 'unclear',
        };
    }

    private function worryFor(string $pattern, SystemError $error, string $bucket): string
    {
        if ($error->severity === SystemError::SEVERITY_WARNING) {
            return 'low';
        }

        if ($pattern === 'permission_cache' || $pattern === 'application_bug') {
            return 'medium';
        }

        if ($error->severity === SystemError::SEVERITY_CRITICAL || $bucket === 'hosting') {
            return 'high';
        }

        return 'medium';
    }
}
