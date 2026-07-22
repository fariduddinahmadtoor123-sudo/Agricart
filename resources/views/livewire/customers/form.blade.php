@php
    $readonly = $this->isReadonly();
    $online = $this->isOnline();
@endphp

<div
    class="agricart-customer-form agricart-supplier-form agricart-user-form"
    x-on:customer-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:customer-form-open.window="$wire.openForm($event.detail.customerId ?? null, $event.detail.mode ?? 'create')"
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
        @if ($online)
            <div class="agricart-customer-form__channel-badge">{{ __('customers.channels.online') }}</div>

            <section class="agricart-customer-form__section">
                <h2 class="agricart-customer-form__section-title">{{ __('customers.sections.online_account') }}</h2>
                <div class="agricart-supplier-form__meta-grid">
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.first_name') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $first_name }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.last_name') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $last_name }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.email') }}</label>
                        <x-filament::input.wrapper>
                            <input type="email" value="{{ $email }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.phone') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $phoneRows[0]['mobile_number'] ?? '' }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.address_line1') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $address_line1 }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    @if (filled($address_line2))
                        <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                            <label class="agricart-form-panel__label">{{ __('customers.fields.address_line2') }}</label>
                            <x-filament::input.wrapper>
                                <input type="text" value="{{ $address_line2 }}" class="fi-input" readonly />
                            </x-filament::input.wrapper>
                        </div>
                    @endif
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.city') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $city }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.state') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $state }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.postal_code') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $postal_code }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('customers.fields.country') }}</label>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $country }}" class="fi-input" readonly />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </section>
        @else
            <div class="agricart-customer-form__quick">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label">{{ __('customers.fields.customer_number') }}</label>
                    <x-filament::input.wrapper>
                        <input type="text" value="{{ $customer_number ?: __('customers.hints.auto_on_save') }}" class="fi-input" readonly />
                    </x-filament::input.wrapper>
                </div>

                <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                    <label class="agricart-form-panel__label" for="cu-display-name">
                        {{ __('customers.fields.display_name') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <input id="cu-display-name" type="text" wire:model="display_name" class="fi-input" placeholder="{{ __('customers.hints.display_name') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>

                <div class="agricart-customer-form__phones">
                    <label class="agricart-form-panel__label">{{ __('customers.fields.phone') }} <span class="agricart-form-panel__required">*</span></label>
                    @foreach ($phoneRows as $index => $row)
                        <div class="agricart-customer-form__phone-row" wire:key="customer-phone-{{ $index }}">
                            <x-filament::input.wrapper>
                                <input
                                    type="text"
                                    wire:model="phoneRows.{{ $index }}.mobile_number"
                                    class="fi-input"
                                    placeholder="{{ __('customers.hints.phone') }}"
                                    @disabled($readonly)
                                />
                            </x-filament::input.wrapper>
                            @if ($index === count($phoneRows) - 1)
                                <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addPhoneRow" title="{{ __('customers.actions.add_phone') }}" @disabled($readonly)>+</button>
                            @endif
                            @if (count($phoneRows) > 1)
                                <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removePhoneRow({{ $index }})" title="{{ __('customers.actions.remove') }}" @disabled($readonly)>×</button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                    <label class="agricart-form-panel__label" for="cu-address-quick">{{ __('customers.fields.address_quick') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-address-quick" type="text" wire:model="address_quick" class="fi-input" placeholder="{{ __('customers.hints.address_quick') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            </div>
        @endif

        <section class="agricart-customer-form__section">
            <h2 class="agricart-customer-form__section-title">{{ __('customers.sections.account') }}</h2>
            <div class="agricart-supplier-form__meta-grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-opening">{{ __('customers.fields.opening_balance') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-opening" type="number" step="0.01" wire:model="opening_balance" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    <p class="agricart-form-panel__hint">{{ __('customers.hints.opening_balance') }}</p>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-credit">{{ __('customers.fields.credit_limit') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-credit" type="number" step="0.01" wire:model="credit_limit" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                @unless ($online)
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label" for="cu-email">{{ __('customers.fields.email') }}</label>
                        <x-filament::input.wrapper>
                            <input id="cu-email" type="email" wire:model="email" class="fi-input" placeholder="{{ __('customers.hints.email_optional') }}" @disabled($readonly) />
                        </x-filament::input.wrapper>
                    </div>
                @endunless
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label flex items-center gap-2" for="cu-active">
                        <input id="cu-active" type="checkbox" wire:model="is_active" @disabled($readonly) />
                        <span>{{ __('customers.statuses.active') }}</span>
                    </label>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label flex items-center gap-2" for="cu-marketing">
                        <input id="cu-marketing" type="checkbox" wire:model="accepts_marketing" @disabled($readonly) />
                        <span>{{ __('customers.fields.accepts_marketing') }}</span>
                    </label>
                </div>
            </div>
        </section>

        <section class="agricart-customer-form__section">
            <h2 class="agricart-customer-form__section-title">{{ __('customers.sections.documents') }}</h2>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('customers.fields.profile_photo') }}</label>
                <div class="agricart-supplier-form__upload-row">
                    @if ($profilePhotoPreviewUrl)
                        <img src="{{ $profilePhotoPreviewUrl }}" alt="" class="agricart-supplier-form__photo-preview" />
                    @endif
                    @unless ($readonly)
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" />
                    @endunless
                </div>
            </div>
        </section>

        <section class="agricart-customer-form__section">
            <h2 class="agricart-customer-form__section-title">{{ __('customers.sections.bilingual') }}</h2>
            <p class="agricart-customer-form__hint">{{ __('customers.hints.bilingual_ai') }}</p>

            <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--en">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-name-en">{{ __('customers.fields.name_en') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-city-key">{{ __('customers.fields.city') }}</label>
                    <x-filament::input.wrapper>
                        <select id="cu-city-key" wire:model.live="city_key" class="fi-select-input" @disabled($readonly)>
                            <option value="">{{ __('customers.hints.select_option') }}</option>
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
                <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                    <label class="agricart-form-panel__label" for="cu-address-en">{{ __('customers.fields.address_en') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-address-en" type="text" wire:model="address_en" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div class="agricart-supplier-form__locale-row agricart-supplier-form__locale-row--ur">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-name-ur">{{ __('customers.fields.name_ur') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cu-city-ur">{{ __('customers.fields.city_ur') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-city-ur" type="text" wire:model="city_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field agricart-customer-form__field--wide">
                    <label class="agricart-form-panel__label" for="cu-address-ur">{{ __('customers.fields.address_ur') }}</label>
                    <x-filament::input.wrapper>
                        <input id="cu-address-ur" type="text" wire:model="address_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </section>
    </div>
</div>
