<div class="agricart-product-watermark">
    @if ($statusMessage)
        <div @class([
            'agricart-product-watermark__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-product-watermark__panel">
        <header class="agricart-product-watermark__head">
            <h2>{{ __('agricart.product_watermark.title') }}</h2>
            <p>{{ __('agricart.product_watermark.hint') }}</p>
            <p class="agricart-product-watermark__meta">{{ __('agricart.product_watermark.version', ['version' => $version]) }}</p>
        </header>

        <form wire:submit="save" class="agricart-product-watermark__form">
            <label class="agricart-product-watermark__check">
                <input type="checkbox" wire:model="enabled" @disabled(! $canManage || $saving || $applying)>
                <span>{{ __('agricart.product_watermark.fields.enabled') }}</span>
            </label>

            <div class="agricart-product-watermark__logo">
                <span>{{ __('agricart.product_watermark.fields.logo') }}</span>
                <p class="agricart-product-watermark__help">{{ __('agricart.product_watermark.fields.logo_hint') }}</p>
                @if ($logoPreviewUrl)
                    <img src="{{ $logoPreviewUrl }}" alt="" width="120" height="120" class="agricart-product-watermark__logo-preview">
                    <button type="button" class="agricart-product-watermark__btn" wire:click="removeLogo" @disabled(! $canManage || $saving || $applying)>
                        {{ __('agricart.product_watermark.actions.remove_logo') }}
                    </button>
                @endif
                <input type="file" wire:model="logoUpload" accept="image/*" @disabled(! $canManage || $saving || $applying)>
                <div wire:loading wire:target="logoUpload">{{ __('agricart.product_watermark.actions.uploading') }}</div>
            </div>

            <label class="agricart-product-watermark__field">
                <span>{{ __('agricart.product_watermark.fields.position') }}</span>
                <select wire:model="position" @disabled(! $canManage || $saving || $applying)>
                    @foreach ($positions as $value)
                        <option value="{{ $value }}">{{ __('agricart.product_watermark.positions.'.$value) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="agricart-product-watermark__field">
                <span>{{ __('agricart.product_watermark.fields.size_percent') }}</span>
                <input type="number" min="5" max="40" wire:model="sizePercent" @disabled(! $canManage || $saving || $applying)>
            </label>

            <label class="agricart-product-watermark__field">
                <span>{{ __('agricart.product_watermark.fields.opacity') }}</span>
                <input type="number" min="10" max="100" wire:model="opacity" @disabled(! $canManage || $saving || $applying)>
            </label>

            <fieldset class="agricart-product-watermark__marketplaces">
                <legend>{{ __('agricart.product_watermark.fields.marketplace_title') }}</legend>
                <p class="agricart-product-watermark__help">{{ __('agricart.product_watermark.fields.marketplace_hint') }}</p>
                @foreach ($platforms as $platform)
                    <label class="agricart-product-watermark__field">
                        <span>{{ __('agricart.product_watermark.platforms.'.$platform) }}</span>
                        <select wire:model="marketplacePreferences.{{ $platform }}" @disabled(! $canManage || $saving || $applying)>
                            <option value="watermarked">{{ __('agricart.product_watermark.sources.watermarked') }}</option>
                            <option value="original">{{ __('agricart.product_watermark.sources.original') }}</option>
                        </select>
                    </label>
                @endforeach
            </fieldset>

            <div class="agricart-product-watermark__actions">
                <button type="submit" class="agricart-product-watermark__btn is-primary" @disabled(! $canManage || $saving || $applying)>
                    {{ $saving ? __('agricart.product_watermark.actions.saving') : __('agricart.product_watermark.actions.save') }}
                </button>
                <button type="button" class="agricart-product-watermark__btn" wire:click="applyToAllProducts" @disabled(! $canManage || $saving || $applying)>
                    {{ $applying ? __('agricart.product_watermark.actions.applying') : __('agricart.product_watermark.actions.apply') }}
                </button>
                <button type="button" class="agricart-product-watermark__btn" wire:click="forceApplyToAllProducts" @disabled(! $canManage || $saving || $applying)>
                    {{ __('agricart.product_watermark.actions.apply_force') }}
                </button>
            </div>
        </form>
    </section>
</div>

@include('livewire.settings.partials.watermark-settings-styles')
