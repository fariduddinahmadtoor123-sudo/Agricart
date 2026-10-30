<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\FxLearn\EconomicCalendarService;
use App\Services\FxLearn\EconomicNewsBiasAiService;
use App\Services\FxLearn\FinnhubCredentialService;
use App\Services\FxLearn\MarketSessionClock;
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

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'economic-radar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected string $view = 'filament.pages.fx-learn.economic-radar';

    public string $ccyFilter = 'all';

    public string $impactFilter = 'all';

    /** Y-m-d in display timezone, or "all". */
    public string $dayFilter = 'today';

    public bool $numbersOnly = false;

    public string $search = '';

    /** @var list<array<string, mixed>> */
    public array $events = [];

    public string $feedSource = 'empty';

    public string $fetchedAt = '';

    public ?string $feedError = null;

    /** @var array<string, mixed> */
    public array $feedMeta = [];

    public bool $usingDemo = false;

    public bool $settingsOpen = false;

    public string $settingsApiKey = '';

    public string $settingsLabel = 'Finnhub';

    public ?string $settingsMessage = null;

    public string $settingsMessageTone = 'muted';

    /** @var array<string, mixed> */
    public array $credentialStatus = [];

    public ?string $analyzeEventId = null;

    public bool $analyzing = false;

    public ?string $aiBias = null;

    public ?string $aiVerdict = null;

    public ?string $aiSummaryEn = null;

    public ?string $aiSummaryUr = null;

    public ?string $aiMessage = null;

    public string $aiMessageTone = 'muted';

    public function mount(EconomicCalendarService $calendar, FinnhubCredentialService $credentials): void
    {
        $this->credentialStatus = $credentials->statusPayload();
        $this->loadSnapshot($calendar);
    }

    public function refreshFeed(EconomicCalendarService $calendar): void
    {
        $this->applySnapshot($calendar->refresh());
    }

    #[On('fx-learn-radar-search')]
    public function onToolbarSearch(mixed $query = null): void
    {
        $value = is_array($query) ? (string) ($query['query'] ?? '') : (string) $query;
        $this->search = trim($value);
    }

    #[On('fx-learn-radar-ccy')]
    public function onToolbarCcy(mixed $ccy = null): void
    {
        $value = is_array($ccy) ? (string) ($ccy['ccy'] ?? '') : (string) $ccy;

        if (! in_array($value, ['all', 'usd'], true)) {
            return;
        }

        $this->ccyFilter = $value;
    }

    #[On('fx-learn-radar-impact')]
    public function onToolbarImpact(mixed $impact = null): void
    {
        $value = is_array($impact) ? (string) ($impact['impact'] ?? '') : (string) $impact;

        if (! in_array($value, ['all', 'high', 'med', 'low'], true)) {
            return;
        }

        $this->impactFilter = $value;
    }

    #[On('fx-learn-radar-settings')]
    public function onToolbarSettings(): void
    {
        $this->openSettings();
    }

    public function setDayFilter(string $day): void
    {
        $day = trim($day);

        if ($day === 'all' || $day === 'today') {
            $this->dayFilter = $day;

            return;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) === 1) {
            $this->dayFilter = $day;
        }
    }

    public function toggleNumbersOnly(): void
    {
        $this->numbersOnly = ! $this->numbersOnly;
    }

    /**
     * Day chips for the economic sheet (display timezone).
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public function dayTabs(): array
    {
        $tz = agricart_display_timezone();
        $today = now($tz)->toDateString();
        $base = collect($this->events)
            ->reject(fn (array $ev): bool => str_starts_with((string) ($ev['id'] ?? ''), 'practice-'));

        $byDay = $base
            ->groupBy(function (array $ev) use ($tz): string {
                return CarbonImmutable::createFromTimestampMs((int) ($ev['at'] ?? 0), 'UTC')
                    ->timezone($tz)
                    ->toDateString();
            })
            ->sortKeys();

        $tabs = [
            [
                'key' => 'today',
                'label' => __('agricart.pages.fx-learn.day_today'),
                'count' => (int) ($byDay->get($today)?->count() ?? 0),
            ],
            [
                'key' => 'all',
                'label' => __('agricart.pages.fx-learn.day_all'),
                'count' => $base->count(),
            ],
        ];

        foreach ($byDay as $ymd => $rows) {
            $local = CarbonImmutable::parse((string) $ymd, $tz);
            $tabs[] = [
                'key' => (string) $ymd,
                'label' => $local->format('D d'),
                'count' => $rows->count(),
            ];
        }

        return $tabs;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEvents(): Collection
    {
        $q = mb_strtolower(trim($this->search));
        $tz = agricart_display_timezone();
        $dayKey = $this->dayFilter === 'today' ? now($tz)->toDateString() : $this->dayFilter;

        return collect($this->events)
            ->filter(function (array $ev) use ($q, $tz, $dayKey): bool {
                if ($this->ccyFilter === 'usd' && strtoupper((string) ($ev['ccy'] ?? '')) !== 'USD') {
                    return false;
                }

                if ($this->impactFilter !== 'all' && ($ev['impact'] ?? '') !== $this->impactFilter) {
                    return false;
                }

                if ($dayKey !== 'all') {
                    $evDay = CarbonImmutable::createFromTimestampMs((int) ($ev['at'] ?? 0), 'UTC')
                        ->timezone($tz)
                        ->toDateString();

                    if ($evDay !== $dayKey) {
                        return false;
                    }
                }

                if ($this->numbersOnly) {
                    $hasNum = ! $this->isBlankFigure($ev['previous'] ?? null)
                        || ! $this->isBlankFigure($ev['forecast'] ?? null)
                        || ! $this->isBlankFigure($ev['actual'] ?? null);

                    if (! $hasNum) {
                        return false;
                    }
                }

                if ($q === '') {
                    return true;
                }

                $hay = mb_strtolower(
                    (string) ($ev['title'] ?? '').' '.
                    (string) ($ev['ccy'] ?? '').' '.
                    (string) ($ev['country'] ?? '')
                );

                return str_contains($hay, $q);
            })
            ->sortBy('at')
            ->values();
    }

    /**
     * Next print matching active toolbar filters.
     *
     * @return array<string, mixed>|null
     */
    public function filteredRadarEvent(EconomicCalendarService $calendar): ?array
    {
        return $calendar->nextUpcoming($this->filteredEvents()->all(), highOnly: false);
    }

    /**
     * Next print on the full feed (routine / unfiltered).
     *
     * @return array<string, mixed>|null
     */
    public function routineRadarEvent(EconomicCalendarService $calendar): ?array
    {
        return $calendar->nextUpcoming($this->events, highOnly: false);
    }

    /**
     * @deprecated Use filteredRadarEvent()
     *
     * @return array<string, mixed>|null
     */
    public function radarEvent(EconomicCalendarService $calendar): ?array
    {
        return $this->filteredRadarEvent($calendar);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessionClocks(): array
    {
        return MarketSessionClock::deckSnapshot();
    }

    /**
     * @return array{flag: string, label: string, tz: string, hm: string, sec: string, date: string}
     */
    public function pakistanClock(): array
    {
        $tz = 'Asia/Karachi';
        $local = CarbonImmutable::now($tz);

        return [
            'flag' => '🇵🇰',
            'label' => __('agricart.pages.fx-learn.clock_pakistan'),
            'tz' => $tz,
            'hm' => $local->format('H:i'),
            'sec' => $local->format('s'),
            'date' => $local->format('D d M Y'),
        ];
    }

    public function flagForCcy(string $ccy): string
    {
        return MarketSessionClock::flagForCcy($ccy);
    }

    public function formatPkt(int $atMs): string
    {
        return agricart_datetime(
            CarbonImmutable::createFromTimestampMs($atMs, 'UTC'),
            'D d/m H:i',
            true,
        );
    }

    public function actualTone(array $ev): string
    {
        $actual = trim((string) ($ev['actual'] ?? ''));
        $forecast = trim((string) ($ev['forecast'] ?? ''));

        if ($actual === '' || $actual === '—') {
            return 'pending';
        }

        if ($forecast === '' || $forecast === '—') {
            return 'filled';
        }

        $a = $this->toFloat($actual);
        $f = $this->toFloat($forecast);

        if ($a === null || $f === null) {
            return 'filled';
        }

        if ($a > $f) {
            return 'up';
        }

        if ($a < $f) {
            return 'down';
        }

        return 'flat';
    }

    /**
     * Compact surprise vs forecast for the Actual cell (e.g. +0.1).
     */
    public function surpriseLabel(array $ev): ?string
    {
        $actual = trim((string) ($ev['actual'] ?? ''));
        $forecast = trim((string) ($ev['forecast'] ?? ''));

        if ($actual === '' || $actual === '—' || $forecast === '' || $forecast === '—') {
            return null;
        }

        $a = $this->toFloat($actual);
        $f = $this->toFloat($forecast);

        if ($a === null || $f === null) {
            return null;
        }

        $diff = round($a - $f, 3);

        if (abs($diff) < 0.0000001) {
            return '0';
        }

        $text = rtrim(rtrim(number_format($diff, 3, '.', ''), '0'), '.');

        return ($diff > 0 ? '+' : '').$text;
    }

    public function openSettings(): void
    {
        $credentials = app(FinnhubCredentialService::class);
        $this->credentialStatus = $credentials->statusPayload();
        $this->settingsApiKey = '';
        $this->settingsLabel = 'Finnhub';
        $this->settingsMessage = null;
        $this->settingsMessageTone = 'muted';
        $this->settingsOpen = true;
    }

    public function closeSettings(): void
    {
        $this->settingsOpen = false;
        $this->settingsApiKey = '';
        $this->settingsMessage = null;
    }

    public function saveAndTestConnection(
        FinnhubCredentialService $credentials,
        EconomicCalendarService $calendar,
    ): void {
        $result = $credentials->saveAndTest($this->settingsApiKey, $this->settingsLabel);
        $this->credentialStatus = $credentials->statusPayload();
        $this->settingsMessage = $result['message'];
        $this->settingsMessageTone = $result['ok'] ? 'success' : 'danger';

        if ($result['ok']) {
            $this->settingsApiKey = '';
            $this->applySnapshot($calendar->refresh());
        }
    }

    public function testExistingConnection(
        FinnhubCredentialService $credentials,
        EconomicCalendarService $calendar,
    ): void {
        $result = $credentials->testActive();
        $this->credentialStatus = $credentials->statusPayload();
        $this->settingsMessage = $result['message'];
        $this->settingsMessageTone = $result['ok'] ? 'success' : 'danger';

        if ($result['ok']) {
            $this->applySnapshot($calendar->refresh());
        }
    }

    public function deleteApiKey(
        FinnhubCredentialService $credentials,
        EconomicCalendarService $calendar,
    ): void {
        $result = $credentials->deleteActive();
        $this->credentialStatus = $credentials->statusPayload();
        $this->settingsMessage = $result['message'];
        $this->settingsMessageTone = $result['ok'] ? 'success' : 'danger';
        $this->applySnapshot($calendar->refresh());
    }

    public function openAnalyze(string $eventId): void
    {
        $this->analyzeEventId = $eventId;
        $this->clearAiResult();
        $this->aiMessage = null;
        $this->aiMessageTone = 'muted';
    }

    public function closeAnalyze(): void
    {
        $this->analyzeEventId = null;
        $this->clearAiResult();
        $this->aiMessage = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function analyzingEvent(): ?array
    {
        if ($this->analyzeEventId === null || $this->analyzeEventId === '') {
            return null;
        }

        foreach ($this->events as $ev) {
            if ((string) ($ev['id'] ?? '') === $this->analyzeEventId) {
                return $ev;
            }
        }

        return null;
    }

    public function runAnalyze(EconomicNewsBiasAiService $ai): void
    {
        $event = $this->analyzingEvent();

        if ($event === null) {
            $this->aiMessage = __('agricart.pages.fx-learn.ai_pick_event');
            $this->aiMessageTone = 'warning';

            return;
        }

        $lock = Cache::lock('fxlearn.radar.ai.'.(auth()->id() ?? 'guest'), 120);

        if (! $lock->get()) {
            $this->aiMessage = __('agricart.pages.fx-learn.ai_busy');
            $this->aiMessageTone = 'warning';

            return;
        }

        $this->analyzing = true;

        try {
            $result = $ai->analyze([
                'title' => (string) ($event['title'] ?? ''),
                'ccy' => (string) ($event['ccy'] ?? ''),
                'impact' => (string) ($event['impact'] ?? 'med'),
                'previous' => (string) ($event['previous'] ?? '—'),
                'forecast' => (string) ($event['forecast'] ?? '—'),
                'actual' => (string) ($event['actual'] ?? '—'),
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
     * @param  array{
     *     events: list<array<string, mixed>>,
     *     source: string,
     *     fetched_at: string,
     *     error?: string|null,
     *     meta?: array<string, mixed>,
     *     using_demo?: bool
     * }  $snapshot
     */
    private function applySnapshot(array $snapshot): void
    {
        $this->events = $snapshot['events'] ?? [];
        $this->feedSource = (string) ($snapshot['source'] ?? 'empty');
        $this->fetchedAt = (string) ($snapshot['fetched_at'] ?? '');
        $this->feedError = isset($snapshot['error']) && is_string($snapshot['error']) && $snapshot['error'] !== ''
            ? $snapshot['error']
            : null;
        $this->feedMeta = is_array($snapshot['meta'] ?? null) ? $snapshot['meta'] : [];
        $this->usingDemo = (bool) ($snapshot['using_demo'] ?? false);
    }


    private function clearAiResult(): void
    {
        $this->aiBias = null;
        $this->aiVerdict = null;
        $this->aiSummaryEn = null;
        $this->aiSummaryUr = null;
    }

    private function toFloat(string $value): ?float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', $value) ?? '';

        if ($clean === '' || $clean === '-' || $clean === '.') {
            return null;
        }

        return (float) $clean;
    }

    private function isBlankFigure(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $s = trim((string) $value);

        return $s === '' || $s === '—';
    }
}
