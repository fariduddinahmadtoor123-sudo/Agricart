<div
    class="agricart-sales-channel-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('sales-channel-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:sales-channel-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:sales-channel-form-open.window="$wire.openForm($event.detail.salesChannelId ?? null, $event.detail.mode ?? 'create', $event.detail.channelSlug ?? null)"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('sales_channels.fields.messages_off_section') }}</h3>
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-slug">
                    {{ __('sales_channels.fields.channel') }}
                </label>
                <x-filament::input.wrapper>
                    @if ($this->mode === 'edit')
                        <input
                            id="agricart-sales-channel-slug"
                            type="text"
                            class="fi-input"
                            value="{{ $channelOptions[$channel_slug] ?? $channel_slug }}"
                            readonly
                        />
                    @else
                        <select id="agricart-sales-channel-slug" wire:model="channel_slug" class="fi-input" @disabled(! $this->userCanEdit())>
                            @foreach ($channelOptions as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-offline-en">
                    {{ __('sales_channels.fields.offline_message_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-offline-en"
                        type="text"
                        wire:model="offline_message_en"
                        class="fi-input"
                        @disabled(! $this->userCanEdit())
                        placeholder="{{ __('sales_channels.placeholders.offline_message') }}"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-offline-ur">
                    {{ __('sales_channels.fields.offline_message_ur') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-offline-ur"
                        type="text"
                        wire:model="offline_message_ur"
                        class="fi-input"
                        dir="rtl"
                        @disabled(! $this->userCanEdit())
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-banner-en">
                    {{ __('sales_channels.fields.banner_message_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-banner-en"
                        type="text"
                        wire:model="banner_message_en"
                        class="fi-input"
                        @disabled(! $this->userCanEdit())
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-banner-ur">
                    {{ __('sales_channels.fields.banner_message_ur') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-banner-ur"
                        type="text"
                        wire:model="banner_message_ur"
                        class="fi-input"
                        dir="rtl"
                        @disabled(! $this->userCanEdit())
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('sales_channels.fields.maintenance_messages') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('sales_channels.hints.maintenance_message') }}</p>
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-maint-en">
                    {{ __('sales_channels.fields.maintenance_message_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-maint-en"
                        type="text"
                        wire:model="maintenance_message_en"
                        class="fi-input"
                        @disabled(! $this->userCanEdit())
                        placeholder="{{ __('sales_channels.placeholders.maintenance_message') }}"
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-sales-channel-maint-ur">
                    {{ __('sales_channels.fields.maintenance_message_ur') }}
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="agricart-sales-channel-maint-ur"
                        type="text"
                        wire:model="maintenance_message_ur"
                        class="fi-input"
                        dir="rtl"
                        @disabled(! $this->userCanEdit())
                        placeholder="{{ __('sales_channels.placeholders.maintenance_message_ur') }}"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>
</div>
