@php
    $readonly = $this->isReadonly();
    $paymentLocked = $this->paymentLocked();
@endphp

<div class="agricart-form-panel__fields agricart-finance-expense-payment-form">
    <style>
        .agricart-form-panel__fields.agricart-finance-expense-payment-form {
            display: flex !important;
            flex-direction: column;
            gap: 0.75rem;
        }

        .agricart-finance-expense-payment-form__row {
            display: grid !important;
            width: 100%;
            min-width: 0;
            gap: 0.65rem;
            align-items: end;
        }

        .agricart-finance-expense-payment-form__row > .agricart-form-panel__field {
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        .agricart-finance-expense-payment-form__row > .agricart-form-panel__field > .agricart-form-panel__label {
            margin-block-end: 0.3rem;
        }

        .agricart-finance-expense-payment-form__row > .agricart-form-panel__field > .fi-input-wrp {
            display: flex !important;
            width: 100% !important;
            min-width: 0;
        }

        .agricart-finance-expense-payment-form__row--meta {
            grid-template-columns:
                minmax(5.5rem, 0.85fr)
                minmax(8rem, 1.55fr)
                minmax(6.25rem, 0.9fr)
                minmax(5.5rem, 0.8fr)
                minmax(4.75rem, 0.65fr)
                minmax(5.25rem, 0.75fr) !important;
        }

        .agricart-finance-expense-payment-form__row--meta select.fi-input,
        .agricart-finance-expense-payment-form__row--meta select.fi-select-input,
        .agricart-finance-expense-payment-form__row--meta input.fi-input {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            min-height: 2.4rem;
            height: 2.4rem;
            box-sizing: border-box;
        }

        .agricart-finance-expense-payment-form__row--bank {
            grid-template-columns: minmax(0, 1fr);
        }

        .agricart-finance-expense-payment-form__notes {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-width: 0;
        }

        .agricart-finance-expense-payment-form__notes > .agricart-form-panel__label {
            display: block;
            width: 100%;
            margin-block-end: 0.35rem;
            text-align: start;
            direction: ltr;
        }

        .agricart-finance-expense-payment-form__notes .fi-input-wrp {
            display: flex;
            width: 100%;
        }

        .agricart-finance-expense-payment-form__notes textarea.fi-input {
            display: block;
            width: 100% !important;
            max-width: 100%;
            min-width: 0;
            min-height: 5.5rem;
            direction: ltr;
            text-align: start;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            padding: 0.65rem 0.75rem;
            resize: vertical;
        }

        .agricart-finance-expense-payment-form__hint-box {
            width: 100%;
            margin: 0;
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

    <div class="agricart-finance-expense-payment-form__row agricart-finance-expense-payment-form__row--meta">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-number">{{ __('agricart.finance.expense_payments.fields.payment_number') }}</label>
            <x-filament::input.wrapper>
                <input id="expay-number" type="text" wire:model="payment_number" class="fi-input" readonly disabled />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-expense">
                {{ __('agricart.finance.expense_payments.fields.expense') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <select id="expay-expense" wire:model.live="expense_id" class="fi-select-input" @disabled($readonly || $paymentLocked)>
                    <option value="">{{ __('agricart.finance.expense_payments.fields.expense_none') }}</option>
                    @foreach ($expenses as $expense)
                        <option value="{{ $expense->id }}">
                            {{ $expense->category?->displayName() ? $expense->category->displayName().' / ' : '' }}{{ $expense->displayName() }} ({{ $expense->expense_number }})
                        </option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>
            @error('expense_id') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-date">
                {{ __('agricart.finance.expense_payments.fields.payment_date') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="expay-date" type="date" wire:model="payment_date" class="fi-input" @disabled($readonly || $paymentLocked) />
            </x-filament::input.wrapper>
            @error('payment_date') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-amount">
                {{ __('agricart.finance.expense_payments.fields.amount') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="expay-amount" type="text" inputmode="decimal" wire:model="amount" class="fi-input" placeholder="0.00" @disabled($readonly || $paymentLocked) />
            </x-filament::input.wrapper>
            @error('amount') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-time">{{ __('agricart.finance.expense_payments.fields.payment_time') }}</label>
            <x-filament::input.wrapper>
                <input id="expay-time" type="time" wire:model="payment_time" class="fi-input" @disabled($readonly || $paymentLocked) />
            </x-filament::input.wrapper>
            @error('payment_time') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expay-wallet">
                {{ __('agricart.finance.expense_payments.fields.source_wallet') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <select id="expay-wallet" wire:model.live="source_wallet" class="fi-select-input" @disabled($readonly || $paymentLocked)>
                    <option value="cash">{{ __('agricart.finance.expense_payments.wallets.cash') }}</option>
                    <option value="bank">{{ __('agricart.finance.expense_payments.wallets.bank') }}</option>
                </select>
            </x-filament::input.wrapper>
            @error('source_wallet') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($source_wallet === 'bank')
        <div class="agricart-finance-expense-payment-form__row agricart-finance-expense-payment-form__row--bank">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="expay-bank">
                    {{ __('agricart.finance.expense_payments.fields.bank_account') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <select id="expay-bank" wire:model="bank_account_id" class="fi-select-input" @disabled($readonly || $paymentLocked)>
                        <option value="">{{ __('agricart.finance.expense_payments.fields.bank_account_none') }}</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->displayName() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
                @error('bank_account_id') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    @if ($selectedExpense)
        <p class="agricart-form-panel__hint agricart-finance-expense-payment-form__hint-box">
            <strong>{{ __('agricart.finance.expense_payments.fields.category') }}:</strong>
            {{ $selectedExpense->category?->displayName() ?? '—' }}
            @if (filled($selectedExpense->information))
                · <strong>{{ __('agricart.finance.expenses.fields.information') }}:</strong>
                {{ \Illuminate\Support\Str::limit($selectedExpense->information, 160) }}
            @endif
        </p>
    @endif

    <div class="agricart-form-panel__field agricart-finance-expense-payment-form__notes">
        <label class="agricart-form-panel__label" for="expay-notes">{{ __('agricart.finance.expense_payments.fields.notes') }}</label>
        <x-filament::input.wrapper>
            <textarea
                id="expay-notes"
                wire:model="notes"
                class="fi-input agricart-form-panel__textarea"
                rows="3"
                dir="ltr"
                @disabled($readonly)
                placeholder="{{ __('agricart.finance.expense_payments.fields.notes_placeholder') }}"
            ></textarea>
        </x-filament::input.wrapper>
        @error('notes') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    @if ($paymentLocked)
        <p class="agricart-form-panel__hint agricart-finance-expense-payment-form__hint-box">{{ __('agricart.finance.expense_payments.hints.payment_locked') }}</p>
    @endif
</div>
