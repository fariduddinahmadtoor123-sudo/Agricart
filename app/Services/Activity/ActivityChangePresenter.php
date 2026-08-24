<?php

namespace App\Services\Activity;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;

class ActivityChangePresenter
{
    /**
     * Turn raw JSON properties into plain rows a non-developer can read.
     *
     * @return list<array{field: string, before: string, after: string}>
     */
    public function rows(ActivityLog $log): array
    {
        $properties = $log->properties;

        if (! is_array($properties) || $properties === []) {
            return [];
        }

        if ($log->event === ActivityEvent::FailedLogin || $log->event === ActivityEvent::Login || $log->event === ActivityEvent::Logout) {
            return $this->authRows($properties);
        }

        if ($log->event === ActivityEvent::Converted) {
            return $this->scalarRows($properties);
        }

        $attributes = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];

        if ($attributes === [] && $old === []) {
            return $this->scalarRows($properties);
        }

        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($attributes))));
        $rows = [];

        foreach ($keys as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $before = array_key_exists($key, $old) ? $this->stringify($old[$key]) : '—';
            $after = array_key_exists($key, $attributes) ? $this->stringify($attributes[$key]) : '—';

            if ($before === $after) {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel($key),
                'before' => $before,
                'after' => $after,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return list<array{field: string, before: string, after: string}>
     */
    private function authRows(array $properties): array
    {
        $rows = [];

        if (isset($properties['email'])) {
            $rows[] = [
                'field' => __('agricart.activity.detail.field_email'),
                'before' => '—',
                'after' => $this->stringify($properties['email']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return list<array{field: string, before: string, after: string}>
     */
    private function scalarRows(array $properties): array
    {
        $rows = [];

        foreach ($properties as $key => $value) {
            if (! is_string($key) || in_array($key, ['attributes', 'old'], true)) {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel($key),
                'before' => '—',
                'after' => $this->stringify($value),
            ];
        }

        return $rows;
    }

    private function fieldLabel(string $key): string
    {
        $langKey = 'agricart.activity.detail.fields.'.$key;
        $label = __($langKey);

        if ($label !== $langKey) {
            return $label;
        }

        return str_replace('_', ' ', ucfirst($key));
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('agricart.activity.detail.yes') : __('agricart.activity.detail.no');
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);

            return $text === '' ? '—' : $text;
        }

        return '—';
    }
}
