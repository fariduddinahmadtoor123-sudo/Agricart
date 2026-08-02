<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="save" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.registration.title') }}</h3>
        <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.registration.hint') }}</p>
        <p class="agricart-store-admin__hint">
            <a href="{{ route('auth.staff.register') }}" target="_blank" rel="noopener">{{ __('agricart.store.admin.registration.view_staff') }}</a>
            ·
            <a href="{{ route('auth.supplier.register') }}" target="_blank" rel="noopener">{{ __('agricart.store.admin.registration.view_supplier') }}</a>
        </p>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.registration.section_staff') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.title_en') }}</label>
                <input type="text" wire:model="staff_title_en" class="agricart-store-admin__input" maxlength="255" />
                @error('staff_title_en') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.title_ur') }}</label>
                <input type="text" wire:model="staff_title_ur" class="agricart-store-admin__input" maxlength="255" dir="rtl" />
                @error('staff_title_ur') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.body_en') }}</label>
                <textarea wire:model="staff_body_en" class="agricart-store-admin__input" rows="8" maxlength="20000"></textarea>
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.registration.body_hint') }}</p>
                @error('staff_body_en') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.body_ur') }}</label>
                <textarea wire:model="staff_body_ur" class="agricart-store-admin__input" rows="8" maxlength="20000" dir="rtl"></textarea>
                @error('staff_body_ur') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="agricart-store-admin__field">
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="staff_require_acceptance" />
                <span>{{ __('agricart.store.admin.registration.require_acceptance') }}</span>
            </label>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.registration.require_acceptance_hint') }}</p>
        </div>

        <h4 class="agricart-store-admin__subtitle">{{ __('agricart.store.admin.registration.section_supplier') }}</h4>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.title_en') }}</label>
                <input type="text" wire:model="supplier_title_en" class="agricart-store-admin__input" maxlength="255" />
                @error('supplier_title_en') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.title_ur') }}</label>
                <input type="text" wire:model="supplier_title_ur" class="agricart-store-admin__input" maxlength="255" dir="rtl" />
                @error('supplier_title_ur') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.body_en') }}</label>
                <textarea wire:model="supplier_body_en" class="agricart-store-admin__input" rows="8" maxlength="20000"></textarea>
                <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.registration.body_hint') }}</p>
                @error('supplier_body_en') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.registration.body_ur') }}</label>
                <textarea wire:model="supplier_body_ur" class="agricart-store-admin__input" rows="8" maxlength="20000" dir="rtl"></textarea>
                @error('supplier_body_ur') <p class="agricart-store-admin__error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="agricart-store-admin__field">
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="supplier_require_acceptance" />
                <span>{{ __('agricart.store.admin.registration.require_acceptance') }}</span>
            </label>
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.registration.supplier_require_hint') }}</p>
        </div>

        <div class="agricart-store-admin__actions">
            <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
        </div>
    </form>
</div>
