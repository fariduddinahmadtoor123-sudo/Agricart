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

    public string $quickFilter = 'all';

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

    #[On('fx-learn-radar-filter')]
    public function onToolbarFilter(mixed $filter = null): void
    {
        $value = is_array($filter) ? (string) ($filter['filter'] ?? '') : (string) $filter;

        if (! in_array($value, ['all', 'high', 'usd', 'actual', 'practice'], true)) {
            return;
        }

        $this->quickFilter = $value;
    }

    #[On('fx-learn-radar-settings')]
    public function onToolbarSettings(): void
    {
        $this->openSettings();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEvents(): Collection
    {
        $q = mb_strtolower(trim($this->search));

        return collect($this->events)
            ->filter(function (array $ev) use ($q): bool {
                if ($this->quickFilter === 'high' && ($ev['impact'] ?? '') !== 'high') {
                    return false;
                }

                if ($this->quickFilter === 'usd' && strtoupper((string) ($ev['ccy'] ?? '')) !== 'USD') {
                    return false;
                }

                if ($this->quickFilter === 'actual') {
                    $actual = trim((string) ($ev['actual'] ?? ''));

                    if ($actual === '' || $actual === '—') {
                        return false;
                    }
                }

                if ($this->quickFilter === 'practice' && ($ev['src'] ?? '') !== 'practice') {
                    return false;
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
     * @return array<string, mixed>|null
     */
    public function radarEvent(EconomicCalendarService $calendar): ?array
    {
        return $calendar->nextHighImpact($this->filteredEvents()->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessionClocks(): array
    {
        return MarketSessionClock::deckSnapshot();
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
}
