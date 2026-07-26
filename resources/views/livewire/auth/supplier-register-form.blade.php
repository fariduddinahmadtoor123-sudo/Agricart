<div
    class="ag-store-register"
    x-data
    x-on:supplier-registration-result.window="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
>
    @if ($submitted)
        <div class="ag-store-register__success" role="status" aria-live="polite">
            <span class="ag-store-register__success-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <div class="ag-store-register__success-content">
                <span class="ag-store-register__success-eyebrow">{{ __('agricart.supplier_register.success_kicker') }}</span>
                <h2>{{ __('agricart.supplier_register.success_title') }}</h2>
                <p>{{ $isResubmit ? __('agricart.supplier_register.resubmit_success_text') : __('agricart.supplier_register.success_text') }}</p>
                <dl class="ag-store-register__success-meta">
                    <div>
                        <dt>{{ __('agricart.supplier_register.application_status') }}</dt>
                        <dd>{{ __('agricart.supplier_register.pending_approval') }}</dd>
                    </div>
                    @if ($submittedReference)
                        <div>
                            <dt>{{ __('agricart.supplier_register.application_reference') }}</dt>
                            <dd>{{ $submittedReference }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="ag-store-register__success-note">{{ __('agricart.supplier_register.success_note') }}</p>
                <a href="{{ route('store.home') }}" class="ag-store-register__btn">{{ __('agricart.supplier_register.back_home') }}</a>
            </div>
        </div>
    @else
        <p class="ag-store-register__required-note">
            <span class="ag-store-register__required" aria-hidden="true">*</span>
            {{ __('agricart.supplier_register.required_note') }}
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
                        <h3>{{ __('agricart.supplier_register.sections.business') }}</h3>
                    </div>
                    <p>{{ __('agricart.supplier_register.sections.business_help') }}</p>
                </header>

                <div class="ag-store-register__grid">
                    <div class="ag-store-register__field">
                        <label for="supplier-business-en">
                            {{ __('agricart.supplier_register.fields.business_name_en') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <input id="supplier-business-en" type="text" wire:model="business_name_en" class="ag-store-register__input" required />
                        @error('business_name_en') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="supplier-business-ur">{{ __('agricart.supplier_register.fields.business_name_ur') }}</label>
                        <input id="supplier-business-ur" type="text" wire:model="business_name_ur" class="ag-store-register__input" dir="rtl" />
                        @error('business_name_ur') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="supplier-type">
                            {{ __('agricart.supplier_register.fields.supplier_type') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <select id="supplier-type" wire:model="supplier_type" class="ag-store-register__input" required>
                            <option value="">{{ __('agricart.supplier_register.fields.supplier_type_placeholder') }}</option>
                            @foreach ($supplierTypes as $key => $labels)
                                <option value="{{ $key }}">{{ $labels[app()->getLocale() === 'ur' ? 'ur' : 'en'] ?? $labels['en'] }}</option>
                            @endforeach
                        </select>
                        @error('supplier_type') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field">
                        <label for="supplier-city">
                            {{ __('agricart.supplier_register.fields.city') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <input id="supplier-city" type="text" wire:model="city_en" class="ag-store-register__input" required />
                        @error('city_en') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ag-store-register__field ag-store-register__field--full">
                        <label for="supplier-address">
                            {{ __('agricart.supplier_register.fields.address') }}
                            <span class="ag-store-register__required" aria-hidden="true">*</span>
                        </label>
                        <textarea id="supplier-address" wire:model="address_en" class="ag-store-register__input ag-store-register__textarea" rows="2" required></textarea>
                        @error('address_en') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">02</span>
                        <h3>{{ __('agricart.supplier_register.sections.people') }}</h3>
                    </div>
                    <p>{{ __('agricart.supplier_register.sections.people_help') }}</p>
                </header>

                @foreach ($contactRows as $index => $row)
                    <div class="ag-store-register__grid" wire:key="supplier-contact-{{ $index }}">
                        <div class="ag-store-register__field">
                            <label for="supplier-contact-name-{{ $index }}">
                                {{ __('agricart.supplier_register.fields.contact_name') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input id="supplier-contact-name-{{ $index }}" type="text" wire:model="contactRows.{{ $index }}.contact_name_en" class="ag-store-register__input" required />
                            @error('contacts.'.$index.'.contact_name_en') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                            @error('contactRows.'.$index.'.contact_name_en') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ag-store-register__field">
                            <label for="supplier-contact-phone-{{ $index }}">
                                {{ __('agricart.supplier_register.fields.contact_phone') }}
                                <span class="ag-store-register__required" aria-hidden="true">*</span>
                            </label>
                            <input id="supplier-contact-phone-{{ $index }}" type="tel" wire:model="contactRows.{{ $index }}.mobile_number" class="ag-store-register__input" required />
                            @error('contacts.'.$index.'.mobile_number') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                            @error('contactRows.'.$index.'.mobile_number') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                        </div>
                        @if (count($contactRows) > 1)
                            <div class="ag-store-register__field">
                                <label>&nbsp;</label>
                                <button type="button" class="ag-store-register__btn ag-store-register__btn--ghost" wire:click="removeContactRow({{ $index }})">
                                    {{ __('agricart.supplier_register.remove_person') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach

                <button type="button" class="ag-store-register__btn ag-store-register__btn--ghost" wire:click="addContactRow">
                    {{ __('agricart.supplier_register.add_person') }}
                </button>
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">03</span>
                        <h3>{{ __('agricart.supplier_register.sections.categories') }}</h3>
                    </div>
                    <p>{{ __('agricart.supplier_register.sections.categories_help') }}</p>
                </header>

                <div class="ag-store-register__field">
                    <label for="supplier-category-search">{{ __('agricart.supplier_register.fields.category_search') }}</label>
                    <input id="supplier-category-search" type="search" wire:model.live.debounce.300ms="category_search" class="ag-store-register__input" placeholder="{{ __('agricart.supplier_register.fields.category_search_placeholder') }}" />
                    @if ($categoryOptions !== [])
                        <ul class="ag-store-register__picker-list" role="listbox">
                            @foreach ($categoryOptions as $id => $label)
                                <li>
                                    <button type="button" class="ag-store-register__picker-item" wire:click="addCategory({{ $id }})">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($selectedCategories !== [])
                        <div class="ag-store-register__chips">
                            @foreach ($selectedCategories as $id => $label)
                                <button type="button" class="ag-store-register__chip" wire:click="removeCategory({{ $id }})">
                                    {{ $label }} ×
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="ag-store-register__field">
                    <label for="supplier-custom-categories">{{ __('agricart.supplier_register.fields.custom_categories') }}</label>
                    <textarea id="supplier-custom-categories" wire:model="custom_categories" class="ag-store-register__input ag-store-register__textarea" rows="2" placeholder="{{ __('agricart.supplier_register.fields.custom_categories_placeholder') }}"></textarea>
                    @error('custom_categories') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>

                <div class="ag-store-register__field">
                    <label for="supplier-note">{{ __('agricart.supplier_register.fields.application_note') }}</label>
                    <textarea id="supplier-note" wire:model="application_note" class="ag-store-register__input ag-store-register__textarea" rows="3" placeholder="{{ __('agricart.supplier_register.fields.application_note_placeholder') }}"></textarea>
                    @error('application_note') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>
            </section>

            <section class="ag-store-register__section">
                <header class="ag-store-register__section-head">
                    <div>
                        <span class="ag-store-register__section-number">04</span>
                        <h3>{{ __('agricart.supplier_register.sections.terms') }}</h3>
                    </div>
                    <p>{{ __('agricart.supplier_register.sections.terms_help') }}</p>
                </header>

                <div class="ag-store-register__terms-box">
                    @if ($termsUrl)
                        <p>
                            <a href="{{ $termsUrl }}" target="_blank" rel="noopener noreferrer" class="ag-store-register__terms-link">
                                {{ __('agricart.supplier_register.terms_link') }}
                            </a>
                        </p>
                    @else
                        <p>{{ __('agricart.supplier_register.terms_fallback') }}</p>
                    @endif

                    <label class="ag-store-register__checkbox">
                        <input type="checkbox" wire:model="terms_accepted" />
                        <span>{{ __('agricart.supplier_register.fields.terms_accepted') }} <span class="ag-store-register__required" aria-hidden="true">*</span></span>
                    </label>
                    @error('terms_accepted') <span class="ag-store-register__error">{{ $message }}</span> @enderror
                </div>
            </section>

            <div class="ag-store-register__actions">
                <button type="submit" class="ag-store-register__btn" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">{{ __('agricart.supplier_register.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('agricart.supplier_register.submitting') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
