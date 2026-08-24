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
        /* Keep meta row + selects correct even if cached Vite CSS is stale. */
        .agricart-finance-expense-form__row.agricart-finance-expense-form__row--meta {
            display: grid !important;
            grid-template-columns:
                minmax(5.75rem, 0.95fr)
                minmax(7.5rem, 1.45fr)
                minmax(5.5rem, 0.85fr)
                minmax(4.75rem, 0.55fr)
                minmax(4.75rem, 0.55fr) !important;
            align-items: end;
            gap: 0.65rem;
            width: 100%;
        }

        .agricart-finance-expense-form__row--meta > .agricart-form-panel__field {
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        .agricart-finance-expense-form__row--meta .fi-input-wrp {
            display: flex !important;
            width: 100% !important;
            min-width: 0;
        }

        .agricart-finance-expense-form__row--meta select.fi-input,
        .agricart-finance-expense-form__row--meta select.fi-select-input {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            flex: 1 1 auto;
            box-sizing: border-box;
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
                <select id="expense-category" wire:model="expense_category_id" class="fi-select-input" @disabled($readonly)>
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
                <select id="expense-status" wire:model="status" class="fi-select-input" @disabled($readonly)>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
            @error('status') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field agricart-finance-expense-form__day">
            <label class="agricart-form-panel__label" for="expense-issue-day">{{ __('agricart.finance.expenses.fields.issue_day') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-issue-day" wire:model.live="issue_day" class="fi-select-input" @disabled($readonly)>
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
                <select id="expense-due-day" wire:model.live="due_day" class="fi-select-input" @disabled($readonly)>
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

        <div class="agricart-form-panel__field agricart-finance-expense-form__bill">
            <span class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.bill_image') }}</span>
            <div class="agricart-purchase-form__bill-slot agricart-finance-expense-form__bill-slot">
                @if ($billImagePreviewUrl)
                    <div class="agricart-purchase-form__bill-upload is-filled">
                        <img src="{{ $billImagePreviewUrl }}" alt="" class="agricart-purchase-form__bill-preview" />
                        <div class="agricart-purchase-form__bill-overlay">
                            <a href="{{ $billImagePreviewUrl }}" target="_blank" class="agricart-purchase-form__bill-overlay-btn" title="{{ __('agricart.finance.expenses.actions.view_bill') }}">
                                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedEye" class="agricart-purchase-form__bill-overlay-icon" />
                            </a>
                            @unless ($readonly)
                                <button type="button" class="agricart-purchase-form__bill-overlay-btn is-danger" wire:click="removeBillImage" title="{{ __('agricart.finance.expenses.actions.remove_bill') }}">
                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedTrash" class="agricart-purchase-form__bill-overlay-icon" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @elseif (! $readonly)
                    <label class="agricart-purchase-form__bill-upload" title="{{ __('agricart.finance.expenses.actions.bill_upload') }}">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentText" class="agricart-purchase-form__bill-icon" />
                        <span class="agricart-purchase-form__bill-label">{{ __('agricart.finance.expenses.actions.bill_upload') }}</span>
                        <input type="file" accept="image/*" wire:model="billImageUpload" class="agricart-category-form__file-input" />
                    </label>
                    <div wire:loading wire:target="billImageUpload" class="agricart-purchase-form__bill-loading">{{ __('agricart.finance.expenses.actions.bill_uploading') }}</div>
                @else
                    <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.bill_image_empty') }}</p>
                @endif
            </div>
            @error('billImageUpload') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
            <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.bill_image') }}</p>
        </div>
    </div>
</div>
