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
                    <input type="date" class="agricart-payment-summary-form__meta-input" wire:model.live="transaction_date" />
                </div>
                <div class="agricart-payment-summary-form__meta-chip">
                    <span class="agricart-payment-summary-form__meta-label">{{ __('agricart.finance.fund_transfers.fields.time') }}</span>
                    <input type="time" class="agricart-payment-summary-form__meta-input" wire:model.live="transaction_time" />
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
                    <select id="fund-transfer-type" class="fi-select-input" wire:model.live="transaction_type">
                        @foreach ($transactionTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
                @error('transaction_type') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
            </div>

            <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                <span class="agricart-finance-bank-form__label">{{ __('agricart.finance.fund_transfers.sections.source') }}</span>
                @if ($this->sourceIsLocked())
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" readonly value="{{ __('agricart.finance.fund_transfers.source_wallets.cash') }}" />
                    </x-filament::input.wrapper>
                @elseif ($transaction_type === \App\Livewire\Finance\FundTransferForm::TYPE_TRANSFER)
                    <x-filament::input.wrapper>
                        <select id="fund-source-bank" class="fi-select-input" wire:model.live="source_bank_account_id">
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
                            <select id="fund-source-wallet" class="fi-select-input" wire:model.live="source_wallet">
                                @foreach ($sourceWalletOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                        @if ($this->requiresSourceBank())
                            <x-filament::input.wrapper>
                                <select id="fund-source-bank-pay" class="fi-select-input" wire:model.live="source_bank_account_id">
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
                        <select id="fund-dest-bank" class="fi-select-input" wire:model.live="destination_bank_account_id">
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_bank') }}</option>
                            @foreach ($destinationBankOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_bank_account_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsVendor())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-vendor" class="fi-select-input" wire:model.live="destination_supplier_id">
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_vendor') }}</option>
                            @foreach ($supplierOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_supplier_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @elseif ($this->destinationIsPurchaser())
                    <x-filament::input.wrapper>
                        <select id="fund-dest-purchaser" class="fi-select-input" wire:model.live="destination_purchaser_id">
                            <option value="">{{ __('agricart.finance.fund_transfers.placeholders.select_purchaser') }}</option>
                            @foreach ($purchaserOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                    @error('destination_purchaser_id') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
                @endif
            </div>
        </div>

        <div class="agricart-purchase-form__grid agricart-purchase-form__grid--2">
            <div>
                <label class="agricart-finance-bank-form__label" for="fund-amount">{{ __('agricart.finance.fund_transfers.fields.amount') }}</label>
                <x-filament::input.wrapper>
                    <input id="fund-amount" type="text" inputmode="decimal" class="fi-input" wire:model="amount" placeholder="0.00" />
                </x-filament::input.wrapper>
                @error('amount') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="agricart-finance-bank-form__label" for="fund-notes">{{ __('agricart.finance.fund_transfers.fields.notes') }}</label>
                <x-filament::input.wrapper>
                    <input id="fund-notes" type="text" class="fi-input" wire:model="notes" />
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>
</div>
