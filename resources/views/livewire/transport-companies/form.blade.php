@php
    use Filament\Support\Icons\Heroicon;

    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-supplier-form agricart-user-form agricart-transport-form"
    x-on:transport-company-form-action.window="$wire.handlePanelAction($event.detail.action ?? $event.detail?.[0]?.action)"
    x-on:transport-company-form-open.window="$wire.openForm($event.detail.transportCompanyId ?? $event.detail?.[0]?.transportCompanyId ?? null, $event.detail.mode ?? $event.detail?.[0]?.mode ?? 'create')"
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
        <div class="agricart-supplier-form__meta-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('transport_companies.fields.code') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ $transport_code ?: __('transport_companies.hints.auto_on_save') }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-type">
                    {{ __('transport_companies.fields.type') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <select id="tc-type" wire:model="transport_type" class="fi-select-input" @disabled($readonly)>
                        <option value="">{{ __('transport_companies.hints.select_option') }}</option>
                        @foreach ($transportTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-status">{{ __('transport_companies.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="tc-status" wire:model="status" class="fi-select-input" @disabled($readonly)>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-credit">{{ __('transport_companies.fields.credit_limit') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-credit" type="number" step="0.01" wire:model="credit_limit" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-opening">{{ __('transport_companies.fields.opening_balance') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-opening" type="number" step="0.01" wire:model="opening_balance" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('transport_companies.hints.opening_balance') }}</p>
            </div>
        </div>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--en">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-business-en">
                    {{ __('transport_companies.fields.business_name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="tc-business-en" type="text" wire:model.live.debounce.300ms="business_name_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
                @error('business_name_en')
                    <p class="agricart-form-panel__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-address-en">{{ __('transport_companies.fields.address_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-address-en" type="text" wire:model.live.debounce.300ms="address_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-country">{{ __('transport_companies.fields.country') }}</label>
                <x-filament::input.wrapper>
                    <select id="tc-country" wire:model.live="country_code" class="fi-select-input" @disabled($readonly)>
                        @foreach ($countryOptionsEn as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            @if ($country_code === 'PK')
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="tc-city">{{ __('transport_companies.fields.city') }}</label>
                    <x-filament::input.wrapper>
                        <select id="tc-city" wire:model.live="city_key" class="fi-select-input" @disabled($readonly)>
                            <option value="">{{ __('transport_companies.hints.select_option') }}</option>
                            @foreach ($citiesByProvince as $province => $cities)
                                <optgroup label="{{ $province }}">
                                    @foreach ($cities as $key => $city)
                                        <option value="{{ $key }}">{{ $city['en'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
            @else
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="tc-city-manual">{{ __('transport_companies.fields.city') }}</label>
                    <x-filament::input.wrapper>
                        <input id="tc-city-manual" type="text" wire:model.live.debounce.300ms="city_manual" class="fi-input" placeholder="{{ __('transport_companies.hints.city_manual') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--ur">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-business-ur">{{ __('transport_companies.fields.business_name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-business-ur" type="text" wire:model.live.debounce.300ms="business_name_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-address-ur">{{ __('transport_companies.fields.address_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-address-ur" type="text" wire:model.live.debounce.300ms="address_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-country-ur">{{ __('transport_companies.fields.country_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-country-ur" type="text" value="{{ $country_ur }}" dir="rtl" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-city-ur">{{ __('transport_companies.fields.city_ur') }}</label>
                <x-filament::input.wrapper>
                    @if ($country_code === 'PK' && filled($city_key))
                        <input id="tc-city-ur" type="text" value="{{ $city_ur }}" dir="rtl" class="fi-input" readonly @disabled($readonly) />
                    @else
                        <input id="tc-city-ur" type="text" wire:model.live.debounce.300ms="city_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    @endif
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Other branches: one compact line — address EN/UR, contact EN/UR, phone, + person, Add branch --}}
        <p class="agricart-supplier-form__contacts-hint">
            {{ __('transport_companies.hints.branches') }}
        </p>

        @foreach ($branchRows as $branchIndex => $branch)
            @foreach (($branch['contacts'] ?? []) as $contactIndex => $contact)
                <div
                    class="agricart-company-profile-form__contact-row agricart-transport-form__branch-row"
                    wire:key="tc-branch-{{ $branchIndex }}-contact-{{ $contactIndex }}"
                >
                    @if ($contactIndex === 0)
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="branchRows.{{ $branchIndex }}.address_en"
                                class="fi-input"
                                placeholder="{{ __('transport_companies.fields.branch_address_en') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="branchRows.{{ $branchIndex }}.address_ur"
                                dir="rtl"
                                class="fi-input"
                                placeholder="{{ __('transport_companies.fields.branch_address_ur') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                    @else
                        <span class="agricart-transport-form__branch-spacer" aria-hidden="true"></span>
                        <span class="agricart-transport-form__branch-spacer" aria-hidden="true"></span>
                    @endif

                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="branchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.contact_name_en"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.contact_name_en') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="branchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.contact_name_ur"
                            dir="rtl"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.contact_name_ur') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="branchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.mobile_number"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.mobile_number') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>

                    @if ($contactIndex === count($branch['contacts'] ?? []) - 1)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn"
                            wire:click="addBranchContactRow({{ $branchIndex }})"
                            title="{{ __('transport_companies.actions.add_contact') }}"
                            @disabled($readonly)
                        >+</button>
                    @endif

                    @if (count($branch['contacts'] ?? []) > 1)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn is-remove"
                            wire:click="removeBranchContactRow({{ $branchIndex }}, {{ $contactIndex }})"
                            title="{{ __('transport_companies.actions.remove') }}"
                            @disabled($readonly)
                        >×</button>
                    @elseif (count($branchRows) > 1 && $contactIndex === 0)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn is-remove"
                            wire:click="removeBranchRow({{ $branchIndex }})"
                            title="{{ __('transport_companies.actions.remove_branch') }}"
                            @disabled($readonly)
                        >×</button>
                    @endif

                    @if (
                        $branchIndex === count($branchRows) - 1
                        && $contactIndex === count($branch['contacts'] ?? []) - 1
                    )
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn agricart-transport-form__branch-plus"
                            wire:click="addBranchRow"
                            title="{{ __('transport_companies.actions.add_branch') }}"
                            aria-label="{{ __('transport_companies.actions.add_branch') }}"
                            @disabled($readonly)
                        >+</button>
                    @endif
                </div>
            @endforeach
        @endforeach

        <h3 class="agricart-transport-form__section-title">
            {{ __('transport_companies.sections.destination') }}
        </h3>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--en">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-business-en">
                    {{ __('transport_companies.fields.destination_business_name_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="tc-dest-business-en" type="text" wire:model.live.debounce.300ms="destination_business_name_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-address-en">{{ __('transport_companies.fields.destination_address_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-dest-address-en" type="text" wire:model.live.debounce.300ms="destination_address_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-country">{{ __('transport_companies.fields.country') }}</label>
                <x-filament::input.wrapper>
                    <select id="tc-dest-country" wire:model.live="destination_country_code" class="fi-select-input" @disabled($readonly)>
                        @foreach ($countryOptionsEn as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            @if ($destination_country_code === 'PK')
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="tc-dest-city">{{ __('transport_companies.fields.city') }}</label>
                    <x-filament::input.wrapper>
                        <select id="tc-dest-city" wire:model.live="destination_city_key" class="fi-select-input" @disabled($readonly)>
                            <option value="">{{ __('transport_companies.hints.select_option') }}</option>
                            @foreach ($citiesByProvince as $province => $cities)
                                <optgroup label="{{ $province }}">
                                    @foreach ($cities as $key => $city)
                                        <option value="{{ $key }}">{{ $city['en'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
            @else
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="tc-dest-city-manual">{{ __('transport_companies.fields.city') }}</label>
                    <x-filament::input.wrapper>
                        <input id="tc-dest-city-manual" type="text" wire:model.live.debounce.300ms="destination_city_manual" class="fi-input" placeholder="{{ __('transport_companies.hints.city_manual') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>

        <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--ur">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-business-ur">{{ __('transport_companies.fields.destination_business_name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-dest-business-ur" type="text" wire:model.live.debounce.300ms="destination_business_name_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-address-ur">{{ __('transport_companies.fields.destination_address_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-dest-address-ur" type="text" wire:model.live.debounce.300ms="destination_address_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-country-ur">{{ __('transport_companies.fields.country_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tc-dest-country-ur" type="text" value="{{ $destination_country_ur }}" dir="rtl" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tc-dest-city-ur">{{ __('transport_companies.fields.city_ur') }}</label>
                <x-filament::input.wrapper>
                    @if ($destination_country_code === 'PK' && filled($destination_city_key))
                        <input id="tc-dest-city-ur" type="text" value="{{ $destination_city_ur }}" dir="rtl" class="fi-input" readonly @disabled($readonly) />
                    @else
                        <input id="tc-dest-city-ur" type="text" wire:model.live.debounce.300ms="destination_city_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    @endif
                </x-filament::input.wrapper>
            </div>
        </div>

        <p class="agricart-supplier-form__contacts-hint">
            {{ __('transport_companies.hints.destination_branches') }}
        </p>

        @foreach ($destinationBranchRows as $branchIndex => $branch)
            @foreach (($branch['contacts'] ?? []) as $contactIndex => $contact)
                <div
                    class="agricart-company-profile-form__contact-row agricart-transport-form__branch-row"
                    wire:key="tc-dest-branch-{{ $branchIndex }}-contact-{{ $contactIndex }}"
                >
                    @if ($contactIndex === 0)
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="destinationBranchRows.{{ $branchIndex }}.address_en"
                                class="fi-input"
                                placeholder="{{ __('transport_companies.fields.branch_address_en') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="destinationBranchRows.{{ $branchIndex }}.address_ur"
                                dir="rtl"
                                class="fi-input"
                                placeholder="{{ __('transport_companies.fields.branch_address_ur') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                    @else
                        <span class="agricart-transport-form__branch-spacer" aria-hidden="true"></span>
                        <span class="agricart-transport-form__branch-spacer" aria-hidden="true"></span>
                    @endif

                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="destinationBranchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.contact_name_en"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.contact_name_en') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="destinationBranchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.contact_name_ur"
                            dir="rtl"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.contact_name_ur') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="destinationBranchRows.{{ $branchIndex }}.contacts.{{ $contactIndex }}.mobile_number"
                            class="fi-input"
                            placeholder="{{ __('transport_companies.fields.mobile_number') }}"
                            @disabled($readonly)
                        />
                    </x-filament::input.wrapper>

                    @if ($contactIndex === count($branch['contacts'] ?? []) - 1)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn"
                            wire:click="addDestinationBranchContactRow({{ $branchIndex }})"
                            title="{{ __('transport_companies.actions.add_contact') }}"
                            @disabled($readonly)
                        >+</button>
                    @endif

                    @if (count($branch['contacts'] ?? []) > 1)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn is-remove"
                            wire:click="removeDestinationBranchContactRow({{ $branchIndex }}, {{ $contactIndex }})"
                            title="{{ __('transport_companies.actions.remove') }}"
                            @disabled($readonly)
                        >×</button>
                    @elseif (count($destinationBranchRows) > 1 && $contactIndex === 0)
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn is-remove"
                            wire:click="removeDestinationBranchRow({{ $branchIndex }})"
                            title="{{ __('transport_companies.actions.remove_branch') }}"
                            @disabled($readonly)
                        >×</button>
                    @endif

                    @if (
                        $branchIndex === count($destinationBranchRows) - 1
                        && $contactIndex === count($branch['contacts'] ?? []) - 1
                    )
                        <button
                            type="button"
                            class="agricart-company-profile-form__mini-btn agricart-transport-form__branch-plus"
                            wire:click="addDestinationBranchRow"
                            title="{{ __('transport_companies.actions.add_branch') }}"
                            aria-label="{{ __('transport_companies.actions.add_branch') }}"
                            @disabled($readonly)
                        >+</button>
                    @endif
                </div>
            @endforeach
        @endforeach

        <p class="agricart-supplier-form__contacts-hint">
            {{ __('transport_companies.hints.primary_contact_required') }}
        </p>

        @foreach ($contactRows as $index => $row)
            <div class="agricart-company-profile-form__contact-row agricart-supplier-form__contact-row" wire:key="tc-contact-{{ $index }}">
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live.debounce.300ms="contactRows.{{ $index }}.contact_name_en" class="fi-input" placeholder="{{ __('transport_companies.fields.contact_name_en') }}{{ $index === 0 ? ' *' : '' }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live.debounce.300ms="contactRows.{{ $index }}.contact_name_ur" dir="rtl" class="fi-input" placeholder="{{ __('transport_companies.fields.contact_name_ur') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live.debounce.300ms="contactRows.{{ $index }}.mobile_number" class="fi-input" placeholder="{{ __('transport_companies.fields.mobile_number') }}{{ $index === 0 ? ' *' : '' }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                @if ($index === count($contactRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addContactRow" title="{{ __('transport_companies.actions.add_contact') }}" @disabled($readonly)>+</button>
                @endif
                @if (count($contactRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removeContactRow({{ $index }})" title="{{ __('transport_companies.actions.remove') }}" @disabled($readonly)>×</button>
                @endif
            </div>
        @endforeach

        @foreach ($bankRows as $index => $row)
            <div class="agricart-company-profile-form__contact-row agricart-supplier-form__bank-row" wire:key="tc-bank-{{ $index }}">
                @if (filled($row['bank_key']))
                    <x-filament::input.wrapper class="agricart-supplier-form__bank-name-wrap">
                        <input type="text" value="{{ $row['bank_name_en'] }}" class="fi-input" readonly title="{{ __('transport_companies.fields.bank_name_en') }}" />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper class="agricart-supplier-form__bank-name-wrap">
                        <input type="text" value="{{ $row['bank_name_ur'] }}" dir="rtl" class="fi-input" readonly title="{{ __('transport_companies.fields.bank_name_ur') }}" />
                    </x-filament::input.wrapper>
                    @unless ($readonly)
                        <button type="button" class="agricart-company-profile-form__mini-btn is-remove agricart-supplier-form__bank-clear" wire:click="clearBank({{ $index }})" title="{{ __('transport_companies.actions.remove') }}">×</button>
                    @endunless
                @else
                    <div class="agricart-supplier-form__bank-picker agricart-supplier-form__bank-picker--search">
                        <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                            <input type="search" wire:model.live.debounce.200ms="bankSearches.{{ $index }}" class="fi-input fi-input-has-inline-prefix" placeholder="{{ __('transport_companies.hints.bank_search') }}" @disabled($readonly) />
                        </x-filament::input.wrapper>
                        @if (filled(trim($bankSearches[$index] ?? '')) && ($filteredBanksByRow[$index] ?? []) !== [])
                            <div class="agricart-category-form__picker-results" role="listbox">
                                @foreach ($filteredBanksByRow[$index] as $bankKey => $bankLabel)
                                    <button type="button" class="agricart-category-form__picker-result" wire:click="selectBank({{ $index }}, @js($bankKey))" wire:key="tc-bank-opt-{{ $index }}-{{ $bankKey }}" role="option" @disabled($readonly)>
                                        {{ $bankLabel }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_title" class="fi-input" placeholder="{{ __('transport_companies.fields.account_title') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_title_ur" dir="rtl" class="fi-input" placeholder="{{ __('transport_companies.fields.account_title_ur') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="bankRows.{{ $index }}.account_number" class="fi-input" placeholder="{{ __('transport_companies.hints.iban_or_account') }}" @disabled($readonly) />
                </x-filament::input.wrapper>
                @if ($index === count($bankRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addBankRow" title="{{ __('transport_companies.actions.add_bank') }}" @disabled($readonly)>+</button>
                @endif
                @if (count($bankRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removeBankRow({{ $index }})" title="{{ __('transport_companies.actions.remove') }}" @disabled($readonly)>×</button>
                @endif
            </div>
        @endforeach

        <div class="agricart-user-form__documents agricart-supplier-form__documents">
            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('transport_companies.fields.profile_photo') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($profilePhotoPreviewUrl)
                        <img src="{{ $profilePhotoPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('transport_companies.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('transport_companies.fields.card_front') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cardFrontPreviewUrl)
                        <img src="{{ $cardFrontPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('transport_companies.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cardFrontUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>

            <div class="agricart-user-form__document">
                <span class="agricart-form-panel__label">{{ __('transport_companies.fields.card_back') }}</span>
                <label class="agricart-user-form__document-upload">
                    @if ($cardBackPreviewUrl)
                        <img src="{{ $cardBackPreviewUrl }}" alt="" class="agricart-user-form__document-preview" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedIdentification" class="agricart-user-form__document-placeholder" />
                        <span>{{ __('transport_companies.hints.choose_file') }}</span>
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="cardBackUpload" accept="image/*" class="agricart-category-form__file-input" />
                    @endunless
                </label>
            </div>
        </div>
    </div>
</div>
