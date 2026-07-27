<div
    class="agricart-delivery-method-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('delivery-method-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:delivery-method-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:delivery-method-form-open.window="$wire.openForm($event.detail.deliveryMethodId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.delivery_methods.sections.method') }}</h3>
        <div class="agricart-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-name-en">
                    {{ __('agricart.delivery_methods.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) required />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-name-ur">
                    {{ __('agricart.delivery_methods.fields.name_ur') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-desc-en">{{ __('agricart.delivery_methods.fields.description_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-desc-en" type="text" wire:model="description_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-desc-ur">{{ __('agricart.delivery_methods.fields.description_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-desc-ur" type="text" wire:model="description_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <span class="agricart-form-panel__label">{{ __('agricart.delivery_methods.fields.payment_options') }}</span>
                <div class="agricart-delivery-method-form__payment-row">
                    <label class="agricart-delivery-method-form__check">
                        <input type="checkbox" wire:model="bank_transfer_allowed" @disabled($this->isReadonly()) />
                        <span>{{ __('agricart.delivery_methods.fields.bank_transfer') }}</span>
                    </label>
                    <label class="agricart-delivery-method-form__check">
                        <input type="checkbox" wire:model="cod_allowed" @disabled($this->isReadonly()) />
                        <span>{{ __('agricart.delivery_methods.fields.cod') }}</span>
                    </label>
                    <label class="agricart-delivery-method-form__check">
                        <input type="checkbox" wire:model="full_advance_required" @disabled($this->isReadonly()) />
                        <span>{{ __('agricart.delivery_methods.fields.full_advance_required') }}</span>
                    </label>
                    <div class="agricart-delivery-method-form__advance">
                        <label class="agricart-form-panel__label" for="agricart-dm-advance">{{ __('agricart.delivery_methods.fields.cod_min_advance_percent') }}</label>
                        <x-filament::input.wrapper>
                            <input id="agricart-dm-advance" type="number" min="0" max="100" step="0.01" wire:model="cod_min_advance_percent" class="fi-input" @disabled($this->isReadonly()) />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-delivery-method-form__advance">
                        <label class="agricart-form-panel__label" for="agricart-dm-sort" title="{{ __('agricart.delivery_methods.hints.sort_order') }}">
                            {{ __('agricart.delivery_methods.fields.sort_order') }}
                        </label>
                        <x-filament::input.wrapper>
                            <input id="agricart-dm-sort" type="number" min="0" wire:model="sort_order" class="fi-input" @disabled($this->isReadonly()) />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-delivery-method-form__status">
                        <label class="agricart-form-panel__label" for="agricart-dm-status" title="{{ __('agricart.delivery_methods.hints.status') }}">
                            {{ __('agricart.delivery_methods.fields.status') }}
                        </label>
                        <x-filament::input.wrapper>
                            <select id="agricart-dm-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                                <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                                <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                            </select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
                <p class="agricart-delivery-method-form__hint">{{ __('agricart.delivery_methods.hints.payment_row') }}</p>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-ship-mode-en">{{ __('agricart.delivery_methods.fields.shipping_charge_mode_en') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-dm-ship-mode-en" wire:model="shipping_charge_mode" class="fi-select-input" @disabled($this->isReadonly())>
                        @foreach ($shippingModes as $mode)
                            <option value="{{ $mode->value }}">{{ __('agricart.delivery_methods.shipping_modes.'.$mode->value) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-ship-mode-ur">{{ __('agricart.delivery_methods.fields.shipping_charge_mode_ur') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-dm-ship-mode-ur" wire:model="shipping_charge_mode" dir="rtl" class="fi-select-input" @disabled($this->isReadonly())>
                        @foreach ($shippingModes as $mode)
                            <option value="{{ $mode->value }}">{{ __('agricart.delivery_methods.shipping_modes.'.$mode->value, [], 'ur') }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-notice-en">{{ __('agricart.delivery_methods.fields.customer_notice_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-notice-en" type="text" wire:model="customer_notice_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-dm-notice-ur">{{ __('agricart.delivery_methods.fields.customer_notice_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-dm-notice-ur" type="text" wire:model="customer_notice_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>
</div>
