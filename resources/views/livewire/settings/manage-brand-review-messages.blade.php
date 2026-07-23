<div class="agricart-review-messages">
    <div class="agricart-review-messages__intro">
        <h3 class="agricart-review-messages__title">{{ __('agricart.brands.review_messages.title') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.brands.review_messages.intro') }}</p>
    </div>

    @if ($statusMessage)
        <div class="agricart-review-messages__status agricart-review-messages__status--{{ $statusType }}" role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-review-messages__list">
        @foreach ($criteriaKeys as $key)
            @php($row = $messages[$key] ?? [
                'positive_en' => '',
                'positive_ur' => '',
                'neutral_en' => '',
                'neutral_ur' => '',
                'needs_improvement_en' => '',
                'needs_improvement_ur' => '',
            ])
            <section class="agricart-review-messages__card" wire:key="review-msg-{{ $key }}">
                <header class="agricart-review-messages__card-head">
                    <h4 class="agricart-review-messages__card-title">
                        {{ __('agricart.brands.review_criteria.'.$key.'.label') }}
                    </h4>
                    <p class="agricart-review-messages__card-title-ur" dir="rtl">
                        {{ __('agricart.brands.review_criteria.'.$key.'.label_ur') }}
                    </p>
                    <p class="agricart-form-panel__hint">{{ __('agricart.brands.review_criteria.'.$key.'.check') }}</p>
                </header>

                <div class="agricart-review-messages__grid agricart-review-messages__grid--bands">
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.positive_en') }}</label>
                        <textarea
                            rows="2"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.positive_en"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.positive_ur') }}</label>
                        <textarea
                            rows="2"
                            dir="rtl"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.positive_ur"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.neutral_en') }}</label>
                        <textarea
                            rows="2"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.neutral_en"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.neutral_ur') }}</label>
                        <textarea
                            rows="2"
                            dir="rtl"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.neutral_ur"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.needs_improvement_en') }}</label>
                        <textarea
                            rows="2"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.needs_improvement_en"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.needs_improvement_ur') }}</label>
                        <textarea
                            rows="2"
                            dir="rtl"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.needs_improvement_ur"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    @if ($canManage)
        <div class="agricart-review-messages__actions">
            <button
                type="button"
                class="agricart-review-messages__save"
                wire:click="save"
                wire:loading.attr="disabled"
                @disabled($saving)
            >
                <span wire:loading.remove wire:target="save">{{ __('agricart.brands.review_messages.save') }}</span>
                <span wire:loading wire:target="save">{{ __('agricart.brands.review_messages.saving') }}</span>
            </button>
            <button
                type="button"
                class="agricart-review-messages__reset"
                wire:click="resetToFactoryDefaults"
                wire:confirm="{{ __('agricart.brands.review_messages.reset_confirm') }}"
                wire:loading.attr="disabled"
            >
                {{ __('agricart.brands.review_messages.reset_button') }}
            </button>
        </div>
    @endif
</div>
