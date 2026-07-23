@php use Filament\Support\Icons\Heroicon; @endphp

<div
    class="agricart-brand-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('brand-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('brand-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:brand-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:brand-form-open.window="$wire.openForm($event.detail.brandId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'agricart-category-form__alert--prominent' => $statusType === 'success' && $ai_status === 'enriched',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-brand-form__hero agricart-category-form__hero">
        <div class="agricart-brand-form__image-col">
            <label class="agricart-brand-form__image-upload" title="{{ __('agricart.brands.image.upload') }}">
                @if ($imagePreviewUrl)
                    <img src="{{ $imagePreviewUrl }}" alt="" class="agricart-brand-form__image-preview" />
                @else
                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-brand-form__image-placeholder" />
                    <span class="agricart-brand-form__image-label">{{ __('agricart.brands.image.upload') }}</span>
                @endif
                @unless ($this->isReadonly())
                    <input type="file" wire:model="imageUpload" accept="image/*" class="agricart-category-form__file-input" />
                @endunless
            </label>
            <div wire:loading wire:target="imageUpload" class="agricart-brand-form__upload-hint">
                {{ __('agricart.brands.image.uploading') }}
            </div>
        </div>

        <div class="agricart-category-form__hero-names">
            <div class="agricart-category-form__names">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-brand-name-en">
                        {{ __('agricart.brands.fields.name_en') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <input id="agricart-brand-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) required />
                    </x-filament::input.wrapper>
                    <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.name_from_file') }}</p>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-brand-name-ur">
                        {{ __('agricart.brands.fields.name_ur') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <input id="agricart-brand-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>

    <section class="agricart-form-panel__section agricart-brand-form__ai-section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.brands.fields.ai_message') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.ai_message') }}</p>
        <div class="agricart-form-panel__field agricart-form-panel__field--full">
            <textarea
                rows="4"
                wire:model="enrichmentPrompt"
                class="agricart-brand-form__textarea agricart-brand-form__textarea--ai"
                @disabled($this->isReadonly())
                placeholder="{{ __('agricart.brands.ai.prompt_example') }}"
            ></textarea>
        </div>
    </section>

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.brands.sections.descriptions') }}</h3>
        <div class="agricart-brand-form__description-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.description_en') }}</label>
                <textarea
                    rows="5"
                    wire:model="description_en"
                    class="agricart-brand-form__textarea agricart-brand-form__textarea--description"
                    @disabled($this->isReadonly())
                ></textarea>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.description_ur') }}</label>
                <textarea
                    rows="5"
                    wire:model="description_ur"
                    dir="rtl"
                    class="agricart-brand-form__textarea agricart-brand-form__textarea--description"
                    @disabled($this->isReadonly())
                ></textarea>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.brands.sections.details') }}</h3>
        <div class="agricart-form-panel__fields">
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.website_url') }}</label>
                <x-filament::input.wrapper>
                    <input type="url" wire:model="website_url" class="fi-input" placeholder="https://..." @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.website_url') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.code') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="code" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.slug') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="slug" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select wire:model.live="status" class="fi-select-input" @disabled($this->isReadonly())>
                        <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                        <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                    </select>
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.status') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.agricart_rating') }}</label>
                <x-filament::input.wrapper>
                    <select wire:model.live="agricart_rating" class="fi-select-input" @disabled($this->isReadonly())>
                        <option value="">{{ __('agricart.brands.fields.agricart_rating_none') }}</option>
                        @for ($score = 10; $score >= 1; $score--)
                            <option value="{{ $score }}">{{ __('agricart.brands.fields.agricart_rating_value', ['score' => $score]) }}</option>
                        @endfor
                    </select>
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.ai_status') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ __('agricart.categories.ai_status.' . ($ai_status ?: 'none')) }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
            @if (filled($agricart_rating))
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <label class="agricart-form-panel__label">
                        {{ __('agricart.brands.fields.agricart_rating_reason_en') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <textarea
                        rows="3"
                        wire:model="agricart_rating_reason_en"
                        class="agricart-brand-form__textarea"
                        @disabled($this->isReadonly())
                        placeholder="{{ __('agricart.brands.hints.agricart_rating_reason_en') }}"
                    ></textarea>
                </div>
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <label class="agricart-form-panel__label">
                        {{ __('agricart.brands.fields.agricart_rating_reason_ur') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <textarea
                        rows="3"
                        wire:model="agricart_rating_reason_ur"
                        dir="rtl"
                        class="agricart-brand-form__textarea"
                        @disabled($this->isReadonly())
                        placeholder="{{ __('agricart.brands.hints.agricart_rating_reason_ur') }}"
                    ></textarea>
                </div>
            @endif
            @if ($status === 'inactive')
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <label class="agricart-form-panel__label">
                        {{ __('agricart.brands.fields.inactive_reason_en') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <textarea
                        rows="3"
                        wire:model="inactive_reason_en"
                        class="agricart-brand-form__textarea"
                        @disabled($this->isReadonly())
                        placeholder="{{ __('agricart.brands.hints.inactive_reason_en') }}"
                    ></textarea>
                </div>
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <label class="agricart-form-panel__label">
                        {{ __('agricart.brands.fields.inactive_reason_ur') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <textarea
                        rows="3"
                        wire:model="inactive_reason_ur"
                        dir="rtl"
                        class="agricart-brand-form__textarea"
                        @disabled($this->isReadonly())
                        placeholder="{{ __('agricart.brands.hints.inactive_reason_ur') }}"
                    ></textarea>
                </div>
            @endif
        </div>
    </section>
</div>
