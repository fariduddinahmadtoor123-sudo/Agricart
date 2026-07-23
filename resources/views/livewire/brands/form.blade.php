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
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <div class="agricart-brand-review">
                    <div class="agricart-brand-review__header">
                        <div>
                            <h4 class="agricart-brand-review__title">{{ __('agricart.brands.fields.agricart_rating') }}</h4>
                            <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating', ['total' => \App\Support\Catalog\BrandAgricartReviewCriteria::scoreMax()]) }}</p>
                        </div>
                        <div class="agricart-brand-review__score" aria-live="polite">
                            <span class="agricart-brand-review__score-value">{{ filled($agricart_rating) ? $agricart_rating : '—' }}</span>
                            <span class="agricart-brand-review__score-of">/ {{ \App\Support\Catalog\BrandAgricartReviewCriteria::scoreMax() }}</span>
                        </div>
                    </div>

                    <div class="agricart-brand-review__list" role="list">
                        @foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $criterionKey)
                            @php
                                $criterion = $agricart_rating_criteria[$criterionKey] ?? ['rating' => null, 'reason_en' => '', 'reason_ur' => ''];
                                $criterionRating = $criterion['rating'] ?? null;
                                $criterionBand = \App\Support\Catalog\BrandAgricartReviewCriteria::bandForRating(
                                    is_numeric($criterionRating) ? (int) $criterionRating : null,
                                );
                            @endphp
                            <div class="agricart-brand-review__row" role="listitem" wire:key="brand-criterion-{{ $criterionKey }}">
                                <div class="agricart-brand-review__copy">
                                    <p class="agricart-brand-review__label">
                                        <span>{{ __('agricart.brands.review_criteria.'.$criterionKey.'.label') }}</span>
                                        <span class="agricart-brand-review__label-ur" dir="rtl">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.label_ur') }}</span>
                                    </p>
                                    <p class="agricart-brand-review__check">{{ __('agricart.brands.review_criteria.'.$criterionKey.'.check') }}</p>
                                    @if ($criterionBand)
                                        <p class="agricart-brand-review__band agricart-brand-review__band--{{ $criterionBand }}">
                                            {{ __('agricart.brands.review_actions.'.$criterionBand) }}
                                            <span class="agricart-brand-review__band-score">({{ $criterionRating }}/{{ \App\Support\Catalog\BrandAgricartReviewCriteria::MAX_RATING }})</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="agricart-brand-review__actions" role="group" aria-label="{{ __('agricart.brands.fields.agricart_rating') }}">
                                    @for ($rating = 1; $rating <= 10; $rating++)
                                        @php
                                            $btnBand = \App\Support\Catalog\BrandAgricartReviewCriteria::bandForRating($rating);
                                        @endphp
                                        <button
                                            type="button"
                                            class="agricart-brand-review__btn agricart-brand-review__btn--{{ $btnBand }} {{ (int) $criterionRating === $rating ? 'is-active' : '' }}"
                                            wire:click="setAgricartCriterion('{{ $criterionKey }}', {{ $rating }})"
                                            @disabled($this->isReadonly())
                                        >{{ $rating }}</button>
                                    @endfor
                                </div>
                                @if ($criterionRating !== null)
                                    <div class="agricart-brand-review__reasons">
                                        <p class="agricart-form-panel__hint agricart-brand-review__suggest-hint">
                                            {{ __('agricart.brands.review_messages.suggested_hint') }}
                                        </p>
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">
                                                {{ __('agricart.brands.fields.criterion_reason_en') }}
                                                <span class="agricart-form-panel__required">*</span>
                                            </label>
                                            <textarea
                                                rows="2"
                                                wire:model.blur="agricart_rating_criteria.{{ $criterionKey }}.reason_en"
                                                class="agricart-brand-form__textarea"
                                                @disabled($this->isReadonly())
                                                placeholder="{{ __('agricart.brands.hints.criterion_reason_en') }}"
                                            ></textarea>
                                        </div>
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">
                                                {{ __('agricart.brands.fields.criterion_reason_ur') }}
                                                <span class="agricart-form-panel__required">*</span>
                                            </label>
                                            <textarea
                                                rows="2"
                                                wire:model.blur="agricart_rating_criteria.{{ $criterionKey }}.reason_ur"
                                                dir="rtl"
                                                class="agricart-brand-form__textarea"
                                                @disabled($this->isReadonly())
                                                placeholder="{{ __('agricart.brands.hints.criterion_reason_ur') }}"
                                            ></textarea>
                                        </div>
                                        @unless ($this->isReadonly())
                                            <label class="agricart-brand-review__default">
                                                <input
                                                    type="checkbox"
                                                    wire:model="saveCriterionMessageAsDefault.{{ $criterionKey }}"
                                                />
                                                <span>{{ __('agricart.brands.review_messages.save_as_default') }}</span>
                                            </label>
                                        @endunless
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @unless ($this->isReadonly())
                        <div class="agricart-brand-review__toolbar">
                            @if (filled($brandId))
                                <button
                                    type="button"
                                    class="agricart-brand-review__print"
                                    wire:click="openPrintSheet"
                                >
                                    {{ __('agricart.brands.actions.print_short') }}
                                </button>
                            @endif
                            <button
                                type="button"
                                class="agricart-brand-review__ai"
                                wire:click="generateAgricartReviewWithAi"
                                wire:loading.attr="disabled"
                                wire:target="generateAgricartReviewWithAi"
                                @disabled($enriching)
                            >
                                <span wire:loading.remove wire:target="generateAgricartReviewWithAi">
                                    {{ __('agricart.brands.ai.review_write') }}
                                </span>
                                <span wire:loading wire:target="generateAgricartReviewWithAi">
                                    {{ __('agricart.brands.ai.review_writing') }}
                                </span>
                            </button>
                            <button type="button" class="agricart-brand-review__clear" wire:click="clearAgricartReview">
                                {{ __('agricart.brands.review_actions.clear') }}
                            </button>
                        </div>
                        <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating_ai') }}</p>
                    @else
                        @if (filled($brandId))
                            <div class="agricart-brand-review__toolbar">
                                <button
                                    type="button"
                                    class="agricart-brand-review__print"
                                    wire:click="openPrintSheet"
                                >
                                    {{ __('agricart.brands.actions.print_short') }}
                                </button>
                            </div>
                        @endif
                    @endunless

                    <div class="agricart-brand-review__notes">
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.agricart_rating_reason_en') }}</label>
                            <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating_auto') }}</p>
                            <textarea
                                rows="8"
                                wire:model="agricart_rating_reason_en"
                                class="agricart-brand-form__textarea"
                                readonly
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.agricart_rating_reason_ur') }}</label>
                            <textarea
                                rows="8"
                                wire:model="agricart_rating_reason_ur"
                                dir="rtl"
                                class="agricart-brand-form__textarea"
                                readonly
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.agricart_rating_revision_en') }}</label>
                            <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating_revision') }}</p>
                            <textarea
                                rows="3"
                                wire:model="agricart_rating_revision_en"
                                class="agricart-brand-form__textarea"
                                @disabled($this->isReadonly())
                                placeholder="{{ __('agricart.brands.hints.agricart_rating_revision_en') }}"
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.agricart_rating_revision_ur') }}</label>
                            <textarea
                                rows="3"
                                wire:model="agricart_rating_revision_ur"
                                dir="rtl"
                                class="agricart-brand-form__textarea"
                                @disabled($this->isReadonly())
                                placeholder="{{ __('agricart.brands.hints.agricart_rating_revision_ur') }}"
                            ></textarea>
                        </div>
                    </div>

                    <div class="agricart-brand-review__legal">
                        <h4 class="agricart-brand-review__title">{{ __('agricart.brands.fields.brand_review_notice') }}</h4>
                        <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.brand_review_notice') }}</p>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.brand_review_notice_en') }}</label>
                            <textarea
                                rows="5"
                                wire:model="brand_review_notice_en"
                                class="agricart-brand-form__textarea"
                                @disabled($this->isReadonly())
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.brand_review_notice_ur') }}</label>
                            <textarea
                                rows="5"
                                wire:model="brand_review_notice_ur"
                                dir="rtl"
                                class="agricart-brand-form__textarea"
                                @disabled($this->isReadonly())
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.brands.fields.ai_status') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ __('agricart.categories.ai_status.' . ($ai_status ?: 'none')) }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
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
