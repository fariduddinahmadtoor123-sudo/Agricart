@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div
    class="agricart-purchase-form agricart-finance-bank-form"
    x-on:finance-bank-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:finance-bank-form-open.window="$wire.openForm($event.detail.bankAccountId ?? null, $event.detail.mode ?? 'create')"
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

    @if ($showExistingMeta && ! $isHistory)
        <p class="agricart-finance-bank-form__balance-inline">
            <span>{{ __('agricart.finance.banks.fields.current_balance') }}</span>
            <strong>{{ $money($balance) }}</strong>
        </p>
    @endif

    @if ($showAccountFields)
        <div class="agricart-finance-bank-form__stack">
            {{-- قطار 1: بینک سرچ (یا منتخب بینک EN/UR) --}}
            <div class="agricart-finance-bank-form__row" style="display:flex;flex-wrap:nowrap;align-items:flex-end;gap:0.65rem;width:100%;">
                @if (filled($bank_key))
                    <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                        <span class="agricart-finance-bank-form__label">{{ __('agricart.finance.banks.fields.bank_name_en') }}</span>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $selectedBankNameEn }}" class="fi-input" readonly @disabled($readonly) />
                        </x-filament::input.wrapper>
                    </div>
                    <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                        <span class="agricart-finance-bank-form__label">{{ __('agricart.finance.banks.fields.bank_name_ur') }}</span>
                        <x-filament::input.wrapper>
                            <input type="text" value="{{ $selectedBankNameUr }}" dir="rtl" class="fi-input" readonly @disabled($readonly) />
                        </x-filament::input.wrapper>
                    </div>
                    @if ($canEdit)
                        <button type="button" class="agricart-company-profile-form__mini-btn is-remove" style="flex:0 0 2rem;margin-bottom:0.2rem;" wire:click="clearBank" title="{{ __('agricart.form_panel.actions.cancel') }}">×</button>
                    @endif
                @else
                    <div class="agricart-supplier-form__bank-picker agricart-supplier-form__bank-picker--search" style="flex:1 1 100%;min-width:0;position:relative;">
                        <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                            <input
                                type="search"
                                wire:model.live.debounce.200ms="bankSearch"
                                class="fi-input fi-input-has-inline-prefix"
                                placeholder="{{ __('agricart.finance.banks.hints.bank_search') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                        @if (filled(trim($bankSearch)) && $filteredBanks !== [])
                            <div class="agricart-category-form__picker-results" role="listbox">
                                @foreach ($filteredBanks as $bankKey => $bankLabel)
                                    <button type="button" class="agricart-category-form__picker-result" wire:click="selectBank(@js($bankKey))" wire:key="finance-bank-opt-{{ $bankKey }}" role="option" @disabled($readonly)>
                                        {{ $bankLabel }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- قطار 2: اکاؤنٹ ٹائٹل EN + UR --}}
            <div class="agricart-finance-bank-form__row" style="display:flex;flex-wrap:nowrap;align-items:flex-end;gap:0.65rem;width:100%;">
                <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                    <label class="agricart-finance-bank-form__label" for="bank-account-title">{{ __('agricart.finance.banks.fields.account_title') }}</label>
                    <x-filament::input.wrapper>
                        <input id="bank-account-title" type="text" class="fi-input" wire:model="account_title" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                    <label class="agricart-finance-bank-form__label" for="bank-account-title-ur">{{ __('agricart.finance.banks.fields.account_title_ur') }}</label>
                    <x-filament::input.wrapper>
                        <input id="bank-account-title-ur" type="text" dir="rtl" class="fi-input" wire:model="account_title_ur" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
            </div>
            @error('account_title') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror

            {{-- قطار 3: قسم + نمبر، سٹیٹس --}}
            <div class="agricart-finance-bank-form__row" style="display:flex;flex-wrap:nowrap;align-items:flex-end;gap:0.65rem;width:100%;">
                <div style="flex:0 1 9.5rem;min-width:6.5rem;display:flex;flex-direction:column;gap:0.3rem;">
                    <label class="agricart-finance-bank-form__label" for="bank-identifier-type">{{ __('agricart.finance.banks.fields.identifier_type') }}</label>
                    <x-filament::input.wrapper>
                        <select id="bank-identifier-type" class="fi-select-input" wire:model.live="account_identifier_type" @disabled($readonly)>
                            @foreach ($identifierTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
                <div style="flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:0.3rem;">
                    <label class="agricart-finance-bank-form__label" for="bank-identifier-value">{{ __('agricart.finance.banks.fields.identifier_value') }}</label>
                    <x-filament::input.wrapper>
                        <input id="bank-identifier-value" type="text" class="fi-input" wire:model="account_identifier" @disabled($readonly) />
                    </x-filament::input.wrapper>
                </div>
                <div style="flex:0 1 7.5rem;min-width:5.5rem;display:flex;flex-direction:column;gap:0.3rem;">
                    <label class="agricart-finance-bank-form__label" for="bank-status">{{ __('agricart.finance.banks.fields.status') }}</label>
                    <x-filament::input.wrapper>
                        <select id="bank-status" class="fi-select-input" wire:model="status" @disabled($readonly)>
                            <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                            <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>
            </div>
            @error('account_identifier') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
            @error('account_number') <p class="agricart-form-panel__error agricart-finance-bank-form__error">{{ $message }}</p> @enderror
        </div>
    @endif

    @if ($isHistory)
        <section class="agricart-purchase-form__section">
            <p class="agricart-finance-bank-form__balance-inline">
                <span>{{ __('agricart.finance.banks.fields.current_balance') }}</span>
                <strong>{{ $money($balance) }}</strong>
            </p>

            <div class="agricart-purchase-form__grid agricart-purchase-form__grid--2 agricart-finance-bank-form__history-filters">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="history-from">{{ __('agricart.finance.banks.fields.history_from') }}</label>
                    <x-filament::input.wrapper>
                        <input id="history-from" type="date" class="fi-input" wire:model.live="historyFrom" />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="history-to">{{ __('agricart.finance.banks.fields.history_to') }}</label>
                    <x-filament::input.wrapper>
                        <input id="history-to" type="date" class="fi-input" wire:model.live="historyTo" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <h2 class="agricart-purchase-form__section-title">{{ __('agricart.finance.banks.sections.history') }}</h2>
            @if ($historyTransactions->isNotEmpty())
                <div class="agricart-categories__table-wrap">
                    <table class="agricart-categories__table agricart-finance-bank-form__ledger">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.finance.banks.ledger.col_date') }}</th>
                                <th>{{ __('agricart.finance.banks.ledger.col_description') }}</th>
                                <th>{{ __('agricart.finance.banks.ledger.col_debit') }}</th>
                                <th>{{ __('agricart.finance.banks.ledger.col_credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historyTransactions as $tx)
                                <tr wire:key="bank-tx-{{ $tx->id }}">
                                    <td>{{ $tx->transaction_date?->format('M j, Y') }}</td>
                                    <td>{{ $tx->description ?: __('agricart.finance.banks.ledger.types.' . $tx->type) }}</td>
                                    <td>
                                        @if ($tx->direction === 'debit')
                                            <span class="agricart-staff-advances__amount is-debit">{{ $money((float) $tx->amount) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($tx->direction === 'credit')
                                            <span class="agricart-staff-advances__amount is-credit">{{ $money((float) $tx->amount) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="agricart-staff-pay__muted">{{ __('agricart.finance.banks.ledger.empty') }}</p>
            @endif
        </section>
    @endif
</div>
