@php
    $readonly = $this->isReadonly();
    $dueNotice = $this->dueNotice();
@endphp

<div
    class="agricart-form-panel__fields agricart-finance-expense-form"
    x-data
    x-on:finance-expense-form-open.window="$wire.openForm($event.detail?.expenseId ?? $event.detail?.[0]?.expenseId ?? null, $event.detail?.mode ?? $event.detail?.[0]?.mode ?? 'create')"
    x-on:finance-expense-form-action.window="$wire.handlePanelAction($event.detail?.action ?? $event.detail?.[0]?.action ?? '')"
>
    <style>
        .agricart-form-panel__fields.agricart-finance-expense-form {
            display: flex !important;
            flex-direction: column;
            gap: 0.75rem;
        }

        .agricart-finance-expense-form__row,
        .agricart-finance-expense-form__notes,
        .agricart-finance-expense-form__ai {
            width: 100%;
            min-width: 0;
            grid-column: 1 / -1;
        }

        .agricart-finance-expense-form__row {
            display: grid !important;
            gap: 0.65rem 0.7rem;
            align-items: end;
        }

        .agricart-finance-expense-form__row--meta {
            grid-template-columns:
                minmax(6.25rem, 0.85fr)
                minmax(7.5rem, 1.35fr)
                minmax(5.5rem, 0.65fr)
                minmax(4.35rem, 0.42fr)
                minmax(4.35rem, 0.42fr);
        }

        .agricart-finance-expense-form__row--names {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr) minmax(7.25rem, 0.7fr);
        }

        .agricart-finance-expense-form__row--details {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .agricart-finance-expense-form__row--body {
            grid-template-columns: minmax(0, 1fr);
            align-items: stretch;
        }

        .agricart-finance-expense-form__row--body:has(.agricart-finance-expense-form__ai) {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .agricart-finance-expense-form__row--meta .fi-input-wrp,
        .agricart-finance-expense-form__row--names .fi-input-wrp,
        .agricart-finance-expense-form__row--details .fi-input-wrp {
            min-height: 2.4rem;
        }

        .agricart-finance-expense-form__row--meta input.fi-input,
        .agricart-finance-expense-form__row--meta select.fi-input,
        .agricart-finance-expense-form__row--meta select.fi-select-input,
        .agricart-finance-expense-form__row--names input.fi-input,
        .agricart-finance-expense-form__row--details input.fi-input {
            min-height: 2.4rem;
            height: 2.4rem;
            padding-block: 0.4rem;
            padding-inline: 0.55rem;
        }

        .agricart-finance-expense-form__day .fi-select-input,
        .agricart-finance-expense-form__day select.fi-input {
            padding-inline: 0.4rem;
            text-align: center;
        }

        .agricart-finance-expense-form__notes,
        .agricart-finance-expense-form__ai {
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100%;
        }

        .agricart-finance-expense-form__notes .fi-input-wrp,
        .agricart-finance-expense-form__ai .fi-input-wrp {
            display: flex;
            width: 100%;
            flex: 1 1 auto;
        }

        .agricart-finance-expense-form__notes textarea.fi-input,
        .agricart-finance-expense-form__ai textarea.fi-input {
            display: block;
            width: 100% !important;
            max-width: 100%;
            min-width: 0;
            min-height: 8.25rem;
            height: 100%;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            direction: ltr;
            text-align: start;
            padding: 0.65rem 0.75rem;
            resize: vertical;
        }

        .agricart-finance-expense-form__due-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
    </style>

    @if ($statusMessage)
        <div @class([
            'agricart-form-panel__alert',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($dueNotice)
        <div @class([
            'agricart-form-panel__alert agricart-finance-expense-form__due',
            'is-success' => $dueNotice['type'] === 'success',
            'is-error' => $dueNotice['type'] === 'error',
            'is-warning' => in_array($dueNotice['type'], ['warning', 'info'], true),
        ]) role="status">
            <div class="agricart-finance-expense-form__due-actions">
                <span>{{ $dueNotice['message'] }}</span>
                @if ($this->hasOpenCycle() && ! $readonly)
                    <button type="button" class="agricart-categories__action" wire:click="markCycleDone" wire:confirm="{{ __('agricart.finance.expenses.actions.mark_done_confirm') }}">
                        {{ __('agricart.finance.expenses.actions.mark_done') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="agricart-finance-expense-form__row agricart-finance-expense-form__row--meta agricart-form-panel__field--full">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-number">{{ __('agricart.finance.expenses.fields.expense_number') }}</label>
            <x-filament::input.wrapper>
                <input id="expense-number" type="text" wire:model="expense_number" class="fi-input" readonly disabled />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category">
                {{ __('agricart.finance.expenses.fields.category') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <select id="expense-category" wire:model="expense_category_id" class="fi-input" @disabled($readonly)>
                    <option value="">{{ __('agricart.finance.expenses.fields.category_none') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->displayName() }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>
            @error('expense_category_id') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-status">{{ __('agricart.finance.expenses.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-status" wire:model="status" class="fi-input" @disabled($readonly)>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
            @error('status') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field agricart-finance-expense-form__day">
            <label class="agricart-form-panel__label" for="expense-issue-day">{{ __('agricart.finance.expenses.fields.issue_day') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-issue-day" wire:model.live="issue_day" class="fi-input" @disabled($readonly)>
                    <option value="">{{ __('agricart.finance.expenses.fields.day_none') }}</option>
                    @for ($day = 1; $day <= 31; $day++)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endfor
                </select>
            </x-filament::input.wrapper>
            @error('issue_day') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field agricart-finance-expense-form__day">
            <label class="agricart-form-panel__label" for="expense-due-day">{{ __('agricart.finance.expenses.fields.due_day') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-due-day" wire:model.live="due_day" class="fi-input" @disabled($readonly)>
                    <option value="">{{ __('agricart.finance.expenses.fields.day_none') }}</option>
                    @for ($day = 1; $day <= 31; $day++)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endfor
                </select>
            </x-filament::input.wrapper>
            @error('due_day') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>
    <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.dates') }}</p>

    <div class="agricart-finance-expense-form__row agricart-finance-expense-form__row--names agricart-form-panel__field--full">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-name-en">
                {{ __('agricart.finance.expenses.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="expense-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-name-ur">{{ __('agricart.finance.expenses.fields.name_ur') }}</label>
            <x-filament::input.wrapper>
                <input id="expense-name-ur" type="text" wire:model="name_ur" class="fi-input" dir="rtl" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-estimated-cost">{{ __('agricart.finance.expenses.fields.estimated_cost') }}</label>
            <x-filament::input.wrapper>
                <input
                    id="expense-estimated-cost"
                    type="number"
                    inputmode="decimal"
                    step="0.01"
                    min="0"
                    wire:model="estimated_cost"
                    class="fi-input"
                    @disabled($readonly)
                    placeholder="0.00"
                />
            </x-filament::input.wrapper>
            @error('estimated_cost') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>
    <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.estimated_cost') }}</p>

    <div class="agricart-finance-expense-form__row agricart-finance-expense-form__row--details agricart-form-panel__field--full">
        @foreach ([1, 2, 3, 4] as $detailIndex)
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="expense-detail-{{ $detailIndex }}">{{ __('agricart.finance.expenses.fields.detail_'.$detailIndex) }}</label>
                <x-filament::input.wrapper>
                    <input
                        id="expense-detail-{{ $detailIndex }}"
                        type="text"
                        wire:model="detail_{{ $detailIndex }}"
                        class="fi-input"
                        @disabled($readonly)
                        placeholder="{{ __('agricart.finance.expenses.fields.detail_'.$detailIndex.'_placeholder') }}"
                    />
                </x-filament::input.wrapper>
                @error('detail_'.$detailIndex) <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
            </div>
        @endforeach
    </div>
    <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.details') }}</p>

    <div class="agricart-finance-expense-form__row agricart-finance-expense-form__row--body agricart-form-panel__field--full">
        <div class="agricart-form-panel__field agricart-finance-expense-form__notes">
            <label class="agricart-form-panel__label" for="expense-information">{{ __('agricart.finance.expenses.fields.information') }}</label>
            <x-filament::input.wrapper>
                <textarea id="expense-information" wire:model="information" class="fi-input agricart-form-panel__textarea" rows="5" @disabled($readonly) placeholder="{{ __('agricart.finance.expenses.fields.information_placeholder') }}"></textarea>
            </x-filament::input.wrapper>
            @error('information') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
            <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.information') }}</p>
        </div>

        @if (! $readonly)
            <div class="agricart-form-panel__field agricart-finance-expense-form__ai">
                <label class="agricart-form-panel__label" for="expense-ai-prompt">{{ __('agricart.finance.expenses.ai.prompt_label') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="expense-ai-prompt" wire:model="enrichmentPrompt" class="fi-input agricart-form-panel__textarea" rows="5" placeholder="{{ __('agricart.finance.expenses.ai.prompt_placeholder') }}"></textarea>
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.ai.hint') }}</p>
            </div>
        @endif
    </div>
</div>
