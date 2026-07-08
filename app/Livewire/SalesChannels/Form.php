<?php

namespace App\Livewire\SalesChannels;

use App\Models\SalesChannel;
use App\Models\User;
use App\Services\SalesChannels\SalesChannelCatalog;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $salesChannelId = null;

    public string $mode = 'create';

    public string $channel_slug = '';

    public string $offline_message_en = '';

    public string $offline_message_ur = '';

    public string $banner_message_en = '';

    public string $banner_message_ur = '';

    public string $maintenance_message_en = '';

    public string $maintenance_message_ur = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->channel_slug = SalesChannel::SLUG_ONLINE_STORE;
    }

    #[On('sales-channel-form-open')]
    public function openForm(?int $salesChannelId = null, string $mode = 'create', ?string $channelSlug = null): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->salesChannelId = $salesChannelId;

        if ($salesChannelId) {
            $this->loadRecord($salesChannelId);
        } elseif (filled($channelSlug)) {
            $this->channel_slug = $channelSlug;
        }
    }

    #[On('sales-channel-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('sales-channels-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function userCanEdit(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('marketplace.edit');
    }

    public function render(): View
    {
        $existingSlugs = SalesChannel::query()
            ->pluck('slug')
            ->all();

        $dropdownOptions = SalesChannelCatalog::dropdownLabels();

        if ($this->mode === 'create') {
            $dropdownOptions = array_filter(
                $dropdownOptions,
                fn (string $label, string $slug): bool => ! in_array($slug, $existingSlugs, true),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        return view('livewire.sales-channels.form', [
            'channelOptions' => $dropdownOptions,
        ]);
    }

    private function loadRecord(int $recordId): void
    {
        $channel = SalesChannel::query()->findOrFail($recordId);

        $this->channel_slug = $channel->slug;
        $this->offline_message_en = $channel->offline_message_en ?? '';
        $this->offline_message_ur = $channel->offline_message_ur ?? '';
        $this->banner_message_en = $channel->banner_message_en ?? '';
        $this->banner_message_ur = $channel->banner_message_ur ?? '';
        $this->maintenance_message_en = $channel->maintenance_message_en ?? '';
        $this->maintenance_message_ur = $channel->maintenance_message_ur ?? '';
    }

    private function persist(): bool
    {
        if (! $this->userCanEdit()) {
            $this->setStatus(__('sales_channels.hints.no_edit_permission'), 'danger');

            return false;
        }

        $this->validate([
            'channel_slug' => ['required', 'string', 'in:' . implode(',', SalesChannelCatalog::implementedSlugs())],
            'offline_message_en' => ['nullable', 'string', 'max:500'],
            'offline_message_ur' => ['nullable', 'string', 'max:500'],
            'banner_message_en' => ['nullable', 'string', 'max:500'],
            'banner_message_ur' => ['nullable', 'string', 'max:500'],
            'maintenance_message_en' => ['nullable', 'string', 'max:500'],
            'maintenance_message_ur' => ['nullable', 'string', 'max:500'],
        ]);

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            if ($this->salesChannelId) {
                $channel = SalesChannel::query()->findOrFail($this->salesChannelId);
            } else {
                $channel = SalesChannel::query()->firstOrNew(['slug' => $this->channel_slug]);
                $defaults = SalesChannelCatalog::defaultsForSlug($this->channel_slug);
                $channel->fill($defaults);
            }

            $channel->fill([
                'offline_message_en' => $this->offline_message_en ?: null,
                'offline_message_ur' => $this->offline_message_ur ?: null,
                'banner_message_en' => $this->banner_message_en ?: null,
                'banner_message_ur' => $this->banner_message_ur ?: null,
                'maintenance_message_en' => $this->maintenance_message_en ?: null,
                'maintenance_message_ur' => $this->maintenance_message_ur ?: null,
            ]);
            $channel->save();

            app(SalesChannelService::class)->clearCache();

            $this->setStatus(__('sales_channels.messages.saved'), 'success');

            return true;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function resetForm(): void
    {
        $this->salesChannelId = null;
        $this->mode = 'create';
        $this->channel_slug = SalesChannel::SLUG_ONLINE_STORE;
        $this->offline_message_en = '';
        $this->offline_message_ur = '';
        $this->banner_message_en = '';
        $this->banner_message_ur = '';
        $this->maintenance_message_en = '';
        $this->maintenance_message_ur = '';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('sales-channel-form-busy', saving: $this->saving);
    }
}
