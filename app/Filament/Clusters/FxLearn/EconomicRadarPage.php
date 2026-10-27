<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\FxLearn\EconomicCalendarService;
use App\Services\FxLearn\EconomicNewsBiasAiService;
use App\Support\FriendlyError;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Throwable;

class EconomicRadarPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Economic Radar';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'economic-radar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected string $view = 'filament.pages.fx-learn.economic-radar';

    public string $tf = 'week';

    public string $impact = 'all';

    public string $ccy = 'ALL';

    /** @var list<array<string, mixed>> */
    public array $events = [];

    public string $feedSource = 'backup';

    public string $fetchedAt = '';

    public bool $showAllView = false;

    public ?string $selectedEventId = null;

    public string $manualActual = '';

    public bool $analyzing = false;

    public ?string $aiBias = null;

    public ?string $aiVerdict = null;

    public ?string $aiSummaryEn = null;

    public ?string $aiSummaryUr = null;

    public ?string $aiDollarBias = null;

    public ?string $aiInvertedNote = null;

    public ?string $aiMessage = null;

    public string $aiMessageTone = 'muted';

    private const PREVIEW_LIMIT = 3;

    public function mount(EconomicCalendarService $calendar): void
    {
        $this->tf = 'week';
        $this->loadSnapshot($calendar);
    }

    public function openAllView(): void
    {
        $this->showAllView = true;
    }

    public function closeAllView(): void
    {
        $this->showAllView = false;
    }

    public function selectEvent(string $eventId): void
    {
        $event = $this->findEvent($eventId);

        if ($event === null) {
            return;
        }

        $this->selectedEventId = $eventId;
        $this->manualActual = '';
        $this->clearAiResult();
    }

    public function updatedManualActual(string $value): void
    {
        $this->manualActual = trim($value);
        $this->clearAiResult();
    }

    public function analyzeNews(EconomicNewsBiasAiService $ai): void
    {
        $event = $this->selectedEvent();

        if ($event === null) {
            $this->aiMessage = __('agricart.pages.fx-learn.econ_ai_pick_event');
            $this->aiMessageTone = 'warning';

            return;
        }

        $effective = $this->effectiveActual($event);

        if ($effective === null) {
            $this->aiMessage = __('agricart.pages.fx-learn.econ_ai_actual_required');
            $this->aiMessageTone = 'warning';

            return;
        }

        $lock = Cache::lock('fxlearn.econ.ai.'.(auth()->id() ?? 'guest'), 120);

        if (! $lock->get()) {
            $this->aiMessage = __('agricart.pages.fx-learn.econ_ai_busy');
            $this->aiMessageTone = 'warning';

            return;
        }

        $this->analyzing = true;
        $this->aiMessage = null;
        $this->aiMessageTone = 'muted';

        try {
            $result = $ai->analyze([
                'title' => (string) ($event['title'] ?? ''),
                'ccy' => (string) ($event['ccy'] ?? ''),
                'impact' => (string) ($event['impact'] ?? 'med'),
                'previous' => (string) ($event['previous'] ?? '—'),
                'forecast' => (string) ($event['forecast'] ?? '—'),
                'actual' => $effective,
                'actual_source' => $this->isManualActual($event) ? 'manual' : 'feed',
            ]);

            if (! $result['success']) {
                $this->clearAiResult();
                $this->aiMessage = $result['message'];
                $this->aiMessageTone = 'danger';

                return;
            }

            $data = $result['data'] ?? [];
            $this->aiBias = (string) ($data['bias'] ?? '');
            $this->aiVerdict = (string) ($data['verdict'] ?? '');
            $this->aiSummaryEn = (string) ($data['summary_en'] ?? '');
            $this->aiSummaryUr = (string) ($data['summary_ur'] ?? '');
            $this->aiDollarBias = isset($data['dollar_bias']) && is_string($data['dollar_bias'])
                ? $data['dollar_bias']
                : null;
            $this->aiInvertedNote = isset($data['inverted_note']) && is_string($data['inverted_note'])
                ? $data['inverted_note']
                : null;
            $this->aiMessage = $result['message'];
            $this->aiMessageTone = 'success';
        } catch (Throwable $e) {
            $this->clearAiResult();
            $this->aiMessage = FriendlyError::message($e);
            $this->aiMessageTone = 'danger';
        } finally {
            $this->analyzing = false;
            $lock->release();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedEvent(): ?array
    {
        if ($this->selectedEventId === null || $this->selectedEventId === '') {
            return null;
        }

        return $this->findEvent($this->selectedEventId);
    }

    public function feedActual(?array $event = null): string
    {
        $event ??= $this->selectedEvent();

        if ($event === null) {
            return '—';
        }

        $actual = trim((string) ($event['actual'] ?? ''));

        return $actual !== '' ? $actual : '—';
    }

    public function effectiveActual(?array $event = null): ?string
    {
        $event ??= $this->selectedEvent();

        if ($event === null) {
            return null;
        }

        $feed = $this->feedActual($event);

        if ($feed !== '—') {
            return $feed;
        }

        $manual = trim($this->manualActual);

        if ($manual === '' || $manual === '—') {
            return null;
        }

        return $manual;
    }

    public function isManualActual(?array $event = null): bool
    {
        $event ??= $this->selectedEvent();

        if ($event === null) {
            return false;
        }

        if ($this->feedActual($event) !== '—') {
            return false;
        }

        $manual = trim($this->manualActual);

        return $manual !== '' && $manual !== '—';
    }

    public function needsManualActual(?array $event = null): bool
    {
        $event ??= $this->selectedEvent();

        if ($event === null) {
            return false;
        }

        return $this->feedActual($event) === '—';
    }

    public function refreshFeed(EconomicCalendarService $calendar): void
    {
        $this->applySnapshot($calendar->refresh());
    }

    private function clearAiResult(): void
    {
        $this->aiBias = null;
        $this->aiVerdict = null;
        $this->aiSummaryEn = null;
        $this->aiSummaryUr = null;
        $this->aiDollarBias = null;
        $this->aiInvertedNote = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEvent(string $eventId): ?array
    {
        foreach ($this->events as $event) {
            if ((string) ($event['id'] ?? '') === $eventId) {
                return $event;
            }
        }

        return null;
    }

    #[On('fx-learn-econ-tf')]
    public function onToolbarTf(mixed $tf = null): void
    {
        $value = is_array($tf) ? (string) ($tf['tf'] ?? '') : (string) $tf;
        $this->setTf($value !== '' ? $value : 'today');
    }

    #[On('fx-learn-econ-impact')]
    public function onToolbarImpact(mixed $impact = null): void
    {
        $value = is_array($impact) ? (string) ($impact['impact'] ?? '') : (string) $impact;
        $this->setImpact($value !== '' ? $value : 'all');
    }

    #[On('fx-learn-econ-ccy')]
    public function onToolbarCcy(mixed $ccy = null): void
    {
        $value = is_array($ccy) ? (string) ($ccy['ccy'] ?? '') : (string) $ccy;
        $this->setCcy($value !== '' ? $value : 'ALL');
    }

    /**
     * Soft pull — uses smart Actual window refresh when needed (no forced bust).
     */
    public function softRefresh(EconomicCalendarService $calendar): void
    {
        $this->applySnapshot($calendar->snapshot());
    }

    public function updatedTf(string $value): void
    {
        if (! in_array($value, ['today', 'tomorrow', 'week'], true)) {
            $this->tf = 'week';
        }
    }

    public function updatedImpact(string $value): void
    {
        if (! in_array($value, ['all', 'low', 'med', 'high'], true)) {
            $this->impact = 'all';
        }
    }

    public function setTf(string $tf): void
    {
        if (! in_array($tf, ['today', 'tomorrow', 'week'], true)) {
            return;
        }

        $this->tf = $tf;
    }

    public function setImpact(string $impact): void
    {
        if (! in_array($impact, ['all', 'low', 'med', 'high'], true)) {
            return;
        }

        $this->impact = $impact;
    }

    public function impactStars(string $impact): string
    {
        return match ($impact) {
            'high' => '★★★',
            'med' => '★★',
            'low' => '★',
            default => '',
        };
    }

    public function updatedCcy(string $value): void
    {
        $ccy = strtoupper($value);

        if (! in_array($ccy, ['ALL', 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD', 'CNY'], true)) {
            $this->ccy = 'ALL';

            return;
        }

        $this->ccy = $ccy;
    }

    public function setCcy(string $ccy): void
    {
        $ccy = strtoupper($ccy);

        if (! in_array($ccy, ['ALL', 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD', 'CNY'], true)) {
            return;
        }

        $this->ccy = $ccy;
    }

    public function rangeLabel(): string
    {
        return match ($this->tf) {
            'tomorrow' => __('agricart.pages.fx-learn.econ_tf_tomorrow'),
            'today' => __('agricart.pages.fx-learn.econ_tf_today'),
            default => __('agricart.pages.fx-learn.econ_tf_week'),
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEvents(): Collection
    {
        $range = $this->timeRange();

        return collect($this->events)
            ->filter(function (array $ev) use ($range): bool {
                $at = (int) ($ev['at'] ?? 0);

                if ($at < $range['from'] || $at > $range['to']) {
                    return false;
                }

                if ($this->impact !== 'all' && ($ev['impact'] ?? '') !== $this->impact) {
                    return false;
                }

                if ($this->ccy !== 'ALL' && strtoupper((string) ($ev['ccy'] ?? '')) !== $this->ccy) {
                    return false;
                }

                return true;
            })
            ->sortBy('at')
            ->values();
    }

    /**
     * Compact preview: live/upcoming first, then most recent past (so Done rows stay visible).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function previewEvents(): Collection
    {
        $all = $this->filteredEvents();

        if ($all->isEmpty()) {
            return $all;
        }

        $nowMs = (int) CarbonImmutable::now('UTC')->getTimestampMs();

        $upcoming = $all
            ->filter(fn (array $ev): bool => (int) ($ev['at'] ?? 0) >= $nowMs - 120_000)
            ->sortBy('at')
            ->values();

        $recentPast = $all
            ->filter(fn (array $ev): bool => (int) ($ev['at'] ?? 0) < $nowMs - 120_000)
            ->sortByDesc('at')
            ->values();

        $pick = $upcoming->take(self::PREVIEW_LIMIT);

        if ($pick->count() < self::PREVIEW_LIMIT) {
            $pick = $pick->concat($recentPast->take(self::PREVIEW_LIMIT - $pick->count()));
        }

        return $pick->sortBy('at')->values();
    }

    public function formatPkt(int $atMs): string
    {
        return agricart_datetime(
            CarbonImmutable::createFromTimestampMs($atMs, 'UTC'),
            'D d/m H:i',
            true,
        );
    }

    public function countdownLabel(int $atMs): string
    {
        $now = CarbonImmutable::now('UTC');
        $at = CarbonImmutable::createFromTimestampMs($atMs, 'UTC');
        $diffSec = $at->getTimestamp() - $now->getTimestamp();

        if (abs($diffSec) < 90) {
            return __('agricart.pages.fx-learn.econ_now');
        }

        $past = $diffSec < 0;
        $abs = abs($diffSec);
        $h = intdiv($abs, 3600);
        $m = intdiv($abs % 3600, 60);

        if ($h > 48) {
            $days = intdiv($h, 24);

            return $past
                ? __('agricart.pages.fx-learn.econ_ago_days', ['days' => $days])
                : __('agricart.pages.fx-learn.econ_in_days', ['days' => $days]);
        }

        $parts = [];

        if ($h > 0) {
            $parts[] = $h.'h';
        }

        $parts[] = $m.'m';
        $label = implode(' ', $parts);

        return $past
            ? __('agricart.pages.fx-learn.econ_ago', ['time' => $label])
            : __('agricart.pages.fx-learn.econ_in', ['time' => $label]);
    }

    /**
     * upcoming | live | done
     */
    public function eventStatus(array $ev): string
    {
        $at = (int) ($ev['at'] ?? 0);
        $nowMs = (int) CarbonImmutable::now('UTC')->getTimestampMs();
        $actual = trim((string) ($ev['actual'] ?? ''));
        $hasActual = $actual !== '' && $actual !== '—';

        if ($hasActual) {
            return 'done';
        }

        $delta = $at - $nowMs;

        if ($delta > 90_000) {
            return 'upcoming';
        }

        if ($delta >= -1_800_000) {
            return 'live';
        }

        return 'done';
    }

    public function shouldPoll(): bool
    {
        $nowMs = (int) CarbonImmutable::now('UTC')->getTimestampMs();

        foreach ($this->filteredEvents() as $ev) {
            if (($ev['impact'] ?? '') !== 'high') {
                continue;
            }

            $actual = trim((string) ($ev['actual'] ?? ''));

            if ($actual !== '' && $actual !== '—') {
                continue;
            }

            $at = (int) ($ev['at'] ?? 0);

            if ($at >= $nowMs - 2_400_000 && $at <= $nowMs + 120_000) {
                return true;
            }
        }

        return false;
    }

    public function feedBadgeLabel(): string
    {
        return match ($this->feedSource) {
            'ff' => __('agricart.pages.fx-learn.econ_feed_live'),
            default => __('agricart.pages.fx-learn.econ_feed_backup'),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return FxLearnCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }

    private function loadSnapshot(EconomicCalendarService $calendar): void
    {
        $this->applySnapshot($calendar->snapshot());
    }

    /**
     * @param  array{events: list<array<string, mixed>>, source: string, fetched_at: string}  $snapshot
     */
    private function applySnapshot(array $snapshot): void
    {
        $this->events = $snapshot['events'];
        $this->feedSource = $snapshot['source'];
        $this->fetchedAt = $snapshot['fetched_at'];
    }

    /**
     * @return array{from: int, to: int}
     */
    private function timeRange(): array
    {
        $tz = agricart_display_timezone();
        $now = CarbonImmutable::now($tz);
        $today0 = $now->startOfDay();
        $week0 = $now->startOfWeek(CarbonImmutable::MONDAY);

        return match ($this->tf) {
            'tomorrow' => [
                'from' => (int) $today0->addDay()->getTimestampMs(),
                'to' => (int) $today0->addDay()->endOfDay()->getTimestampMs(),
            ],
            'week' => [
                'from' => (int) $week0->getTimestampMs(),
                'to' => (int) $week0->addWeek()->subSecond()->getTimestampMs(),
            ],
            default => [
                'from' => (int) $today0->getTimestampMs(),
                'to' => (int) $today0->endOfDay()->getTimestampMs(),
            ],
        };
    }
}
