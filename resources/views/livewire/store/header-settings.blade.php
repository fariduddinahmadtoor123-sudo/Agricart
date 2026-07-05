<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="save" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.header.title') }}</h3>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="header_enabled" />
            <span>{{ __('agricart.store.admin.header.enabled') }}</span>
        </label>

        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.site_name_en') }}</label>
                <input type="text" wire:model="site_name_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.site_name_ur') }}</label>
                <input type="text" wire:model="site_name_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.search_en') }}</label>
                <input type="text" wire:model="search_placeholder_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.search_ur') }}</label>
                <input type="text" wire:model="search_placeholder_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>

        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.header.logo') }}</label>
            @if ($logoPreviewUrl)
                <div class="agricart-store-admin__logo-preview">
                    <img src="{{ $logoPreviewUrl }}" alt="" />
                    <button type="button" wire:click="removeLogo" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.header.remove_logo') }}</button>
                </div>
            @endif
            <input type="file" wire:model="logoUpload" accept="image/*" class="agricart-store-admin__file" />
            <div wire:loading wire:target="logoUpload" class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</div>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.header.social') }}</h4>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.social_links_hint') }}</p>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="show_social" />
            <span>{{ __('agricart.store.admin.header.show_social') }}</span>
        </label>
        <div class="agricart-store-admin__grid-2">
            @foreach (['facebook', 'instagram', 'whatsapp', 'youtube', 'twitter'] as $network)
                <div class="agricart-store-admin__field">
                    <label>{{ ucfirst($network) }}</label>
                    <input type="url" wire:model="social_{{ $network }}" class="agricart-store-admin__input" placeholder="https://" />
                </div>
            @endforeach
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.header.auth_cart') }}</h4>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="show_auth_links" />
            <span>{{ __('agricart.store.admin.header.show_auth') }}</span>
        </label>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.login_url') }}</label>
                <input type="text" wire:model="login_url" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.register_url') }}</label>
                <input type="text" wire:model="register_url" class="agricart-store-admin__input" />
            </div>
        </div>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="show_cart" />
            <span>{{ __('agricart.store.admin.header.show_cart') }}</span>
        </label>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.header.whatsapp_float') }}</h4>
        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="whatsapp_enabled" />
            <span>{{ __('agricart.store.admin.header.whatsapp_enabled') }}</span>
        </label>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.whatsapp_number') }}</label>
                <input type="text" wire:model="whatsapp_number" class="agricart-store-admin__input" placeholder="923001234567" />
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.whatsapp_number_hint') }}</p>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.whatsapp_message_en') }}</label>
                <input type="text" wire:model="whatsapp_message_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.header.whatsapp_message_ur') }}</label>
                <input type="text" wire:model="whatsapp_message_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>
</div>
