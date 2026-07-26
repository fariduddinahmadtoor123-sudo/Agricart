<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="save" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.seo.title') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.seo.hint') }}</p>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.seo.verification') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.seo.google_site_verification') }}</label>
                <input type="text" wire:model="google_site_verification" class="agricart-store-admin__input" autocomplete="off" />
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.seo.google_site_verification_hint') }}</p>
                @error('google_site_verification') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.seo.bing_site_verification') }}</label>
                <input type="text" wire:model="bing_site_verification" class="agricart-store-admin__input" autocomplete="off" />
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.seo.bing_site_verification_hint') }}</p>
                @error('bing_site_verification') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.seo.tracking') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.seo.ga_measurement_id') }}</label>
                <input type="text" wire:model="ga_measurement_id" class="agricart-store-admin__input" placeholder="G-XXXXXXXXXX" autocomplete="off" />
                @error('ga_measurement_id') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.seo.gtm_container_id') }}</label>
                <input type="text" wire:model="gtm_container_id" class="agricart-store-admin__input" placeholder="GTM-XXXXXXX" autocomplete="off" />
                @error('gtm_container_id') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.seo.meta_pixel_id') }}</label>
                <input type="text" wire:model="meta_pixel_id" class="agricart-store-admin__input" placeholder="1234567890" autocomplete="off" />
                @error('meta_pixel_id') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.seo.social_image') }}</h4>
        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.seo.default_og_image') }}</label>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.seo.default_og_image_hint') }}</p>
            @if ($ogImagePreviewUrl)
                <div class="agricart-store-admin__logo-preview">
                    <img src="{{ $ogImagePreviewUrl }}" alt="" />
                    <button type="button" wire:click="removeOgImage" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.seo.remove_og_image') }}</button>
                </div>
            @endif
            <input type="file" wire:model="ogImageUpload" accept="image/*" class="agricart-store-admin__file" />
            <div wire:loading wire:target="ogImageUpload" class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</div>
            @error('ogImageUpload') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.seo.merchant') }}</h4>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.seo.merchant_hint') }}</p>
        <p class="agricart-store-admin__hint"><code>{{ url('/feeds/google-merchant.xml') }}</code></p>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>
</div>
