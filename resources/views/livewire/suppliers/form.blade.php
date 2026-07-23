@php
    use Filament\Support\Icons\Heroicon;

    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-supplier-form agricart-user-form"
    x-on:supplier-form-action.window="$wire.handlePanelAction($event.detail.action ?? $event.detail?.[0]?.action)"
    x-on:supplier-form-open.window="$wire.openForm($event.detail.supplierId ?? $event.detail?.[0]?.supplierId ?? null, $event.detail.mode ?? $event.detail?.[0]?.mode ?? 'create')"
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

    <div class="agricart-supplier-form__stack">
        <div class="agricart-form-panel__field agricart-supplier-form__category-field">
            <label class="agricart-form-panel__label">{{ __('suppliers.fields.categories') }}</label>
            <div class="agricart-supplier-form__category-picker">
                <div class="agricart-category-form__picker">
                    <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                        <input
                            id="supplier-category-search"
                            type="search"
                            wire:model.live.debounce.300ms="category_search"
                            wire:key="supplier-category-search-input"
                            class="fi-input fi-input-has-inline-prefix"
                            placeholder="{{ __('suppliers.hints.category_search') }}"
                            autocomplete="off"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>

                    @if (mb_strlen(trim($category_search)) >= 2)
                        <div class="agricart-category-form__picker-results" role="listbox">
                            @forelse ($filteredCategoryOptions as $id => $label)
                                <button
                                    type="button"
                                    class="agricart-category-form__picker-result"
                                    wire:click="addCategory({{ $id }})"
                                    wire:key="supplier-cat-opt-{{ $id }}"
                                    role="option"
                                    @disabled($readonly)
                                >
                                    {{ $label }}
                                </button>
                            @empty
                                <div class="agricart-category-form__picker-empty">{{ __('suppliers.hints.no_categories') }}</div>
                            @endforelse
                        </div>
                    @endif
                </div>

                @if ($selectedCategories !== [])
                    <div class="agricart-supplier-form__category-chips">
                        @foreach ($selectedCategories as $id => $label)
                            <span class="agricart-product-form__inline-chip" wire:key="supplier-cat-{{ $id }}">
                                <span>{{ $label }}</span>
                                @unless ($readonly)
                                    <button type="button" wire:click="removeCategory({{ $id }})" title="{{ __('suppliers.actions.remove') }}">×</button>
                                @endunless
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="agricart-supplier-form__meta-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('suppliers.fields.supplier_code') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ $supplier_code ?: __('suppliers.hints.auto_on_save') }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-type">{{ __('suppliers.fields.supplier_type') }}</label>
                <x-filament::input.wrapper>
                    <select id="sp-type" wire:model="supplier_type" class="fi-select-input" @disabled($readonly)>
                        <option value="">{{ __('suppliers.hints.select_option') }}</option>
                        @foreach ($supplierTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-status">{{ __('suppliers.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="sp-status" wire:model.live="status" class="fi-select-input" @disabled($readonly)>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-credit">{{ __('suppliers.fields.credit_limit') }}</label>
                <x-filament::input.wrapper>
                    <input id="sp-credit" type="number" step="0.01" wire:model="credit_limit" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-opening">{{ __('suppliers.fields.opening_balance') }}</label>
                <x-filament::input.wrapper>
                    <input id="sp-opening" type="number" step="0.01" wire:model="opening_balance" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('suppliers.hints.opening_balance') }}</p>
            </div>
        </div>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--en">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-business-en">
                    {{ __('suppliers.fields.business_name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                    <x-filament::input.wrapper>
                        <input id="sp-business-en" type="text" wire:model.live.debounce.300ms="business_name_en" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    @error('business_name_en')
                        <p class="agricart-form-panel__error">{{ $message }}</p>
                    @enderror
                </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-country">
                    {{ __('suppliers.fields.country') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <select id="sp-country" wire:model.live="country_code" class="fi-select-input" @disabled($readonly)>
                        @foreach ($countryOptionsEn as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            @if ($country_code === 'PK')
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="sp-city">
                        {{ __('suppliers.fields.city') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <select id="sp-city" wire:model.live="city_key" class="fi-select-input" @disabled($readonly)>
                            <option value="">{{ __('suppliers.hints.select_option') }}</option>
                            @foreach ($citiesByProvince as $province => $cities)
                                <optgroup label="{{ $province }}">
                                    @foreach ($cities as $key => $city)
                                        <option value="{{ $key }}">{{ $city['en'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('city_key')
                        <p class="agricart-form-panel__error">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="sp-city-manual">
                        {{ __('suppliers.fields.city') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <input id="sp-city-manual" type="text" wire:model.live.debounce.300ms="city_manual" class="fi-input" placeholder="{{ __('suppliers.hints.city_manual') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-address-en">
                    {{ __('suppliers.fields.address_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                    <x-filament::input.wrapper>
                        <input id="sp-address-en" type="text" wire:model.live.debounce.300ms="address_en" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    @error('address_en')
                        <p class="agricart-form-panel__error">{{ $message }}</p>
                    @enderror
                </div>
        </div>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--ur">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-business-ur">{{ __('suppliers.fields.business_name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="sp-business-ur" type="text" wire:model.live.debounce.300ms="business_name_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-country-ur">{{ __('suppliers.fields.country_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="sp-country-ur" type="text" value="{{ $country_ur }}" dir="rtl" class="fi-input" readonly @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-city-ur">{{ __('suppliers.fields.city_ur') }}</label>
                <x-filament::input.wrapper>
                    @if ($country_code === 'PK' && filled($city_key))
                        <input id="sp-city-ur" type="text" value="{{ $city_ur }}" dir="rtl" class="fi-input" readonly @disabled($readonly) />
                    @else
                        <input id="sp-city-ur" type="text" wire:model.live.debounce.300ms="city_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    @endif
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="sp-address-ur">{{ __('suppliers.fields.address_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="sp-address-ur" type="text" wire:model.live.debounce.300ms="address_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
        </div>

        <p class="agricart-supplier-form__contacts-hint">
            {{ __('suppliers.hints.primary_contact_required') }}
        </p>

        @foreach ($contactRows as $index => $row)
            <div class="agricart-company-profile-form__contact-row agricart-supplier-form__contact-row" wire:key="supplier-contact-{{ $index }}">
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="contactRows.{{ $index }}.contact_name_en"
                        class="fi-input"
                        placeholder="{{ __('suppliers.fields.contact_name_en') }}{{ $index === 0 ? ' *' : '' }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="contactRows.{{ $index }}.contact_name_ur"
                        dir="rtl"
                        class="fi-input"
                        placeholder="{{ __('suppliers.fields.contact_name_ur') }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="contactRows.{{ $index }}.mobile_number"
                        class="fi-input"
                        placeholder="{{ __('suppliers.fields.mobile_number') }}{{ $index === 0 ? ' *' : '' }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>
                @if ($index === count($contactRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addContactRow" title="{{ __('suppliers.actions.add_contact') }}" @disabled($readonly)>+</button>
                @endif
                @if (count($contactRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removeContactRow({{ $index }})" title="{{ __('suppliers.actions.remove') }}" @disabled($readonly)>×</button>
                @endif
            </div>
            @error('contactRows.'.$index.'.contact_name_en')
                <p class="agricart-form-panel__error">{{ $message }}</p>
            @enderror
            @error('contactRows.'.$index.'.mobile_number')
                <p class="agricart-form-panel__error">{{ $message }}</p>
            @enderror
        @endforeach

        @foreach ($bankRows as $index => $row)
            <div class="agricart-company-profile-form__contact-row agricart-supplier-form__bank-row" wire:key="supplier-bank-{{ $index }}">
                @if (filled($row['bank_key']))
                    <x-filament::input.wrapper class="agricart-supplier-form__bank-name-wrap">
                        <input type="text" value="{{ $row['bank_name_en'] }}" class="fi-input" readonly title="{{ __('suppliers.fields.bank_name_en') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper class="agricart-supplier-form__bank-name-wrap">
                        <input type="text" value="{{ $row['bank_name_ur'] }}" dir="rtl" class="fi-input" readonly title="{{ __('suppliers.fields.bank_name_ur') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    @unless ($readonly)
                        <button type="button" class="agricart-company-profile-form__mini-btn is-remove agricart-supplier-form__bank-clear" wire:click="clearBank({{ $index }})" title="{{ __('suppliers.actions.remove') }}">×</button>
                    @endunless
                @else
                    <div class="agricart-supplier-form__bank-picker agricart-supplier-form__bank-picker--search">
                        <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                            <input
                                type="search"
                                wire:model.live.debounce.200ms="bankSearches.{{ $index }}"
                                class="fi-input fi-input-has-inline-prefix"
                                placeholder="{{ __('suppliers.hints.bank_search') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                        @if (filled(trim($bankSearches[$index] ?? '')) && ($filteredBanksByRow[$index] ?? []) !== [])
                            <div class="agricart-category-form__picker-results" role="listbox">
                                @foreach ($filteredBanksByRow[$index] as $bankKey => $bankLabel)
                                    <button
                                        type="button"
                                        class="agricart-category-form__picker-result"
                                        wire:click="selectBank({{ $index }}, @js($bankKey))"
                                        wire:key="supplier-bank-opt-{{ $index }}-{{ $bankKey }}"
                                        role="option"
                                        @disabled($readonly)
                                    >
                                        {{ $bankLabel }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_title" class="fi-input" placeholder="{{ __('suppliers.fields.account_title') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_title_ur" dir="rtl" class="fi-input" placeholder="{{ __('suppliers.fields.account_title_ur') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_number" class="fi-input" placeholder="{{ __('suppliers.hints.iban_or_account') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                @if ($index === count($bankRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addBankRow" title="{{ __('suppliers.actions.add_bank') }}" @disabled($readonly)>+</button>
                @endif
                @if (count($bankRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removeBankRow({{ $index }})" title="{{ __('suppliers.actions.remove') }}" @disabled($readonly)>×</button>
                @endif
            </div>
        @endforeach

        <section class="agricart-customer-form__section agricart-supplier-form__portal">
            <h2 class="agricart-customer-form__section-title">{{ __('suppliers.sections.portal') }}</h2>
            <p class="agricart-form-panel__hint">{{ __('suppliers.hints.portal_later') }}</p>
            <label class="agricart-supplier-form__portal-toggle">
                <input type="checkbox" wire:model.live="portal_enabled" @disabled($readonly) />
                <span>{{ __('suppliers.fields.portal_enabled') }}</span>
            </label>
        </section>

        @if ($portal_enabled)
            <div class="agricart-supplier-form__portal-grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="sp-email">{{ __('suppliers.fields.email') }}</label>
                    <x-filament::input.wrapper>
                        <input id="sp-email" type="email" wire:model="email" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>

                @if ($mode === 'create')
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label" for="sp-password">{{ __('suppliers.fields.password') }}</label>
                        <x-agricart.password-field id="sp-password" wire:model="password" input-class="fi-input" autocomplete="new-password" :disabled="$readonly" />
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label" for="sp-password-confirm">{{ __('suppliers.fields.password_confirm') }}</label>
                        <x-agricart.password-field id="sp-password-confirm" wire:model="password_confirmation" input-class="fi-input" autocomplete="new-password" :disabled="$readonly" />
                    </div>
                @endif
            </div>
        @endif

        <div class="agricart-user-form__documents agricart-supplier-form__documents">
            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('suppliers.fields.profile_photo') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($profilePhotoPreviewUrl)
                        <img src="{{ $profilePhotoPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('suppliers.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('suppliers.fields.card_front') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cardFrontPreviewUrl)
                        <img src="{{ $cardFrontPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('suppliers.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cardFrontUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('suppliers.fields.card_back') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cardBackPreviewUrl)
                        <img src="{{ $cardBackPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('suppliers.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cardBackUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>
        </div>
    </div>

    @if ($supplierId)
        <x-communication.record-panels
            linkable-type="supplier"
            :linkable-id="$supplierId"
            thread-profile="supplier"
        />
    @endif
</div>
