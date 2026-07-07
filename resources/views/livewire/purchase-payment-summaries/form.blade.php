<div
    class="agricart-planning-form agricart-payment-summary-form"
    x-on:purchase-payment-summary-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:purchase-payment-summary-form-open.window="$wire.openForm($event.detail.summaryId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-planning-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-planning-form__meta">
        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label">{{ __('purchase_payment_summary.fields.summary_number') }}</label>
            <input type="text" class="agricart-planning-form__input" value="{{ $summary_number ?: __('purchase_payment_summary.hints.auto_on_save') }}" readonly disabled />
        </div>

        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label" for="payment-summary-date">{{ __('purchase_payment_summary.fields.summary_date') }}</label>
            <input id="payment-summary-date" type="date" class="agricart-planning-form__input" wire:model.live="summary_date" @disabled($this->isReadonly()) />
        </div>

        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label" for="payment-summary-paper">{{ __('purchase_payment_summary.fields.paper_size') }}</label>
            <select id="payment-summary-paper" class="agricart-planning-form__input" wire:model.live="paper_size" @disabled($this->isReadonly())>
                @foreach ($paperSizeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label" for="payment-summary-locale">{{ __('purchase_payment_summary.fields.locale') }}</label>
            <select id="payment-summary-locale" class="agricart-planning-form__input" wire:model.live="locale" @disabled($this->isReadonly())>
                @foreach ($localeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <section class="agricart-payment-summary-form__section">
        <div class="agricart-payment-summary-form__section-head">
            <h3>{{ __('purchase_payment_summary.sections.suppliers') }}</h3>
            @unless ($this->isReadonly())
                <button type="button" class="agricart-planning-form__search-btn" wire:click="addManualRow">
                    {{ __('purchase_payment_summary.actions.add_manual') }}
                </button>
            @endunless
        </div>
        <p class="agricart-form-panel__hint">{{ __('purchase_payment_summary.hints.manual_row') }}</p>

        @if ($rows === [])
            <p class="agricart-planning-form__empty">{{ __('purchase_payment_summary.form.empty') }}</p>
        @else
            <div class="agricart-payment-summary-form__table-wrap">
                <table class="agricart-payment-summary-form__table">
                    <thead>
                        <tr>
                            <th>{{ __('purchase_payment_summary.columns.supplier') }}</th>
                            <th>{{ __('purchase_payment_summary.fields.phone') }}</th>
                            <th>{{ __('purchase_payment_summary.fields.address') }}</th>
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
                            <tr wire:key="payment-summary-row-{{ $index }}-{{ $row['supplier_id'] ?? 'manual' }}">
                                <td class="agricart-payment-summary-form__supplier">
                                    @if ($row['is_manual'] ?? false)
                                        @if ($this->isReadonly())
                                            {{ $locale === 'ur' ? ($row['name_ur'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ur']) }}
                                        @else
                                            <input type="text" class="agricart-planning-form__input" wire:model.live="rows.{{ $index }}.name_en" placeholder="{{ __('purchase_payment_summary.fields.supplier') }} (EN)" />
                                            <input type="text" class="agricart-planning-form__input" wire:model.live="rows.{{ $index }}.name_ur" placeholder="{{ __('purchase_payment_summary.fields.supplier') }} (UR)" dir="rtl" />
                                        @endif
                                    @else
                                        <strong>{{ $locale === 'ur' ? ($row['name_ur'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ur']) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['is_manual'] ?? false)
                                        <input type="text" class="agricart-planning-form__input" wire:model.live="rows.{{ $index }}.phone" @disabled($this->isReadonly()) />
                                    @else
                                        {{ $row['phone'] ?: '—' }}
                                    @endif
                                </td>
                                <td class="agricart-payment-summary-form__address">
                                    @if ($row['is_manual'] ?? false)
                                        <input type="text" class="agricart-planning-form__input" wire:model.live="rows.{{ $index }}.address" @disabled($this->isReadonly()) />
                                    @else
                                        {{ $row['address'] ?: '—' }}
                                    @endif
                                </td>
                                <td>
                                    <input type="text" class="agricart-planning-form__input" wire:model.live="rows.{{ $index }}.payment_amount" @disabled($this->isReadonly()) />
                                </td>
                                <td class="agricart-payment-summary-form__check">
                                    <input type="checkbox" wire:click="setLineStatus({{ $index }}, 'ok')" @checked(($row['line_status'] ?? '') === 'ok') @disabled($this->isReadonly()) />
                                </td>
                                <td class="agricart-payment-summary-form__check">
                                    <input type="checkbox" wire:click="setLineStatus({{ $index }}, 'dispute')" @checked(($row['line_status'] ?? '') === 'dispute') @disabled($this->isReadonly()) />
                                </td>
                                @unless ($this->isReadonly())
                                    <td>
                                        @if ($row['is_manual'] ?? false)
                                            <button type="button" class="agricart-planning-form__remove" wire:click="removeRow({{ $index }})">{{ __('purchase_payment_summary.actions.remove') }}</button>
                                        @endif
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="agricart-payment-summary-form__section">
        <h3>{{ __('purchase_payment_summary.sections.sources') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('purchase_payment_summary.hints.sources') }}</p>
        <div class="agricart-payment-summary-form__sources">
            @foreach ($paymentSources as $sourceIndex => $source)
                <div class="agricart-payment-summary-form__source-row" wire:key="payment-source-{{ $sourceIndex }}">
                    <input type="text" class="agricart-planning-form__input" wire:model.live="paymentSources.{{ $sourceIndex }}.label" placeholder="{{ __('purchase_payment_summary.fields.source_label') }}" @disabled($this->isReadonly()) />
                    <input type="text" class="agricart-planning-form__input" wire:model.live="paymentSources.{{ $sourceIndex }}.amount" placeholder="{{ __('purchase_payment_summary.fields.source_amount') }}" @disabled($this->isReadonly()) />
                </div>
            @endforeach
        </div>
    </section>

    <div class="agricart-planning-form__meta">
        <div class="agricart-planning-form__field agricart-planning-form__field--wide">
            <label class="agricart-planning-form__label" for="payment-summary-notes">{{ __('purchase_payment_summary.fields.notes') }}</label>
            <textarea id="payment-summary-notes" rows="3" class="agricart-planning-form__input agricart-planning-form__textarea" wire:model.live="notes" placeholder="{{ __('purchase_payment_summary.hints.notes') }}" @disabled($this->isReadonly())></textarea>
        </div>
    </div>
</div>
