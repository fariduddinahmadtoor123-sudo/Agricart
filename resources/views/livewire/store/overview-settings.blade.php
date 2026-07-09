<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.overview.preview') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.overview.preview_hint') }}</p>
        <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="agricart-store-admin__link-btn">
            {{ __('agricart.store.admin.overview.open_store') }}
        </a>
    </section>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.overview.category_grid') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.overview.category_grid_heading_hint') }}</p>

        <form wire:submit="save" class="agricart-store-admin__form">
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="category_grid_enabled" />
                <span>{{ __('agricart.store.admin.overview.show_grid') }}</span>
            </label>

            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.overview.columns') }}</label>
                <select wire:model="category_grid_columns" class="agricart-store-admin__input">
                    @foreach ([3, 4, 5, 6] as $cols)
                        <option value="{{ $cols }}">{{ $cols }}</option>
                    @endforeach
                </select>
            </div>

            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.title_en') }}</label>
                    <input type="text" wire:model="category_grid_title_en" class="agricart-store-admin__input" />
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.title_ur') }}</label>
                    <input type="text" wire:model="category_grid_title_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>

            <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
        </form>
    </section>
</div>
