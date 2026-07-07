<div
    class="agricart-secret-coding-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('secret-coding-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:secret-coding-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:secret-coding-form-open.window="$wire.openForm($event.detail.secretCodingId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <p class="agricart-form-panel__hint">{{ __('secret_coding.hints.scaffold') }}</p>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-secret-coding-number">{{ __('secret_coding.fields.number') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-secret-coding-number" type="text" wire:model="profile_number" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-secret-coding-status">{{ __('secret_coding.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-secret-coding-status" wire:model="status" class="fi-input" @disabled($this->isReadonly())>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}">{{ __('secret_coding.statuses.' . $statusOption) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-secret-coding-name-en">
                    {{ __('secret_coding.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-secret-coding-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-secret-coding-name-ur">{{ __('secret_coding.fields.name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-secret-coding-name-ur" type="text" wire:model="name_ur" class="fi-input" @disabled($this->isReadonly()) dir="rtl" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-secret-coding-notes">{{ __('secret_coding.fields.notes') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-secret-coding-notes" rows="2" wire:model="notes" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly())></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('secret_coding.sections.digit_mapping') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('secret_coding.hints.digit_mapping') }}</p>

        <div class="agricart-secret-coding-form__digits">
            @foreach ($digits as $digit)
                <div class="agricart-form-panel__field" wire:key="secret-digit-{{ $digit }}">
                    <label class="agricart-form-panel__label" for="agricart-secret-digit-{{ $digit }}">
                        {{ __('secret_coding.fields.digit', ['digit' => $digit]) }}
                    </label>
                    <x-filament::input.wrapper>
                        <input
                            id="agricart-secret-digit-{{ $digit }}"
                            type="text"
                            wire:model="digitSlots.{{ $digit }}"
                            class="fi-input"
                            maxlength="32"
                            @disabled($this->isReadonly())
                        />
                    </x-filament::input.wrapper>
                </div>
            @endforeach
        </div>
    </section>
</div>
