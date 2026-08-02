<div
    class="ag-store-register"
    x-data
    x-on:staff-registration-result.window="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
>
    @if ($submitted)
        <div class="ag-store-register__success" role="status" aria-live="polite">
            <span class="ag-store-register__success-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <div class="ag-store-register__success-content">
                <span class="ag-store-register__success-eyebrow">{{ __('agricart.auth.register_success_kicker') }}</span>
                <h2>{{ __('agricart.auth.register_success_title') }}</h2>
                <p>{{ $isResubmit ? __('agricart.auth.register_resubmit_success_text') : __('agricart.auth.register_success_text') }}</p>
                <dl class="ag-store-register__success-meta">
                    <div>
                        <dt>{{ __('agricart.auth.register_application_status') }}</dt>
                        <dd>{{ __('agricart.auth.register_pending_approval') }}</dd>
                    </div>
                    @if ($submittedReference)
                        <div>
                            <dt>{{ __('agricart.auth.register_application_reference') }}</dt>
                            <dd>{{ $submittedReference }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="ag-store-register__success-note">{{ __('agricart.auth.register_success_note') }}</p>
                <a href="{{ url('/admin/login') }}" class="ag-store-register__btn">{{ __('agricart.auth.login_link') }}</a>
            </div>
        </div>
    @else
        <p class="ag-store-register__required-note">
            <span class="ag-store-register__required" aria-hidden="true">*</span>
            {{ __('agricart.auth.register_required_note') }}
        </p>

        @if ($statusMessage)
            <div class="ag-store-register__alert is-danger" role="alert">{{ $statusMessage }}</div>
        @endif

        <form wire:submit="submit" class="ag-store-register__form">
            <input type="text" wire:model="website" class="ag-store-register__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">01</span>
                        <h3>{{ __('agricart.auth.register_identity_title') }}</h3>
                    </div>
                    <p>{{ __('agricart.auth.register_identity_help') }}</p>
                </header>

                <div class="ag-store-register__grid">
                    <div class="ag-store-register__field">
                        <label for="register-name">
                            {{ __('agricart.users.fields.name') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <input id="register-name" type="text" wire:model="name" class="ag-store-register__input" autocomplete="name" required />
                        @error('name') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="register-name-ur">
                            {{ __('agricart.users.fields.name_ur') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <input id="register-name-ur" type="text" wire:model="name_ur" class="ag-store-register__input" dir="rtl" required />
                        @error('name_ur') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="register-address">
                            {{ __('agricart.users.fields.address') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <textarea id="register-address" wire:model="address" class="ag-store-register__input ag-store-register__textarea" rows="2" autocomplete="street-address" required></textarea>
                        @error('address') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="register-address-ur">
                            {{ __('agricart.users.fields.address_ur') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <textarea id="register-address-ur" wire:model="address_ur" class="ag-store-register__input ag-store-register__textarea" rows="2" dir="rtl" required></textarea>
                        @error('address_ur') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field ag-store-register__field--wide">
                        <label for="register-email">
                            {{ __('agricart.users.fields.email') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <input id="register-email" type="email" wire:model="email" class="ag-store-register__input" autocomplete="email" required />
                        @error('email') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">02</span>
                        <h3>{{ __('agricart.users.sections.phones') }}</h3>
                    </div>
                    <p>{{ __('agricart.auth.register_phone_help') }}</p>
                </header>

                @foreach ($phoneRows as $index => $row)
                    <div class="ag-store-register__row" wire:key="register-phone-{{ $index }}">
                        <div class="ag-store-register__field">
                            <label for="register-contact-{{ $index }}">
                                {{ __('agricart.users.hints.contact_person') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input id="register-contact-{{ $index }}" type="text" wire:model="phoneRows.{{ $index }}.contact_person" class="ag-store-register__input" required />
                            @error("phoneRows.{$index}.contact_person") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label for="register-contact-ur-{{ $index }}">
                                {{ __('agricart.users.hints.contact_person_ur') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input id="register-contact-ur-{{ $index }}" type="text" wire:model="phoneRows.{{ $index }}.contact_person_ur" class="ag-store-register__input" dir="rtl" required />
                            @error("phoneRows.{$index}.contact_person_ur") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label for="register-phone-{{ $index }}">
                                {{ __('agricart.users.hints.phone_number') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input id="register-phone-{{ $index }}" type="tel" inputmode="tel" wire:model="phoneRows.{{ $index }}.phone_number" class="ag-store-register__input" required />
                            @error("phoneRows.{$index}.phone_number") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        @if ($index === count($phoneRows) - 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="addPhoneRow()" aria-label="{{ __('agricart.users.actions.add_phone') }}">+</button>
                        @endif
                        @if (count($phoneRows) > 1)
                            <button type="button" class="ag-store-register__mini-btn is-danger" wire:click="removePhoneRow({{ $index }})" aria-label="{{ __('agricart.users.actions.remove') }}">×</button>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">03</span>
                        <h3>{{ __('agricart.users.sections.bank_accounts') }}</h3>
                    </div>
                    <p>{{ __('agricart.auth.register_bank_help') }}</p>
                </header>

                @foreach ($bankRows as $index => $row)
                    <div class="ag-store-register__bank" wire:key="register-bank-{{ $index }}">
                        <div class="ag-store-register__field">
                            <label>
                                {{ __('agricart.users.fields.bank_name') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            @if (filled($row['bank_name']))
                                <span class="ag-store-register__chip">{{ $row['bank_name'] }} <button type="button" wire:click="clearBank({{ $index }})">×</button></span>
                            @else
                                <input
                                    type="search"
                                    wire:model.live.debounce.200ms="bankSearches.{{ $index }}"
                                    wire:focus="openBankPicker({{ $index }})"
                                    wire:click="openBankPicker({{ $index }})"
                                    class="ag-store-register__input"
                                    placeholder="{{ __('agricart.users.hints.bank_search') }}"
                                    autocomplete="off"
                                    role="combobox"
                                    aria-expanded="{{ $openBankPickerIndex === $index ? 'true' : 'false' }}"
                                    required
                                />
                                @if ($openBankPickerIndex === $index && ($filteredBanksByRow[$index] ?? []) !== [])
                                    <div class="ag-store-register__bank-results" role="listbox">
                                        @foreach ($filteredBanksByRow[$index] as $bankName => $bankLabel)
                                            <button
                                                type="button"
                                                wire:click="selectBank({{ $index }}, @js($bankName))"
                                                wire:key="public-staff-bank-{{ $index }}-{{ md5($bankName) }}"
                                                role="option"
                                            >
                                                {{ $bankLabel }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                                <span class="ag-store-register__field-help">{{ __('agricart.auth.register_bank_select_help') }}</span>
                            @endif
                            @error("bankRows.{$index}.bank_name") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label>
                                {{ __('agricart.users.fields.account_title_en') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" wire:model="bankRows.{{ $index }}.account_title" class="ag-store-register__input" required />
                            @error("bankRows.{$index}.account_title") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label>
                                {{ __('agricart.users.fields.account_title_ur') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" wire:model="bankRows.{{ $index }}.account_title_ur" class="ag-store-register__input" dir="rtl" required />
                            @error("bankRows.{$index}.account_title_ur") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label>
                                {{ __('agricart.users.fields.account_number') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" wire:model="bankRows.{{ $index }}.account_number" class="ag-store-register__input" required />
                            @error("bankRows.{$index}.account_number") <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        @if ($index === count($bankRows) - 1)
                            <button type="button" class="ag-store-register__mini-btn" wire:click="addBankRow()" aria-label="{{ __('agricart.users.actions.add_bank') }}">+</button>
                        @endif
                        @if (count($bankRows) > 1)
                            <button type="button" class="ag-store-register__mini-btn is-danger" wire:click="removeBankRow({{ $index }})" aria-label="{{ __('agricart.users.actions.remove') }}">×</button>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">04</span>
                        <h3>{{ __('agricart.users.sections.documents') }}</h3>
                    </div>
                    <p>{{ __('agricart.auth.register_documents_help') }}</p>
                </header>

                <div class="ag-store-register__docs">
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.profile_photo') }} <span class="ag-store-register__required" aria-hidden="true">*</span></span>
                        @if ($profilePhotoPreviewUrl)<img src="{{ $profilePhotoPreviewUrl }}" alt="" class="ag-store-register__preview" />@endif
                        <input type="file" wire:model="profilePhotoUpload" accept="image/*" required />
                        @error('profilePhotoUpload') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </label>
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.cnic_front') }} <span class="ag-store-register__required" aria-hidden="true">*</span></span>
                        @if ($cnicFrontPreviewUrl)<img src="{{ $cnicFrontPreviewUrl }}" alt="" class="ag-store-register__preview" />@endif
                        <input type="file" wire:model="cnicFrontUpload" accept="image/*" required />
                        @error('cnicFrontUpload') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </label>
                    <label class="ag-store-register__upload">
                        <span>{{ __('agricart.users.fields.cnic_back') }} <span class="ag-store-register__required" aria-hidden="true">*</span></span>
                        @if ($cnicBackPreviewUrl)<img src="{{ $cnicBackPreviewUrl }}" alt="" class="ag-store-register__preview" />@endif
                        <input type="file" wire:model="cnicBackUpload" accept="image/*" required />
                        @error('cnicBackUpload') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">05</span>
                        <h3>{{ __('agricart.auth.register_security_title') }}</h3>
                    </div>
                    <p>{{ __('agricart.auth.register_security_help') }}</p>
                </header>

                <div class="ag-store-register__grid">
                    <div class="ag-store-register__field">
                        <x-agricart.password-field
                            id="register-password"
                            wire:model="password"
                            input-class="ag-store-register__input"
                            autocomplete="new-password"
                            :label="__('agricart.users.fields.password') . ' <span class=&quot;ag-store-register__required&quot; aria-hidden=&quot;true&quot;>*</span>'"
                            required
                        />
                        @error('password') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <x-agricart.password-field
                            id="register-password-confirm"
                            wire:model="password_confirmation"
                            input-class="ag-store-register__input"
                            autocomplete="new-password"
                            :label="__('agricart.users.fields.password_confirm') . ' <span class=&quot;ag-store-register__required&quot; aria-hidden=&quot;true&quot;>*</span>'"
                            required
                        />
                        @error('password_confirmation') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            @if ($requireNoticeAcceptance)
                <section class="ag-store-register__section">
                    <div class="ag-store-register__terms-box">
                        <label class="ag-store-register__checkbox">
                            <input type="checkbox" wire:model="terms_accepted" />
                            <span>{{ __('agricart.auth.terms_accepted') }} <span class="ag-store-register__required" aria-hidden="true">*</span></span>
                        </label>
                        @error('terms_accepted') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>
                </section>
            @endif

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
