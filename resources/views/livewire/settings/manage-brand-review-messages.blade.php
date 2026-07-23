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
            @php($row = $messages[$key] ?? ['high_en' => '', 'high_ur' => '', 'low_en' => '', 'low_ur' => ''])
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

                <div class="agricart-review-messages__grid">
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.high_en') }}</label>
                        <textarea
                            rows="2"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.high_en"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.high_ur') }}</label>
                        <textarea
                            rows="2"
                            dir="rtl"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.high_ur"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.low_en') }}</label>
                        <textarea
                            rows="2"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.low_en"
                            @disabled(! $canManage)
                        ></textarea>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label">{{ __('agricart.brands.review_messages.low_ur') }}</label>
                        <textarea
                            rows="2"
                            dir="rtl"
                            class="agricart-brand-form__textarea"
                            wire:model="messages.{{ $key }}.low_ur"
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
