<?php

namespace App\Livewire\SalesChannels;

use App\Models\SalesChannel;
use App\Models\User;
use App\Services\SalesChannels\FacebookCatalogFeedService;
use App\Services\SalesChannels\SalesChannelCatalog;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
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

    public string $facebook_page_url = '';

    public string $facebook_price_field = 'wholesale_price';

    public string $facebook_feed_token = '';

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
            $this->prepareDefaultsForSlug($channelSlug);
        } else {
            $this->channel_slug = $this->firstAvailableCreateSlug() ?? SalesChannel::SLUG_ONLINE_STORE;
            $this->prepareDefaultsForSlug($this->channel_slug);
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

    public function updatedChannelSlug(string $value): void
    {
        if ($this->mode === 'create') {
            $this->prepareDefaultsForSlug($value);
        }
    }

    public function regenerateFacebookToken(): void
    {
        if (! $this->userCanEdit() || $this->channel_slug !== SalesChannel::SLUG_FACEBOOK) {
            return;
        }

        $this->facebook_feed_token = bin2hex(random_bytes(24));
        $this->setStatus(__('sales_channels.facebook.token_regenerated'), 'success');
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

            // Browser may show the only remaining option (e.g. Facebook) while Livewire
            // still holds online_store — keep slug aligned with available choices.
            if ($dropdownOptions !== [] && ! array_key_exists($this->channel_slug, $dropdownOptions)) {
                $this->channel_slug = (string) array_key_first($dropdownOptions);
                $this->prepareDefaultsForSlug($this->channel_slug);
            }
        }

        $feedUrl = null;

        if ($this->channel_slug === SalesChannel::SLUG_FACEBOOK && filled($this->facebook_feed_token)) {
            $feedUrl = route('store.facebook_catalog_feed', ['token' => $this->facebook_feed_token]);
        }

        return view('livewire.sales-channels.form', [
            'channelOptions' => $dropdownOptions,
            'isFacebook' => $this->channel_slug === SalesChannel::SLUG_FACEBOOK,
            'isOnlineStore' => $this->channel_slug === SalesChannel::SLUG_ONLINE_STORE,
            'facebookFeedUrl' => $feedUrl,
            'facebookFeedStats' => $this->channel_slug === SalesChannel::SLUG_FACEBOOK
                ? app(FacebookCatalogFeedService::class)->eligibilityStats()
                : null,
        ]);
    }

    private function firstAvailableCreateSlug(): ?string
    {
        $existing = SalesChannel::query()->pluck('slug')->all();

        foreach (SalesChannelCatalog::implementedSlugs() as $slug) {
            if (! in_array($slug, $existing, true)) {
                return $slug;
            }
        }

        return null;
    }

    private function prepareDefaultsForSlug(string $slug): void
    {
        if ($slug === SalesChannel::SLUG_FACEBOOK && $this->facebook_feed_token === '') {
            $this->facebook_feed_token = bin2hex(random_bytes(24));
            $this->facebook_price_field = 'wholesale_price';
        }
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
        $this->facebook_page_url = (string) ($channel->setting('page_url') ?? '');
        $this->facebook_price_field = (string) ($channel->setting('price_field') ?? 'wholesale_price');
        $this->facebook_feed_token = (string) ($channel->setting('feed_token') ?? '');

        if ($channel->slug === SalesChannel::SLUG_FACEBOOK && $this->facebook_feed_token === '') {
            $this->facebook_feed_token = bin2hex(random_bytes(24));
        }
    }

    private function persist(): bool
    {
        if (! $this->userCanEdit()) {
            $this->setStatus(__('sales_channels.hints.no_edit_permission'), 'danger');

            return false;
        }

        $rules = [
            'channel_slug' => ['required', 'string', 'in:'.implode(',', SalesChannelCatalog::implementedSlugs())],
        ];

        if ($this->channel_slug === SalesChannel::SLUG_ONLINE_STORE) {
            $rules = array_merge($rules, [
                'offline_message_en' => ['nullable', 'string', 'max:500'],
                'offline_message_ur' => ['nullable', 'string', 'max:500'],
                'banner_message_en' => ['nullable', 'string', 'max:500'],
                'banner_message_ur' => ['nullable', 'string', 'max:500'],
                'maintenance_message_en' => ['nullable', 'string', 'max:500'],
                'maintenance_message_ur' => ['nullable', 'string', 'max:500'],
            ]);
        }

        if ($this->channel_slug === SalesChannel::SLUG_FACEBOOK) {
            $this->facebook_page_url = trim($this->facebook_page_url);
            $rules = array_merge($rules, [
                'facebook_page_url' => $this->facebook_page_url === ''
                    ? ['nullable', 'string', 'max:500']
                    : ['required', 'string', 'max:500', 'url'],
                'facebook_price_field' => ['required', 'in:wholesale_price,retail_price'],
                'facebook_feed_token' => ['required', 'string', 'min:24', 'max:128'],
            ]);
        }

        $this->validate($rules);

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

            if ($this->channel_slug === SalesChannel::SLUG_ONLINE_STORE) {
                $channel->fill([
                    'offline_message_en' => $this->offline_message_en ?: null,
                    'offline_message_ur' => $this->offline_message_ur ?: null,
                    'banner_message_en' => $this->banner_message_en ?: null,
                    'banner_message_ur' => $this->banner_message_ur ?: null,
                    'maintenance_message_en' => $this->maintenance_message_en ?: null,
                    'maintenance_message_ur' => $this->maintenance_message_ur ?: null,
                ]);
            }

            if ($this->channel_slug === SalesChannel::SLUG_FACEBOOK) {
                $channel->mergeSettings([
                    'page_url' => Str::of($this->facebook_page_url)->trim()->toString(),
                    'price_field' => $this->facebook_price_field,
                    'feed_token' => $this->facebook_feed_token,
                ]);
            }

            $channel->save();

            app(SalesChannelService::class)->clearCache();
            app(FacebookCatalogFeedService::class)->clearCache();

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
        $this->channel_slug = $this->firstAvailableCreateSlug() ?? SalesChannel::SLUG_ONLINE_STORE;
        $this->offline_message_en = '';
        $this->offline_message_ur = '';
        $this->banner_message_en = '';
        $this->banner_message_ur = '';
        $this->maintenance_message_en = '';
        $this->maintenance_message_ur = '';
        $this->facebook_page_url = '';
        $this->facebook_price_field = 'wholesale_price';
        $this->facebook_feed_token = '';
        $this->statusMessage = null;
        $this->statusType = 'success';
        $this->prepareDefaultsForSlug($this->channel_slug);
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
