<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="saveSettings" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.hero_slider.settings') }}</h3>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="hero_slider_enabled" />
            <span>{{ __('agricart.store.admin.hero_slider.enabled') }}</span>
        </label>

        <div class="agricart-store-admin__field">
            <label for="hero-slider-interval">{{ __('agricart.store.admin.hero_slider.interval') }}</label>
            <select id="hero-slider-interval" wire:model="hero_slider_interval" class="agricart-store-admin__input">
                @foreach ($intervalOptions as $step => $seconds)
                    <option value="{{ $step }}">{{ $seconds }} {{ __('agricart.store.admin.hero_slider.seconds') }}</option>
                @endforeach
            </select>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.hero_slider.interval_hint') }}</p>
        </div>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.hero_slider.slides') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.hero_slider.slides_hint', ['size' => $recommendedSize]) }}</p>

        <form
            wire:submit="saveSlide"
            wire:key="hero-slide-form-{{ $editingId ?? 'new' }}"
            id="store-hero-slide-form"
            class="agricart-store-admin__form"
        >
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.hero_slider.image') }}</label>
                <p class="agricart-store-admin__hint" style="font-weight:700;margin-block:0.35rem 0.5rem;">
                    {{ __('agricart.store.admin.hero_slider.image_size', ['size' => $recommendedSize]) }}
                </p>
                <input type="file" wire:model="imageUpload" accept="image/jpeg,image/png,image/webp" class="agricart-store-admin__input" @if (! $editingId) required @endif />
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.hero_slider.image_hint', ['size' => $recommendedSize]) }}</p>
                @if ($imageUpload)
                    <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.header.uploading') }}</p>
                @endif
            </div>

            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.hero_slider.alt_en') }}</label>
                    <input type="text" wire:model="alt_en" class="agricart-store-admin__input" />
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.hero_slider.alt_ur') }}</label>
                    <input type="text" wire:model="alt_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>

            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.hero_slider.link') }}</label>
                <input type="text" wire:model="link" class="agricart-store-admin__input" placeholder="/products" />
            </div>

            <div class="agricart-store-admin__actions">
                <button type="submit" class="agricart-store-admin__btn">
                    {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetSlideForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">
                        {{ __('agricart.store.admin.cancel') }}
                    </button>
                @endif
            </div>
        </form>

        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.hero_slider.preview') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($slides as $slide)
                        <tr wire:key="hero-slide-{{ $slide->id }}">
                            <td>
                                <img src="{{ $slide->imageUrl() }}" alt="" style="max-width: 8rem; max-height: 4rem; object-fit: cover;" />
                                @if (filled($slide->alt_en))
                                    <span class="agricart-store-admin__hint">{{ $slide->alt_en }}</span>
                                @endif
                            </td>
                            <td>{{ $slide->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="moveSlide({{ $slide->id }}, 'up')" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.move_up') }}</button>
                                <button type="button" wire:click="moveSlide({{ $slide->id }}, 'down')" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.move_down') }}</button>
                                <button type="button" wire:click="editSlide({{ $slide->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="toggleSlide({{ $slide->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deleteSlide({{ $slide->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">{{ __('agricart.store.admin.hero_slider.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
