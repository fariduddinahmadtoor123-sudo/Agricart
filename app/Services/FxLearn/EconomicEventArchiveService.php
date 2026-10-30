<?php

namespace App\Services\FxLearn;

use App\Models\FxLearnEconomicEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Persists calendar prints so Actual/Previous/Forecast survive cache expiry and weak free feeds.
 */
class EconomicEventArchiveService
{
    /**
     * Upsert live rows that carry any useful figures (or are high-impact scheduled prints).
     *
     * @param  list<array<string, mixed>>  $events
     */
    public function remember(array $events): int
    {
        $saved = 0;

        foreach ($events as $ev) {
            if (! is_array($ev)) {
                continue;
            }

            if (($ev['src'] ?? '') === 'practice' || str_starts_with((string) ($ev['id'] ?? ''), 'practice-')) {
                continue;
            }

            $fingerprint = $this->fingerprint($ev);

            if ($fingerprint === null) {
                continue;
            }

            $previous = $this->clean((string) ($ev['previous'] ?? ''));
            $forecast = $this->clean((string) ($ev['forecast'] ?? ''));
            $actual = $this->clean((string) ($ev['actual'] ?? ''));

            $hasFigures = $previous !== null || $forecast !== null || $actual !== null;
            $isHigh = ($ev['impact'] ?? '') === 'high';

            if (! $hasFigures && ! $isHigh) {
                continue;
            }

            try {
                $atMs = (int) ($ev['at'] ?? 0);
                $eventDate = CarbonImmutable::createFromTimestampMs($atMs, 'UTC')->toDateString();

                /** @var FxLearnEconomicEvent $row */
                $row = FxLearnEconomicEvent::query()->firstOrNew(['fingerprint' => $fingerprint]);

                $row->external_id = (string) ($ev['id'] ?? $row->external_id);
                $row->title = (string) ($ev['title'] ?? $row->title ?? '');
                $row->ccy = strtoupper((string) ($ev['ccy'] ?? $row->ccy ?? 'USD'));
                $row->country = (string) ($ev['country'] ?? $row->country ?? $row->ccy);
                $row->impact = (string) ($ev['impact'] ?? $row->impact ?? 'med');
                $row->at_ms = $atMs > 0 ? $atMs : (int) ($row->at_ms ?? 0);
                $row->event_date = $eventDate;
                $row->unit = trim((string) ($ev['unit'] ?? $row->unit ?? '')) ?: null;
                $row->source = (string) ($ev['src'] ?? $row->source ?? 'live');
                $row->last_seen_at = now();

                if ($previous !== null) {
                    $row->previous = $previous;
                }

                if ($forecast !== null) {
                    $row->forecast = $forecast;
                }

                if ($actual !== null) {
                    $row->actual = $actual;

                    if ($row->released_at === null) {
                        $row->released_at = now();
                    }
                }

                $row->save();
                $saved++;
            } catch (Throwable) {
                // Archive must never break the live page.
            }
        }

        return $saved;
    }

    /**
     * Fill blank Previous/Forecast/Actual from saved history.
     *
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    public function hydrate(array $events): array
    {
        if ($events === []) {
            return [];
        }

        $fps = [];

        foreach ($events as $ev) {
            if (! is_array($ev)) {
                continue;
            }

            $fp = $this->fingerprint($ev);

            if ($fp !== null) {
                $fps[$fp] = true;
            }
        }

        if ($fps === []) {
            return $events;
        }

        $archive = FxLearnEconomicEvent::query()
            ->whereIn('fingerprint', array_keys($fps))
            ->get()
            ->keyBy('fingerprint');

        foreach ($events as $i => $ev) {
            if (! is_array($ev)) {
                continue;
            }

            $fp = $this->fingerprint($ev);

            if ($fp === null || ! isset($archive[$fp])) {
                continue;
            }

            /** @var FxLearnEconomicEvent $row */
            $row = $archive[$fp];

            foreach (['previous', 'forecast', 'actual'] as $field) {
                if ($this->isBlank($ev[$field] ?? null) && filled($row->{$field})) {
                    $events[$i][$field] = (string) $row->{$field};
                }
            }

            if (($events[$i]['unit'] ?? '') === '' && filled($row->unit)) {
                $events[$i]['unit'] = (string) $row->unit;
            }
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    public function fingerprint(array $ev): ?string
    {
        $title = trim((string) ($ev['title'] ?? ''));
        $atMs = (int) ($ev['at'] ?? 0);

        if ($title === '' || $atMs <= 0) {
            return null;
        }

        $day = CarbonImmutable::createFromTimestampMs($atMs, 'UTC')->toDateString();
        $ccy = strtoupper((string) ($ev['ccy'] ?? ''));
        $norm = $this->normalizeTitle($title);

        if ($norm === '') {
            return null;
        }

        return hash('sha256', $day.'|'.$ccy.'|'.$norm);
    }

    public function normalizeTitle(string $title): string
    {
        return Str::of($title)
            ->lower()
            ->replaceMatches('/\([^)]*practice[^)]*\)/i', ' ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->replaceMatches('/\b(the|and|of|for|a|an|m|m m|y|y y|q|q q|usa|us|uk|german|eurozone|euro zone)\b/', ' ')
            ->squish()
            ->substr(0, 72)
            ->toString();
    }

    protected function clean(string $value): ?string
    {
        $s = trim($value);

        if ($s === '' || $s === '—' || $s === '-' || strcasecmp($s, 'n/a') === 0) {
            return null;
        }

        return $s;
    }

    protected function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $s = trim((string) $value);

        return $s === '' || $s === '—';
    }
}
