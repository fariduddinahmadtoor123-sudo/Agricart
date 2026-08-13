<?php

namespace App\Livewire\SalesChannels;

use App\Models\SalesChannel;
use App\Models\User;
use App\Services\SalesChannels\FacebookCatalogFeedService;
use App\Services\SalesChannels\SalesChannelCatalog;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ListSalesChannels extends Component
{
    /** @var list<string> */
    public array $visibleColumns = ['name', 'status', 'maintenance'];

    public string $search = '';

    public string $statusFilter = '';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('sales-channels-saved')]
    public function refreshList(): void
    {
        $this->statusMessage = null;
    }

    #[On('sales-channels-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('sales-channels-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
        }
    }

    #[On('sales-channels-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
    }

    #[On('sales-channels-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('sales-channels-columns-sync', columns: $this->visibleColumns);
    }

    public function userCanToggle(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'marketplace', 'sales-channels', 'edit')
            || \App\Services\Access\ModulePageAccess::can($user, 'marketplace', 'sales-channels', 'view');
    }

    public function toggleChannel(int $channelId): void
    {
        if (! $this->userCanToggle()) {
            return;
        }

        $channel = SalesChannel::query()->find($channelId);

        if ($channel === null) {
            return;
        }

        $this->setEnabled($channelId, ! $channel->is_enabled);
    }

    public function toggleMaintenance(int $channelId): void
    {
        if (! $this->userCanToggle()) {
            return;
        }

        $channel = SalesChannel::query()->find($channelId);

        if ($channel === null || $channel->slug !== SalesChannel::SLUG_ONLINE_STORE) {
            return;
        }

        $channel->maintenance_mode = ! (bool) $channel->maintenance_mode;
        $channel->save();

        app(SalesChannelService::class)->clearCache();

        $this->statusMessage = $channel->maintenance_mode
            ? __('sales_channels.messages.maintenance_enabled')
            : __('sales_channels.messages.maintenance_disabled');
        $this->statusType = 'success';
    }

    public function setStoreOnline(int $channelId): void
    {
        if (! $this->userCanToggle()) {
            return;
        }

        $this->setEnabled($channelId, true);
    }

    public function setStoreOffline(int $channelId): void
    {
        if (! $this->userCanToggle()) {
            return;
        }

        $this->setEnabled($channelId, false);
    }

    public function openEdit(int $channelId): void
    {
        $channel = SalesChannel::query()->find($channelId);

        if ($channel === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: $channel->slug === SalesChannel::SLUG_FACEBOOK
                ? __('sales_channels.facebook.edit_title')
                : __('sales_channels.actions.edit_messages'),
            resource: __('agricart.pages.marketplace.sales-channels'),
            formKey: 'sales-channel',
            salesChannelId: $channelId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        SalesChannel::onlineStore();

        $records = $this->filteredChannels();

        return view('livewire.sales-channels.list', [
            'records' => $records,
            'canToggle' => $this->userCanToggle(),
            'storefrontUrl' => url('/'),
        ]);
    }

    /**
     * @return Collection<int, SalesChannel>
     */
    private function filteredChannels(): Collection
    {
        return SalesChannel::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function (SalesChannel $channel): bool {
                if (filled($this->search)) {
                    $needle = mb_strtolower($this->search);
                    $haystack = mb_strtolower($channel->name_en . ' ' . ($channel->name_ur ?? ''));

                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                if ($this->statusFilter === 'on' && ! $channel->is_enabled) {
                    return false;
                }

                if ($this->statusFilter === 'off' && $channel->is_enabled) {
                    return false;
                }

                return in_array($channel->slug, SalesChannelCatalog::implementedSlugs(), true);
            })
            ->values();
    }

    private function setEnabled(int $channelId, bool $enabled): void
    {
        $channel = SalesChannel::query()->find($channelId);

        if ($channel === null) {
            return;
        }

        $channel->is_enabled = $enabled;
        $channel->save();

        app(SalesChannelService::class)->clearCache();
        app(FacebookCatalogFeedService::class)->clearCache();

        if ($channel->slug === SalesChannel::SLUG_FACEBOOK) {
            $this->statusMessage = $enabled
                ? __('sales_channels.messages.facebook_enabled')
                : __('sales_channels.messages.facebook_disabled');
        } else {
            $this->statusMessage = $enabled
                ? __('sales_channels.messages.store_enabled')
                : __('sales_channels.messages.store_disabled');
        }
        $this->statusType = 'success';
    }
}
