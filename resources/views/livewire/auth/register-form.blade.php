<div class="ag-store-register">
    @if ($submitted)
        <div class="ag-store-register__success">
            <h2>{{ __('agricart.auth.register_success_title') }}</h2>
            <p>{{ __('agricart.auth.register_success_text') }}</p>
            <a href="{{ url('/admin/login') }}" class="ag-store-register__btn">{{ __('agricart.auth.login_link') }}</a>
        </div>
    @else
        @if ($statusMessage)
            <div class="ag-store-register__alert is-danger">{{ $statusMessage }}</div>
        @endif

        <form wire:submit="submit" class="ag-store-register__form">
            <div class="ag-store-register__grid">
                <div class="ag-store-register__field">
                    <label for="register-name">{{ __('agricart.users.fields.name') }} *</label>
                    <input id="register-name" type="text" wire:model="name" class="ag-store-register__input" />
                    @error('name') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-register__field">
                    <label for="register-email">{{ __('agricart.users.fields.email') }} *</label>
                    <input id="register-email" type="email" wire:model="email" class="ag-store-register__input" />
                    @error('email') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-register__field">
                    <label for="register-address">{{ __('agricart.users.fields.address') }}</label>
                    <input id="register-address" type="text" wire:model="address" class="ag-store-register__input" />
                </div>

                <div class="ag-store-register__field">
                    <label for="register-password">{{ __('agricart.users.fields.password') }} *</label>
                    <input id="register-password" type="password" wire:model="password" class="ag-store-register__input" autocomplete="new-password" />
                    @error('password') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-register__field">
                    <label for="register-password-confirm">{{ __('agricart.users.fields.password_confirm') }} *</label>
                    <input id="register-password-confirm" type="password" wire:model="password_confirmation" class="ag-store-register__input" autocomplete="new-password" />
                </div>
            </div>

            <section class="ag-store-register__section">
                <h3>{{ __('agricart.users.sections.phones') }}</h3>
                @foreach ($phoneRows as $index => $row)
                    <div class="ag-store-register__row" wire:key="register-phone-{{ $index }}">
                        <input type="text" wire:model="phoneRows.{{ $index }}.contact_person" class="ag-store-register__input" placeholder="{{ __('agricart.users.hints.contact_person') }}" />
                        <input type="text" wire:model="phoneRows.{{ $index }}.phone_number" class="ag-store-register__input" placeholder="{{ __('agricart.users.hints.phone_number') }}" />
                        @if ($index === count($phoneRows) - 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="addPhoneRow()">+</button>
                        @endif
                        @if (count($phoneRows) > 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="removePhoneRow({{ $index }})">×</button>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="ag-store-register__section">
                <h3>{{ __('agricart.users.sections.bank_accounts') }}</h3>
                @foreach ($bankRows as $index => $row)
                    <div class="ag-store-register__bank" wire:key="register-bank-{{ $index }}">
                        @if (filled($row['bank_name']))
                            <span class="ag-store-register__chip">{{ $row['bank_name'] }} <button type="button" wire:click="clearBank({{ $index }})">×</button></span>
                        @else
                            <input type="search" wire:model.live.debounce.200ms="bankSearches.{{ $index }}" class="ag-store-register__input" placeholder="{{ __('agricart.users.hints.bank_search') }}" />
                            @if (filled(trim($bankSearches[$index] ?? '')) && ($filteredBanksByRow[$index] ?? []) !== [])
                                <div class="ag-store-register__bank-results">
                                    @foreach ($filteredBanksByRow[$index] as $bankName)
                                        <button type="button" wire:click="selectBank({{ $index }}, @js($bankName))">{{ $bankName }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                        <input type="text" wire:model="bankRows.{{ $index }}.account_title" class="ag-store-register__input" placeholder="{{ __('agricart.users.fields.account_title') }}" />
                        <input type="text" wire:model="bankRows.{{ $index }}.account_number" class="ag-store-register__input" placeholder="{{ __('agricart.users.hints.iban_or_account') }}" />
                        @if ($index === count($bankRows) - 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="addBankRow()">+</button>
                        @endif
                        @if (count($bankRows) > 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="removeBankRow({{ $index }})">×</button>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="ag-store-register__section">
                <h3>{{ __('agricart.users.sections.documents') }}</h3>
                <div class="ag-store-register__docs">
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.profile_photo') }}</span>
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" />
                    </label>
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.cnic_front') }}</span>
                        <input type="file" wire:model="cnicFrontUpload" accept="image/*" />
                    </label>
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.cnic_back') }}</span>
                        <input type="file" wire:model="cnicBackUpload" accept="image/*" />
                    </label>
                </div>
            </section>

            <div class="ag-store-register__actions">
                <button type="submit" class="ag-store-register__btn" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">{{ __('agricart.auth.register_submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('agricart.auth.register_saving') }}</span>
                </button>
                <a href="{{ url('/admin/login') }}" class="ag-store-register__link">{{ __('agricart.auth.login_link') }}</a>
            </div>
        </form>
    @endif
</div>
