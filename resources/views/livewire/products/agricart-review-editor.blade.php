<div
    class="agricart-brand-form agricart-category-form agricart-product-agricart-review"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('product-review-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('product-review-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:product-review-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:product-review-form-open.window="$wire.openForm($event.detail.productId ?? null, $event.detail.mode ?? 'create')"
>
    <div class="agricart-product-agricart-review__intro">
        <h3 class="agricart-product-agricart-review__title">{{ __('agricart.products.review_page.search_heading') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.products.review_page.search_hint') }}</p>
    </div>

    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($canChangeProduct || $productId === null)
        <section class="agricart-form-panel__section">
            <div class="agricart-form-panel__fields">
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <label class="agricart-form-panel__label">{{ __('agricart.products.review_page.search_label') }}</label>
                    <x-filament::input.wrapper>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="product_search"
                            class="fi-input"
                            placeholder="{{ __('agricart.products.review_page.search_placeholder') }}"
                            autocomplete="off"
                            @disabled($productId !== null || ! $canChangeProduct)
                        />
                    </x-filament::input.wrapper>

                    @if ($productId === null && $canChangeProduct)
                        @if ($productOptions !== [])
                            <ul class="agricart-product-agricart-review__results" role="listbox">
                                @foreach ($productOptions as $id => $label)
                                    <li>
                                        <button
                                            type="button"
                                            class="agricart-product-agricart-review__result"
                                            wire:click="selectProduct({{ $id }})"
                                        >
                                            {{ $label }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif (mb_strlen(trim($product_search)) >= 2)
                            <p class="agricart-form-panel__hint">{{ __('agricart.products.review_page.search_empty') }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($productId)
        <section class="agricart-form-panel__section">
            <div class="agricart-product-agricart-review__selected">
                <div>
                    <p class="agricart-product-agricart-review__name">{{ $productNameEn }}</p>
                    @if (filled($productNameUr))
                        <p class="agricart-product-agricart-review__name-ur" dir="rtl">{{ $productNameUr }}</p>
                    @endif
                    <p class="agricart-product-agricart-review__meta">
                        {{ __('agricart.products.fields.brand') }}: {{ $brandNameEn !== '' ? $brandNameEn : '—' }}
                    </p>
                </div>
                @if ($canChangeProduct)
                    <button type="button" class="agricart-brand-review__clear" wire:click="clearSelectedProduct">
                        {{ __('agricart.products.review_page.change_product') }}
                    </button>
                @endif
            </div>
        </section>

        <section class="agricart-form-panel__section">
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <div class="agricart-brand-review">
                    <div class="agricart-brand-review__header">
                        <div>
                            <h4 class="agricart-brand-review__title">{{ __('agricart.products.fields.agricart_rating') }}</h4>
                            <p class="agricart-form-panel__hint">{{ __('agricart.products.hints.agricart_rating', ['total' => \App\Support\Catalog\BrandAgricartReviewCriteria::scoreMax()]) }}</p>
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
                            <div class="agricart-brand-review__row" role="listitem" wire:key="product-review-criterion-{{ $criterionKey }}">
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
                                <div class="agricart-brand-review__actions" role="group" aria-label="{{ __('agricart.products.fields.agricart_rating') }}">
                                    @for ($rating = 1; $rating <= 10; $rating++)
                                        @php
                                            $btnBand = \App\Support\Catalog\BrandAgricartReviewCriteria::bandForRating($rating);
                                        @endphp
                                        <button
                                            type="button"
                                            class="agricart-brand-review__btn agricart-brand-review__btn--{{ $btnBand }} {{ (int) $criterionRating === $rating ? 'is-active' : '' }}"
                                            wire:click="setAgricartCriterion('{{ $criterionKey }}', {{ $rating }})"
                                            @disabled(! $canEdit)
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
                                                @disabled(! $canEdit)
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
                                                @disabled(! $canEdit)
                                                placeholder="{{ __('agricart.brands.hints.criterion_reason_ur') }}"
                                            ></textarea>
                                        </div>
                                        @if ($canEdit)
                                            <label class="agricart-brand-review__default">
                                                <input type="checkbox" wire:model="saveCriterionMessageAsDefault.{{ $criterionKey }}" />
                                                <span>{{ __('agricart.brands.review_messages.save_as_default') }}</span>
                                            </label>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="agricart-brand-review__notes">
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.agricart_rating_reason_en') }}</label>
                            <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating_auto') }}</p>
                            <textarea
                                rows="8"
                                wire:model="agricart_rating_reason_en"
                                class="agricart-brand-form__textarea"
                                readonly
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.agricart_rating_reason_ur') }}</label>
                            <textarea
                                rows="8"
                                wire:model="agricart_rating_reason_ur"
                                dir="rtl"
                                class="agricart-brand-form__textarea"
                                readonly
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.agricart_rating_revision_en') }}</label>
                            <p class="agricart-form-panel__hint">{{ __('agricart.brands.hints.agricart_rating_revision') }}</p>
                            <textarea
                                rows="3"
                                wire:model="agricart_rating_revision_en"
                                class="agricart-brand-form__textarea"
                                @disabled(! $canEdit)
                                placeholder="{{ __('agricart.brands.hints.agricart_rating_revision_en') }}"
                            ></textarea>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.agricart_rating_revision_ur') }}</label>
                            <textarea
                                rows="3"
                                wire:model="agricart_rating_revision_ur"
                                dir="rtl"
                                class="agricart-brand-form__textarea"
                                @disabled(! $canEdit)
                                placeholder="{{ __('agricart.brands.hints.agricart_rating_revision_ur') }}"
                            ></textarea>
                        </div>
                    </div>

                    @if ($canEdit)
                        <p class="agricart-form-panel__hint">{{ __('agricart.products.hints.agricart_rating_ai') }}</p>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="agricart-form-panel__section">
            <p class="agricart-form-panel__hint">{{ __('agricart.products.review_page.empty_state') }}</p>
        </section>
    @endif
</div>
