<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="save" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.top_bar.title') }}</h3>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="top_bar_enabled" />
            <span>{{ __('agricart.store.admin.top_bar.enabled') }}</span>
        </label>

        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.left_en') }}</label>
                <input type="text" wire:model="top_bar_left_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.left_ur') }}</label>
                <input type="text" wire:model="top_bar_left_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.center_en') }}</label>
                <input type="text" wire:model="top_bar_center_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.center_ur') }}</label>
                <input type="text" wire:model="top_bar_center_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.right_en') }}</label>
                <input type="text" wire:model="top_bar_right_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.top_bar.right_ur') }}</label>
                <input type="text" wire:model="top_bar_right_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>
</div>
