@php
    $readonly = $this->isReadonly();
    $paymentLocked = $this->paymentLocked();
@endphp

<div class="agricart-form-panel__fields agricart-finance-expense-payment-form">
    @if ($statusMessage)
        <div @class([
            'agricart-form-panel__alert',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.payment_number') }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="payment_number" class="fi-input" readonly disabled />
        </x-filament::input.wrapper>
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.expense') }} *</label>
        <x-filament::input.wrapper>
            <select wire:model.live="expense_id" class="fi-input" @disabled($readonly || $paymentLocked)>
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

    @if ($selectedExpense)
        <div class="agricart-form-panel__field agricart-form-panel__field--full">
            <p class="agricart-form-panel__hint">
                <strong>{{ __('agricart.finance.expense_payments.fields.category') }}:</strong>
                {{ $selectedExpense->category?->displayName() ?? '—' }}
                @if (filled($selectedExpense->information))
                    <br><strong>{{ __('agricart.finance.expenses.fields.information') }}:</strong>
                    {{ \Illuminate\Support\Str::limit($selectedExpense->information, 160) }}
                @endif
            </p>
        </div>
    @endif

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.amount') }} *</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="amount" class="fi-input" @disabled($readonly || $paymentLocked) />
        </x-filament::input.wrapper>
        @error('amount') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.payment_date') }} *</label>
        <x-filament::input.wrapper>
            <input type="date" wire:model="payment_date" class="fi-input" @disabled($readonly || $paymentLocked) />
        </x-filament::input.wrapper>
        @error('payment_date') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.payment_time') }}</label>
        <x-filament::input.wrapper>
            <input type="time" wire:model="payment_time" class="fi-input" @disabled($readonly || $paymentLocked) />
        </x-filament::input.wrapper>
        @error('payment_time') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.source_wallet') }} *</label>
        <x-filament::input.wrapper>
            <select wire:model.live="source_wallet" class="fi-input" @disabled($readonly || $paymentLocked)>
                <option value="cash">{{ __('agricart.finance.expense_payments.wallets.cash') }}</option>
                <option value="bank">{{ __('agricart.finance.expense_payments.wallets.bank') }}</option>
            </select>
        </x-filament::input.wrapper>
        @error('source_wallet') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    @if ($source_wallet === 'bank')
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.bank_account') }} *</label>
            <x-filament::input.wrapper>
                <select wire:model="bank_account_id" class="fi-input" @disabled($readonly || $paymentLocked)>
                    <option value="">{{ __('agricart.finance.expense_payments.fields.bank_account_none') }}</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->displayName() }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>
            @error('bank_account_id') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    @endif

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_payments.fields.notes') }}</label>
        <x-filament::input.wrapper>
            <textarea wire:model="notes" class="fi-input agricart-form-panel__textarea" rows="2" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
        @error('notes') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    @if ($paymentLocked)
        <p class="agricart-form-panel__hint agricart-form-panel__field--full">{{ __('agricart.finance.expense_payments.hints.payment_locked') }}</p>
    @endif
</div>
