<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\FxLearn\EconomicCalendarService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class EconomicRadarPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Economic Radar';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'economic-radar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected string $view = 'filament.pages.fx-learn.economic-radar';

    /** Agricart merged calendar, or a single upstream feed. */
    public string $feedView = 'ag';

    public string $tf = 'today';

    public string $impact = 'all';

    public string $ccy = 'ALL';

    public ?string $selectedId = null;

    /** @var list<array<string, mixed>> */
    public array $events = [];

    /** @var array{fc: list<array<string, mixed>>, smt: list<array<string, mixed>>} */
    public array $feeds = [
        'fc' => [],
        'smt' => [],
    ];

    /** @var array{ag: int, fc: int, smt: int} */
    public array $feedCounts = [
        'ag' => 0,
        'fc' => 0,
        'smt' => 0,
    ];

    public string $feedSource = 'backup';

    public string $fetchedAt = '';

    public function mount(EconomicCalendarService $calendar): void
    {
        $dow = (int) CarbonImmutable::now(agricart_display_timezone())->dayOfWeek;
        $this->tf = ($dow === 0 || $dow === 6) ? 'nextweek' : 'today';

        $this->loadSnapshot($calendar);
        $this->ensureSelection();
    }

    public function refreshFeed(EconomicCalendarService $calendar): void
    {
        $snapshot = $calendar->refresh();
        $this->applySnapshot($snapshot);
        $this->ensureSelection(force: true);
    }

    public function printFiltered(): void
    {
        $this->js('setTimeout(() => window.print(), 50)');
    }

    public function filterSummary(): string
    {
        $tf = match ($this->tf) {
            'lastweek' => __('agricart.pages.fx-learn.econ_tf_last'),
            'tomorrow' => __('agricart.pages.fx-learn.econ_tf_tomorrow'),
            'nextweek' => __('agricart.pages.fx-learn.econ_tf_next'),
            default => __('agricart.pages.fx-learn.econ_tf_today'),
        };

        $impact = match ($this->impact) {
            'high' => __('agricart.pages.fx-learn.econ_impact_high'),
            'med' => __('agricart.pages.fx-learn.econ_impact_med'),
            default => __('agricart.pages.fx-learn.econ_impact_all'),
        };

        $ccy = $this->ccy === 'ALL'
            ? __('agricart.pages.fx-learn.econ_ccy_all')
            : $this->ccy;

        $src = match ($this->feedView) {
            'fc' => __('agricart.pages.fx-learn.econ_src_fc'),
            'smt' => __('agricart.pages.fx-learn.econ_src_smt'),
            default => __('agricart.pages.fx-learn.econ_src_ag'),
        };

        return __('agricart.pages.fx-learn.econ_print_filters', [
            'tf' => $tf,
            'impact' => $impact,
            'ccy' => $ccy,
            'src' => $src,
        ]);
    }

    public function setFeedView(string $view): void
    {
        if (! in_array($view, ['ag', 'fc', 'smt'], true)) {
            return;
        }

        $this->feedView = $view;
        $this->ensureSelection(force: true);
    }

    public function setTf(string $tf): void
    {
        if (! in_array($tf, ['lastweek', 'today', 'tomorrow', 'nextweek'], true)) {
            return;
        }

        $this->tf = $tf;
        $this->ensureSelection(force: true);
    }

    public function setImpact(string $impact): void
    {
        if (! in_array($impact, ['all', 'high', 'med'], true)) {
            return;
        }

        $this->impact = $impact;
        $this->ensureSelection(force: true);
    }

    public function setCcy(string $ccy): void
    {
        $ccy = strtoupper($ccy);

        if (! in_array($ccy, ['ALL', 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'NZD', 'CHF', 'CNY'], true)) {
            return;
        }

        $this->ccy = $ccy;
        $this->ensureSelection(force: true);
    }

    public function selectEvent(string $id): void
    {
        if ($this->filteredEvents()->contains(fn (array $ev): bool => $ev['id'] === $id)) {
            $this->selectedId = $id;
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function activeEvents(): Collection
    {
        return collect(match ($this->feedView) {
            'fc' => $this->feeds['fc'] ?? [],
            'smt' => $this->feeds['smt'] ?? [],
            default => $this->events,
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEvents(): Collection
    {
        $range = $this->timeRange();

        return $this->activeEvents()
            ->filter(function (array $ev) use ($range): bool {
                $at = (int) ($ev['at'] ?? 0);

                if ($at < $range['from'] || $at > $range['to']) {
                    return false;
                }

                if ($this->impact === 'high' && ($ev['impact'] ?? '') !== 'high') {
                    return false;
                }

                if ($this->impact === 'med' && ! in_array($ev['impact'] ?? '', ['high', 'med'], true)) {
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
     * @return array<string, mixed>|null
     */
    public function selectedEvent(): ?array
    {
        $visible = $this->filteredEvents();

        if ($this->selectedId) {
            $hit = $visible->firstWhere('id', $this->selectedId);

            if (is_array($hit)) {
                return $hit;
            }
        }

        return $visible->first();
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

    public function actualTone(array $ev): string
    {
        $a = $this->parseNumber($ev['actual'] ?? null);
        $f = $this->parseNumber($ev['forecast'] ?? null);

        if ($a === null || $f === null) {
            return '';
        }

        if ($a > $f) {
            return 'beat';
        }

        if ($a < $f) {
            return 'miss';
        }

        return 'flat';
    }

    public function feedBadgeLabel(): string
    {
        return match ($this->feedSource) {
            'fc+smt' => __('agricart.pages.fx-learn.econ_feed_live'),
            'fc', 'smt' => __('agricart.pages.fx-learn.econ_feed_partial'),
            default => __('agricart.pages.fx-learn.econ_feed_backup'),
        };
    }

    /**
     * @return list<string>
     */
    public function eventSourceTags(array $ev): array
    {
        $sources = $ev['sources'] ?? null;

        if (is_array($sources) && $sources !== []) {
            return array_values(array_filter(
                array_map('strval', $sources),
                static fn (string $s): bool => in_array($s, ['fc', 'smt'], true),
            ));
        }

        $src = (string) ($ev['src'] ?? '');

        return in_array($src, ['fc', 'smt'], true) ? [$src] : [];
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
     *     feeds?: array{fc: list<array<string, mixed>>, smt: list<array<string, mixed>>},
     *     counts?: array{ag: int, fc: int, smt: int},
     *     source: string,
     *     fetched_at: string
     * }  $snapshot
     */
    private function applySnapshot(array $snapshot): void
    {
        $this->events = $snapshot['events'];
        $this->feeds = [
            'fc' => $snapshot['feeds']['fc'] ?? [],
            'smt' => $snapshot['feeds']['smt'] ?? [],
        ];
        $this->feedCounts = [
            'ag' => (int) ($snapshot['counts']['ag'] ?? count($this->events)),
            'fc' => (int) ($snapshot['counts']['fc'] ?? count($this->feeds['fc'])),
            'smt' => (int) ($snapshot['counts']['smt'] ?? count($this->feeds['smt'])),
        ];
        $this->feedSource = $snapshot['source'];
        $this->fetchedAt = $snapshot['fetched_at'];
    }

    private function ensureSelection(bool $force = false): void
    {
        $visible = $this->filteredEvents();

        if ($visible->isEmpty()) {
            $this->selectedId = null;

            return;
        }

        if (! $force && $this->selectedId && $visible->contains(fn (array $ev): bool => $ev['id'] === $this->selectedId)) {
            return;
        }

        $nowMs = (int) (CarbonImmutable::now('UTC')->getTimestampMs());
        $upcoming = $visible->filter(fn (array $ev): bool => (int) $ev['at'] >= $nowMs - 120_000);

        $pick = $upcoming->first(fn (array $ev): bool => ($ev['impact'] ?? '') === 'high')
            ?? $upcoming->first(fn (array $ev): bool => ($ev['impact'] ?? '') === 'med')
            ?? $upcoming->first()
            ?? $visible->last();

        $this->selectedId = is_array($pick) ? (string) $pick['id'] : null;
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
            'lastweek' => [
                'from' => (int) $week0->subWeek()->getTimestampMs(),
                'to' => (int) $week0->subSecond()->getTimestampMs(),
            ],
            'nextweek' => [
                'from' => (int) $week0->addWeek()->getTimestampMs(),
                'to' => (int) $week0->addWeeks(2)->subSecond()->getTimestampMs(),
            ],
            default => [
                'from' => (int) $today0->getTimestampMs(),
                'to' => (int) $today0->endOfDay()->getTimestampMs(),
            ],
        };
    }

    private function parseNumber(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $s = trim((string) $raw);

        if ($s === '' || $s === '—') {
            return null;
        }

        $mult = 1.0;

        if (preg_match('/b$/i', $s) === 1) {
            $mult = 1e9;
        } elseif (preg_match('/m$/i', $s) === 1) {
            $mult = 1e6;
        } elseif (preg_match('/k$/i', $s) === 1) {
            $mult = 1e3;
        }

        $s = preg_replace('/[,%$]/', '', $s) ?? $s;
        $s = preg_replace('/[bmk]$/i', '', $s) ?? $s;
        $n = filter_var(trim($s), FILTER_VALIDATE_FLOAT);

        return $n === false ? null : ((float) $n) * $mult;
    }
}
