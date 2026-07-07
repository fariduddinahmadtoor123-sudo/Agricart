@php
    use Filament\Support\Icons\Heroicon;

    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-user-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('user-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:user-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:user-form-open.window="$wire.openForm($event.detail.userId ?? null, $event.detail.mode ?? 'create')"
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

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.users.fields.user_number') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ $user_number ?: __('agricart.users.hints.auto_on_save') }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-user-name">
                    {{ __('agricart.users.fields.name') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-user-name" type="text" wire:model="name" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-user-role">
                    {{ __('agricart.users.fields.role') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <select id="agricart-user-role" wire:model="role_id" class="fi-select-input" @disabled($readonly)>
                        <option value="">{{ __('agricart.users.hints.select_role') }}</option>
                        @foreach ($roleOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-user-status">{{ __('agricart.form_panel.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-user-status" wire:model.live="status" class="fi-select-input" @disabled($readonly)>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>
        </div>

        <div @class([
            'agricart-user-form__credentials-grid',
            'agricart-user-form__credentials-grid--edit' => $mode !== 'create',
        ])>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-user-email">
                    {{ __('agricart.users.fields.email') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-user-email" type="email" wire:model="email" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-user-address">{{ __('agricart.users.fields.address') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-user-address" type="text" wire:model="address" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            @if ($mode === 'create')
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-user-password">
                        {{ __('agricart.users.fields.password') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-agricart.password-field
                        id="agricart-user-password"
                        wire:model="password"
                        input-class="fi-input"
                        autocomplete="new-password"
                        :disabled="$readonly"
                    />
                </div>

                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-user-password-confirm">
                        {{ __('agricart.users.fields.password_confirm') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-agricart.password-field
                        id="agricart-user-password-confirm"
                        wire:model="password_confirmation"
                        input-class="fi-input"
                        autocomplete="new-password"
                        :disabled="$readonly"
                    />
                </div>
            @endif
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__contacts-split">
            <div class="agricart-user-form__contacts-column">
                <div class="agricart-user-form__repeatable-header">
                    <h3 class="agricart-form-panel__section-title">{{ __('agricart.users.sections.phones') }}</h3>
                </div>

                <div class="agricart-user-form__repeatable-headings">
                    <span class="agricart-form-panel__label">{{ __('agricart.users.fields.contact_person') }}</span>
                    <span class="agricart-form-panel__label">{{ __('agricart.users.fields.phone_number') }}</span>
                    <span class="agricart-user-form__repeatable-spacer" aria-hidden="true"></span>
                </div>

                @foreach ($phoneRows as $index => $row)
                    <div class="agricart-user-form__repeatable-row" wire:key="user-phone-{{ $index }}">
                        <div class="agricart-form-panel__field">
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="phoneRows.{{ $index }}.contact_person" class="fi-input" placeholder="{{ __('agricart.users.hints.contact_person') }}" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="phoneRows.{{ $index }}.phone_number" class="fi-input" placeholder="{{ __('agricart.users.hints.phone_number') }}" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-user-form__repeatable-actions">
                            @if ($index === count($phoneRows) - 1)
                                <button type="button" class="agricart-product-form__icon-btn" wire:click="addPhoneRow()" title="{{ __('agricart.users.actions.add_phone') }}" @disabled($readonly)>+</button>
                            @endif
                            @if (count($phoneRows) > 1)
                                <button type="button" class="agricart-product-form__icon-btn agricart-product-form__icon-btn--muted" wire:click="removePhoneRow({{ $index }})" title="{{ __('agricart.users.actions.remove') }}" @disabled($readonly)>×</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="agricart-user-form__contacts-column">
                <div class="agricart-user-form__repeatable-header">
                    <h3 class="agricart-form-panel__section-title">{{ __('agricart.users.sections.bank_accounts') }}</h3>
                </div>

                <div class="agricart-user-form__bank-headings">
                    <span class="agricart-form-panel__label">{{ __('agricart.users.fields.bank_name') }}</span>
                    <span class="agricart-form-panel__label">{{ __('agricart.users.fields.account_title') }}</span>
                    <span class="agricart-form-panel__label">{{ __('agricart.users.fields.account_number') }}</span>
                    <span class="agricart-user-form__repeatable-spacer" aria-hidden="true"></span>
                </div>

                @foreach ($bankRows as $index => $row)
                    <div class="agricart-user-form__bank-row" wire:key="user-bank-{{ $index }}">
                        <div class="agricart-form-panel__field">
                            <div class="agricart-category-form__picker agricart-product-form__picker-slot">
                                @if (filled($row['bank_name']))
                                    <span class="agricart-product-form__inline-chip">
                                        <span>{{ $row['bank_name'] }}</span>
                                        @unless ($readonly)
                                            <button type="button" wire:click="clearBank({{ $index }})" title="{{ __('agricart.users.actions.remove') }}">×</button>
                                        @endunless
                                    </span>
                                @else
                                    <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                                        <input
                                            type="search"
                                            wire:model.live.debounce.200ms="bankSearches.{{ $index }}"
                                            class="fi-input fi-input-has-inline-prefix"
                                            placeholder="{{ __('agricart.users.hints.bank_search') }}"
                                            @disabled($readonly)
                                        />
                                    </x-filament::input.wrapper>
                                    @if (filled(trim($bankSearches[$index] ?? '')) && ($filteredBanksByRow[$index] ?? []) !== [])
                                        <div class="agricart-category-form__picker-results" role="listbox">
                                            @foreach ($filteredBanksByRow[$index] as $bankName)
                                                <button
                                                    type="button"
                                                    class="agricart-category-form__picker-result"
                                                    wire:click="selectBank({{ $index }}, @js($bankName))"
                                                    wire:key="user-bank-option-{{ $index }}-{{ md5($bankName) }}"
                                                    role="option"
                                                    @disabled($readonly)
                                                >
                                                    {{ $bankName }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="agricart-form-panel__field">
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="bankRows.{{ $index }}.account_title" class="fi-input" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="bankRows.{{ $index }}.account_number" class="fi-input" placeholder="{{ __('agricart.users.hints.iban_or_account') }}" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-user-form__repeatable-actions">
                            @if ($index === count($bankRows) - 1)
                                <button type="button" class="agricart-product-form__icon-btn" wire:click="addBankRow()" title="{{ __('agricart.users.actions.add_bank') }}" @disabled($readonly)>+</button>
                            @endif
                            @if (count($bankRows) > 1)
                                <button type="button" class="agricart-product-form__icon-btn agricart-product-form__icon-btn--muted" wire:click="removeBankRow({{ $index }})" title="{{ __('agricart.users.actions.remove') }}" @disabled($readonly)>×</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.users.sections.documents') }}</h3>
        <div class="agricart-user-form__documents">
            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('agricart.users.fields.profile_photo') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($profilePhotoPreviewUrl)
                        <img src="{{ $profilePhotoPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('agricart.users.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('agricart.users.fields.cnic_front') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cnicFrontPreviewUrl)
                        <img src="{{ $cnicFrontPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('agricart.users.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cnicFrontUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('agricart.users.fields.cnic_back') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cnicBackPreviewUrl)
                        <img src="{{ $cnicBackPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('agricart.users.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cnicBackUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>
        </div>
    </section>
</div>
