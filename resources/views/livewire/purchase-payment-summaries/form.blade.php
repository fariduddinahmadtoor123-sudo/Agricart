@php use Filament\Support\Icons\Heroicon; @endphp

<div
    class="agricart-purchase-form agricart-payment-summary-form"
    x-on:purchase-payment-summary-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:purchase-payment-summary-form-open.window="$wire.openForm($event.detail.summaryId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <header class="agricart-purchase-form__banner agricart-payment-summary-form__banner">
        <div class="agricart-payment-summary-form__meta-row">
            <div class="agricart-payment-summary-form__meta-chip">
                <span class="agricart-payment-summary-form__meta-label">{{ __('purchase_payment_summary.fields.summary_number') }}</span>
                <strong class="agricart-payment-summary-form__meta-value">{{ $summary_number ?: __('purchase_payment_summary.hints.auto_on_save') }}</strong>
            </div>
            <div class="agricart-payment-summary-form__meta-chip">
                <span class="agricart-payment-summary-form__meta-label">{{ __('purchase_payment_summary.fields.summary_date') }}</span>
                @if ($this->isReadonly())
                    <strong class="agricart-payment-summary-form__meta-value">{{ $formattedSummaryDate }}</strong>
                @else
                    <input type="date" class="agricart-payment-summary-form__meta-input" wire:model.live="summary_date" />
                @endif
            </div>
            <div class="agricart-payment-summary-form__meta-chip">
                <span class="agricart-payment-summary-form__meta-label">{{ __('purchase_payment_summary.fields.paper_size') }}</span>
                @if ($this->isReadonly())
                    <strong class="agricart-payment-summary-form__meta-value">{{ __('purchase_payment_summary.paper_sizes.' . $paper_size) }}</strong>
                @else
                    <select class="agricart-payment-summary-form__meta-input" wire:model.live="paper_size">
                        @foreach ($paperSizeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="agricart-payment-summary-form__meta-chip">
                <span class="agricart-payment-summary-form__meta-label">{{ __('purchase_payment_summary.fields.locale') }}</span>
                @if ($this->isReadonly())
                    <strong class="agricart-payment-summary-form__meta-value">{{ __('purchase_payment_summary.locales.' . $locale) }}</strong>
                @else
                    <select class="agricart-payment-summary-form__meta-input" wire:model.live="locale">
                        @foreach ($localeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="agricart-payment-summary-form__meta-chip">
                <span class="agricart-payment-summary-form__meta-label">{{ __('purchase_payment_summary.fields.purchaser') }}</span>
                @if ($this->isReadonly())
                    <strong class="agricart-payment-summary-form__meta-value">{{ $purchaserOptions[$purchaser_id] ?? '—' }}</strong>
                @else
                    <select class="agricart-payment-summary-form__meta-input" wire:model.live="purchaser_id">
                        <option value="">{{ __('purchase_payment_summary.hints.select_purchaser') }}</option>
                        @foreach ($purchaserOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <div class="agricart-payment-summary-form__store-block">
            <p class="agricart-payment-summary-form__store-name">{{ $storeName ?: '—' }}</p>
            @if (filled($storeAddress) || filled($storePhone))
                <p class="agricart-payment-summary-form__store-meta">
                    @if (filled($storeAddress))
                        <span>{{ $storeAddress }}</span>
                    @endif
                    @if (filled($storePhone))
                        <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                        <span>{{ $storePhone }}</span>
                    @endif
                </p>
            @endif
            <p class="agricart-payment-summary-form__store-meta">
                <span>{{ __('purchase_payment_summary.fields.created_by') }}:</span>
                <strong>{{ $creatorName }}</strong>
            </p>
        </div>
    </header>

    @unless ($this->isReadonly())
        <div class="agricart-purchase-form__search">
            <div class="agricart-purchase-form__search-row">
                <label class="agricart-purchase-form__search-label" for="payment-summary-supplier-search">{{ __('purchase_payment_summary.fields.supplier_search') }}</label>
                <input
                    id="payment-summary-supplier-search"
                    type="search"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model.live.debounce.250ms="supplier_search"
                    placeholder="{{ __('purchase_payment_summary.hints.supplier_search') }}"
                    autocomplete="off"
                />
                <button
                    type="button"
                    class="agricart-payment-summary-form__icon-btn"
                    wire:click="addManualRow"
                    title="{{ __('purchase_payment_summary.actions.add_manual') }}"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedPlus" class="agricart-payment-summary-form__icon" />
                </button>
            </div>
            @if ($supplierResults !== [])
                <ul class="agricart-purchase-form__results">
                    @foreach ($supplierResults as $result)
                        <li wire:key="payment-summary-supplier-{{ $result['id'] }}">
                            <button type="button" class="agricart-purchase-form__result" wire:click="addSupplier({{ $result['id'] }})">
                                {{ $result['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endunless

    @if ($rows === [])
        <p class="agricart-payment-summary-form__empty">{{ __('purchase_payment_summary.form.empty') }}</p>
    @else
        <div class="agricart-purchase-form__grid-wrap">
            <table class="agricart-purchase-form__grid agricart-payment-summary-form__grid">
                <thead>
                    <tr>
                        <th>{{ __('purchase_payment_summary.columns.sr') }}</th>
                        <th>{{ __('purchase_payment_summary.columns.supplier') }}</th>
                        <th>{{ __('purchase_payment_summary.columns.payment') }}</th>
                        <th>{{ __('purchase_payment_summary.columns.ok') }}</th>
                        <th>{{ __('purchase_payment_summary.columns.dispute') }}</th>
                        @unless ($this->isReadonly())
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="payment-summary-row-{{ $index }}-{{ $row['supplier_id'] ?? 'manual-' . $index }}">
                            <td>{{ $index + 1 }}</td>
                            <td class="agricart-payment-summary-form__party">
                                @if ($row['is_manual'] ?? false)
                                    @if ($this->isReadonly())
                                        <span class="agricart-payment-summary-form__party-name">{{ $this->rowDisplayName($row) }}</span>
                                    @else
                                        <input
                                            type="text"
                                            class="agricart-purchase-form__cell-input agricart-payment-summary-form__party-input"
                                            wire:model.live="rows.{{ $index }}.name_en"
                                            placeholder="{{ __('purchase_payment_summary.hints.manual_name') }}"
                                        />
                                    @endif
                                @else
                                    <span class="agricart-payment-summary-form__party-name">{{ $this->rowDisplayName($row) }}</span>
                                    @if (filled($this->rowPartyMeta($row)))
                                        <span class="agricart-payment-summary-form__party-meta">{{ $this->rowPartyMeta($row) }}</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-purchase-form__cell-input"
                                    wire:model="rows.{{ $index }}.payment_amount"
                                    wire:blur="encodeRowPayment({{ $index }})"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="checkbox"
                                    class="agricart-payment-summary-form__check"
                                    wire:click="setLineStatus({{ $index }}, 'ok')"
                                    @checked(($row['line_status'] ?? '') === 'ok')
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="checkbox"
                                    class="agricart-payment-summary-form__check"
                                    wire:click="setLineStatus({{ $index }}, 'dispute')"
                                    @checked(($row['line_status'] ?? '') === 'dispute')
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            @unless ($this->isReadonly())
                                <td>
                                    <button type="button" class="agricart-purchase-form__remove" wire:click="removeRow({{ $index }})">{{ __('purchase_payment_summary.actions.remove') }}</button>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <section class="agricart-payment-summary-form__sources-section">
        <div class="agricart-payment-summary-form__sources-header">
            <span class="agricart-form-panel__label">{{ __('purchase_payment_summary.fields.source_label') }}</span>
            <span class="agricart-form-panel__label">{{ __('purchase_payment_summary.fields.source_amount') }}</span>
            <span class="agricart-payment-summary-form__sources-header-spacer" aria-hidden="true"></span>
        </div>

        @foreach ($paymentSources as $sourceIndex => $source)
            <div class="agricart-payment-summary-form__source-row" wire:key="payment-source-{{ $sourceIndex }}">
                <input
                    type="text"
                    class="agricart-purchase-form__input agricart-payment-summary-form__source-input"
                    wire:model.live.debounce.300ms="paymentSources.{{ $sourceIndex }}.label"
                    placeholder="{{ __('purchase_payment_summary.fields.source_label') }}"
                    @disabled($this->isReadonly())
                />
                <input
                    type="text"
                    class="agricart-purchase-form__input agricart-payment-summary-form__source-input"
                    wire:model="paymentSources.{{ $sourceIndex }}.amount"
                    wire:blur="encodeSourcePayment({{ $sourceIndex }})"
                    placeholder="{{ __('purchase_payment_summary.fields.source_amount') }}"
                    @disabled($this->isReadonly())
                />
                @unless ($this->isReadonly())
                    <div class="agricart-payment-summary-form__source-actions">
                        @if (count($paymentSources) > 1)
                            <button
                                type="button"
                                class="agricart-product-form__icon-btn agricart-product-form__icon-btn--muted"
                                wire:click="removePaymentSource({{ $sourceIndex }})"
                                title="{{ __('purchase_payment_summary.actions.remove') }}"
                            >
                                <x-filament::icon :icon="Heroicon::OutlinedMinus" />
                            </button>
                        @endif
                        @if ($loop->last && $canAddPaymentSource)
                            <button
                                type="button"
                                class="agricart-product-form__icon-btn"
                                wire:click="addPaymentSource"
                                title="{{ __('purchase_payment_summary.actions.add_source') }}"
                            >
                                <x-filament::icon :icon="Heroicon::OutlinedPlus" />
                            </button>
                        @endif
                    </div>
                @else
                    <span class="agricart-payment-summary-form__sources-header-spacer" aria-hidden="true"></span>
                @endunless
            </div>
        @endforeach
    </section>

    <div class="agricart-purchase-form__totals agricart-payment-summary-form__totals">
        <div>
            <span>{{ __('purchase_payment_summary.totals.supplier_payments') }}</span>
            <strong>{{ filled($totalSupplierPayments) ? $totalSupplierPayments : '—' }}</strong>
        </div>
        <div>
            <span>{{ __('purchase_payment_summary.totals.source_payments') }}</span>
            <strong>{{ filled($totalSourcePayments) ? $totalSourcePayments : '—' }}</strong>
        </div>
        <div>
            <span>{{ __('purchase_payment_summary.totals.balance') }}</span>
            <strong>{{ filled($balanceRemaining) ? $balanceRemaining : '—' }}</strong>
        </div>
    </div>

    <section class="agricart-payment-summary-form__notes">
        <label class="agricart-purchase-form__label" for="payment-summary-notes">{{ __('purchase_payment_summary.fields.notes') }}</label>
        <textarea
            id="payment-summary-notes"
            class="agricart-payment-summary-form__notes-input"
            wire:model.live.debounce.300ms="notes"
            rows="3"
            placeholder="{{ __('purchase_payment_summary.hints.notes') }}"
            @disabled($this->isReadonly())
        ></textarea>
    </section>
</div>
