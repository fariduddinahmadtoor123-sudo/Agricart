@php
    $readonly = $this->isReadonly();
    $transferLocked = $this->transferLocked();
    $coreDisabled = $readonly || $transferLocked;
@endphp

<div
    class="agricart-purchase-form agricart-finance-bank-form agricart-finance-fund-transfer-form"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-finance-bank-form__stack">
        <header class="agricart-payment-summary-form__banner agricart-finance-fund-transfer-form__banner">
            <div class="agricart-payment-summary-form__meta-row">
                <div class="agricart-payment-summary-form__meta-chip">
                    <span class="agricart-payment-summary-form__meta-label">{{ __('agricart.finance.fund_transfers.fields.transaction_id') }}</span>
                    <strong class="agricart-payment-summary-form__meta-value">{{ $transaction_number ?: __('agricart.finance.fund_transfers.hints.auto_on_save') }}</strong>
                </div>
                <div class="agricart-payment-summary-form__meta-chip">
                    <span class="agricart-payment-summary-form__meta-label">{{ __('agricart.finance.fund_transfers.fields.date') }}</span>
                    <input type="date" class="agricart-payment-summary-form__meta-input" wire:model.live="transaction_date" @disabled($coreDisabled) />
                </div>
                <div class="agricart-payment-summary-form__meta-chip">
                    <span class="agricart-payment-summary-form__meta-label">{{ __('agricart.finance.fund_transfers.fields.time') }}</span>
                    <input type="time" class="agricart-payment-summary-form__meta-input" wire:model.live="transaction_time" @disabled($coreDisabled) />
                </div>
            </div>
        </header>

        <div
            class="agricart-finance-bank-form__row agricart-finance-fund-transfer-form__type-row"
            style="display:flex;flex-wrap:nowrap;align-items:flex-end;gap:0.65rem;width:100%;"
        >
            <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                <label class="agricart-finance-bank-form__label" for="fund-transfer-type">{{ __('agricart.finance.fund_transfers.fields.transaction_type') }}</label>
                <x-filament::input.wrapper>
                    <select id="fund-transfer-type" class="fi-select-input" wire:model.live="transaction_type" @disabled($coreDisabled)>
                        @foreach ($transactionTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
                @error('transaction_type') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
            </div>

            <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                <span class="agricart-finance-bank-form__label">{{ $this->sourceWalletLabel() }}</span>
                @if ($this->sourceIsExternal())
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" readonly value="{{ __('agricart.finance.fund_transfers.source_wallets.external') }}" />
                    </x-filament::input.wrapper>
                @elseif ($this->sourceIsLocked())
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" readonly value="{{ __('agricart.finance.fund_transfers.source_wallets.cash') }}" />
                    </x-filament::input.wrapper>
                @elseif ($this->sourceIsBankLocked())
                    <x-filament::input.wrapper>
                        <select id="fund-source-bank" class="fi-select-input" wire:model.live="source_bank_account_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_bank') }}</option>
                            @foreach ($bankAccountOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('source_bank_account_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @else
                    <div class="agricart-finance-fund-transfer-form__col-stack">
                        <x-filament::input.wrapper>
                            <select id="fund-source-wallet" class="fi-select-input" wire:model.live="source_wallet" @disabled($coreDisabled)>
                                @foreach ($sourceWalletOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                        @if ($this->requiresSourceBank())
                            <x-filament::input.wrapper>
                                <select id="fund-source-bank-pay" class="fi-select-input" wire:model.live="source_bank_account_id" @disabled($coreDisabled)>
                                    <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_bank') }}</option>
                                    @foreach ($bankAccountOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-filament::input.wrapper>
                            @error('source_bank_account_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                        @endif
                    </div>
                @endif
            </div>

            <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                <span class="agricart-finance-bank-form__label">{{ __('agricart.finance.fund_transfers.sections.destination') }}</span>
                @if ($this->requiresDestinationBank())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-bank" class="fi-select-input" wire:model.live="destination_bank_account_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_bank') }}</option>
                            @foreach ($destinationBankOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_bank_account_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsVendor())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-vendor" class="fi-select-input" wire:model.live="destination_supplier_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_vendor') }}</option>
                            @foreach ($supplierOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_supplier_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsCustomer())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-customer" class="fi-select-input" wire:model.live="destination_customer_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_customer') }}</option>
                            @foreach ($customerOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_customer_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsExpense())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-expense" class="fi-select-input" wire:model.live="destination_expense_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_expense') }}</option>
                            @foreach ($expenseOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_expense_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsPurchaser())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-purchaser" class="fi-select-input" wire:model.live="destination_purchaser_id" @disabled($coreDisabled)>
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_purchaser') }}</option>
                            @foreach ($purchaserOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_purchaser_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsCash())
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" readonly value="{{ __('agricart.finance.fund_transfers.source_wallets.cash') }}" />
                    </x-filament::input.wrapper>
                @elseif ($this->destinationIsExternal())
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" readonly value="{{ __('agricart.finance.fund_transfers.source_wallets.external') }}" />
                    </x-filament::input.wrapper>
                @endif
            </div>
        </div>
        <p class="agricart-form-panel__hint">{{ $this->typeHint() }}</p>

        <div class="agricart-purchase-form__grid agricart-purchase-form__grid--2">
            <div>
                <label class="agricart-finance-bank-form__label" for="fund-amount">{{ __('agricart.finance.fund_transfers.fields.amount') }}</label>
                <x-filament::input.wrapper>
                    <input id="fund-amount" type="text" inputmode="decimal" class="fi-input" wire:model="amount" placeholder="0.00" @disabled($coreDisabled) />
                </x-filament::input.wrapper>
                @error('amount') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
            </div>
            @if ($this->requiresHandledByName())
                <div>
                    <label class="agricart-finance-bank-form__label" for="fund-handled-by">{{ __('agricart.finance.fund_transfers.fields.handled_by_name') }} <span class="agricart-form-panel__required" aria-hidden="true">*</span></label>
                    <x-filament::input.wrapper>
                        <input id="fund-handled-by" type="text" class="fi-input" wire:model="handled_by_name" maxlength="120" placeholder="{{ __('agricart.finance.fund_transfers.placeholders.handled_by_name') }}" @disabled($coreDisabled) />
                    </x-filament::input.wrapper>
                    @error('handled_by_name') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="agricart-finance-bank-form__label" for="fund-notes">{{ __('agricart.finance.fund_transfers.fields.notes') }}</label>
                <x-filament::input.wrapper>
                    <input id="fund-notes" type="text" class="fi-input" wire:model="notes" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-form-panel__field agricart-finance-expense-form__bill">
            <span class="agricart-form-panel__label">{{ __('agricart.finance.fund_transfers.fields.bank_receipt') }}</span>
            <div class="agricart-purchase-form__bill-slot agricart-finance-expense-form__bill-slot">
                @if ($bankReceiptPreviewUrl)
                    <div class="agricart-purchase-form__bill-upload is-filled">
                        <img src="{{ $bankReceiptPreviewUrl }}" alt="" class="agricart-purchase-form__bill-preview" />
                        <div class="agricart-purchase-form__bill-overlay">
                            <a href="{{ $bankReceiptPreviewUrl }}" target="_blank" class="agricart-purchase-form__bill-overlay-btn" title="{{ __('agricart.finance.fund_transfers.actions.view_receipt') }}">
                                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedEye" class="agricart-purchase-form__bill-overlay-icon" />
                            </a>
                            @unless ($readonly)
                                <button type="button" class="agricart-purchase-form__bill-overlay-btn is-danger" wire:click="removeBankReceipt" title="{{ __('agricart.finance.fund_transfers.actions.remove_receipt') }}">
                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedTrash" class="agricart-purchase-form__bill-overlay-icon" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @elseif (! $readonly)
                    <label class="agricart-purchase-form__bill-upload" title="{{ __('agricart.finance.fund_transfers.actions.receipt_upload') }}">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentText" class="agricart-purchase-form__bill-icon" />
                        <span class="agricart-purchase-form__bill-label">{{ __('agricart.finance.fund_transfers.actions.receipt_upload') }}</span>
                        <input type="file" accept="image/*" wire:model="bankReceiptUpload" class="agricart-category-form__file-input" />
                    </label>
                    <div wire:loading wire:target="bankReceiptUpload" class="agricart-purchase-form__bill-loading">{{ __('agricart.finance.fund_transfers.actions.receipt_uploading') }}</div>
                @else
                    <p class="agricart-form-panel__hint">{{ __('agricart.finance.fund_transfers.hints.bank_receipt_empty') }}</p>
                @endif
            </div>
            @error('bankReceiptUpload') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
            <p class="agricart-form-panel__hint">{{ __('agricart.finance.fund_transfers.hints.bank_receipt') }}</p>
        </div>

        @if ($transferLocked && ! $readonly)
            <p class="agricart-form-panel__hint">{{ __('agricart.finance.fund_transfers.hints.transfer_locked') }}</p>
        @endif
    </div>
</div>
