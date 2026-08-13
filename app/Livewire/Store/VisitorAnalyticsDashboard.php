<?php

namespace App\Livewire\Store;

use App\Models\User;
use App\Services\Analytics\VisitorAnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class VisitorAnalyticsDashboard extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $country = '';

    public string $city = '';

    public string $device = '';

    public string $source = '';

    public ?string $statusMessage = null;

    /**
     * @var array<string, int>
     */
    public array $sectionPages = [
        'top_pages' => 1,
        'top_products' => 1,
        'top_categories' => 1,
        'top_cms_pages' => 1,
        'countries' => 1,
        'regions' => 1,
        'cities' => 1,
        'timezones' => 1,
        'ip_networks' => 1,
        'devices' => 1,
        'browsers' => 1,
        'operating_systems' => 1,
        'sources' => 1,
    ];

    public function mount(): void
    {
        $this->authorizeAccess();
        $today = CarbonImmutable::today((string) config('app.timezone'));
        $this->dateFrom = $today->subDays(29)->toDateString();
        $this->dateTo = $today->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'country', 'city', 'device', 'source'], true)) {
            $this->sectionPages = array_fill_keys(array_keys($this->sectionPages), 1);
        }

        if (in_array($property, ['dateFrom', 'dateTo'], true)) {
            $this->normalizeRange();
        }
    }

    public function clearFilters(): void
    {
        $today = CarbonImmutable::today((string) config('app.timezone'));
        $this->dateFrom = $today->subDays(29)->toDateString();
        $this->dateTo = $today->toDateString();
        $this->country = '';
        $this->city = '';
        $this->device = '';
        $this->source = '';
        $this->statusMessage = null;
        $this->sectionPages = array_fill_keys(array_keys($this->sectionPages), 1);
    }

    #[On('applyAnalyticsFilters')]
    public function applyAnalyticsFilters(
        string $dateFrom = '',
        string $dateTo = '',
        string $country = '',
        string $city = '',
        string $device = '',
        string $source = '',
    ): void {
        $this->dateFrom = mb_substr($dateFrom, 0, 10);
        $this->dateTo = mb_substr($dateTo, 0, 10);
        $this->country = mb_substr($country, 0, 255);
        $this->city = mb_substr($city, 0, 255);
        $this->device = mb_substr($device, 0, 24);
        $this->source = mb_substr($source, 0, 50);
        $this->sectionPages = array_fill_keys(array_keys($this->sectionPages), 1);
        $this->normalizeRange();
    }

    public function showSectionPage(string $section, int $page): void
    {
        if (! array_key_exists($section, $this->sectionPages)) {
            return;
        }

        $this->sectionPages[$section] = max(1, min(10, $page));
    }

    public function render(VisitorAnalyticsReportService $reports): View
    {
        $this->authorizeAccess();
        [$from, $to] = $this->range();
        $filters = array_filter([
            'country' => $this->country,
            'city' => $this->city,
            'device' => $this->device,
            'source' => $this->source,
        ]);
        $report = $reports->report($from, $to, $filters);

        return view('livewire.store.visitor-analytics-dashboard', [
            'report' => $report,
            'exportUrl' => route('admin.store.visitor-analytics.export', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                ...$filters,
            ]),
        ]);
    }

    private function normalizeRange(): void
    {
        [$from, $to] = $this->range();

        if ($from->greaterThan($to)) {
            $this->dateFrom = $to->toDateString();
            $from = $to;
        }

        if ($from->diffInDays($to) > 90) {
            $this->dateFrom = $to->subDays(90)->toDateString();
            $this->statusMessage = __('agricart.analytics.range_limited');
        } else {
            $this->statusMessage = null;
        }
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function range(): array
    {
        $timezone = (string) config('app.timezone');
        $today = CarbonImmutable::today($timezone);

        try {
            $from = CarbonImmutable::parse($this->dateFrom, $timezone)->startOfDay();
            $to = CarbonImmutable::parse($this->dateTo, $timezone)->endOfDay();
        } catch (\Throwable) {
            return [$today->subDays(29), $today->endOfDay()];
        }

        return [$from, $to];
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'store', 'visitor-analytics', 'view')),
            403,
        );
    }
}
