<x-filament-panels::page>
    @include('filament.pages.tools.partials.pending-payments-styles')

    @if ($screen === 'form')
        @php
            $tLocale = $formLang === 'ur' ? 'ur' : 'en';
            $totals = $this->sheetTotals();
            $hNo = $formLang === 'ur' ? __('agricart.pages.tools.pp_grid_no_ur') : __('agricart.pages.tools.pp_grid_no_en');
            $hShop = $formLang === 'ur' ? __('agricart.pages.tools.pp_grid_shopkeeper_ur') : __('agricart.pages.tools.pp_grid_shopkeeper_en');
            $hOwed = $formLang === 'ur' ? __('agricart.pages.tools.pp_grid_owed_ur') : __('agricart.pages.tools.pp_grid_owed_en');
            $hPays = $formLang === 'ur' ? __('agricart.pages.tools.pp_grid_payments_ur') : __('agricart.pages.tools.pp_grid_payments_en');
            $hRem = $formLang === 'ur' ? __('agricart.pages.tools.pp_grid_remaining_ur') : __('agricart.pages.tools.pp_grid_remaining_en');
        @endphp

        @if ($payRowKey !== '')
            <div class="agricart-pp-paybox no-print" wire:key="pp-pay-{{ $payRowKey }}">
                <strong>{{ __('agricart.pages.tools.pp_pay_title') }}</strong>
                <span dir="ltr">#{{ $payRowKey }} — {{ $this->cellRow($payRowKey)['shopkeeper'] }}</span>
                <input type="text" wire:model="payDate" placeholder="{{ __('agricart.pages.tools.pp_pay_date') }}" dir="ltr" style="width:7rem" />
                <input type="text" wire:model="payAmount" placeholder="{{ __('agricart.pages.tools.pp_pay_amount') }}" dir="ltr" style="width:7rem" />
                <button type="button" class="agricart-pp-btn" wire:click="confirmPay">{{ __('agricart.pages.tools.pp_pay_confirm') }}</button>
                <button type="button" class="agricart-pp-btn" wire:click="cancelPayForm">{{ __('agricart.pages.tools.pp_pay_cancel') }}</button>
            </div>
        @endif

        <div
            class="agricart-pp-sheet-wrap"
            data-pp-shopkeepers='@json($this->shopkeeperOptions())'
            @if (! $this->isViewMode())
                x-data="toolsPpDraft(@js(['userId' => (int) auth()->id()]))"
            @else
                x-data
            @endif
            x-init="
                window.__agricartPpShopkeepers = JSON.parse($el.getAttribute('data-pp-shopkeepers') || '[]');
                window.dispatchEvent(new CustomEvent('tools-pending-payments-screen',{detail:{screen:'form',formMode:@js($this->isViewMode() ? 'view' : 'edit')}}));
            "
            wire:key="pp-form-{{ $shopkeepersTick }}-{{ $formMode }}-{{ $persistStatus }}-{{ $editingSheetId ?? 0 }}"
        >
            @if (! $this->isViewMode())
                @teleport('#agricart-pp-toolbar-slot')
                    <div class="agricart-ro-chrome print:hidden" dir="ltr" x-data="toolsPpEntry(@js(['lang' => $formLang]))" x-on:tools-pending-payments-lang-changed.window="setLang($event.detail.lang)">
                        <div class="agricart-pp-entry" style="flex:1 1 auto;width:100%" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                            <div class="agricart-pp-phrasepick" style="flex:1;min-width:0">
                                <input
                                    type="text"
                                    class="agricart-pp-entry__input"
                                    wire:model.live="entryShopkeeper"
                                    x-ref="entry"
                                    placeholder="{{ __('agricart.pages.tools.pp_entry_placeholder') }}"
                                    autocomplete="off"
                                    @if ($formLang === 'ur') dir="rtl" @endif
                                    x-on:input="onEntryType($event)"
                                    x-on:focus="onEntryType($event)"
                                    x-on:blur="onEntryBlur()"
                                    x-on:keydown.escape.prevent="phraseOpen = false"
                                    x-on:keydown.enter.prevent="onEntryEnter()"
                                    x-on:keydown.arrow-down.prevent="movePhrase(1)"
                                    x-on:keydown.arrow-up.prevent="movePhrase(-1)"
                                />
                                <template x-teleport="body">
                                    <div class="agricart-pp-phrasepick__menu" x-show="phraseOpen" x-cloak x-bind:style="entryMenuStyle()">
                                        <template x-for="(p, i) in entryFiltered" :key="'epp-'+i+'-'+p">
                                            <button type="button" class="agricart-pp-phrasepick__opt" :class="{ 'is-active': i === phraseActive }" x-on:mousedown.prevent="pickEntryPhrase(p)" x-text="p"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <input type="text" class="agricart-pp-entry__owed" x-ref="owed" wire:model.live="entryOwed" inputmode="decimal" placeholder="{{ __('agricart.pages.tools.pp_entry_owed') }}" dir="ltr" title="{{ __('agricart.pages.tools.pp_entry_owed') }}" />
                            <button type="button" class="agricart-pp-iconbtn" :class="{ 'is-listening': listening }" x-on:click="toggleMic()" title="{{ __('agricart.pages.tools.pp_entry_mic') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                            </button>
                            <button type="button" class="agricart-pp-iconbtn" x-on:click="copyEntry()" title="{{ __('agricart.pages.tools.pp_entry_copy') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <button type="button" class="agricart-pp-iconbtn" wire:click="clearEntry" title="{{ __('agricart.pages.tools.pp_entry_clear') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            </button>
                            <input type="text" class="agricart-pp-entry__fwd" x-ref="fwd" wire:model.live="forwardTo" inputmode="numeric" placeholder="#" title="{{ __('agricart.pages.tools.pp_entry_forward_to') }}" dir="ltr" x-on:keydown.enter.prevent="doForward()" />
                            <button type="button" class="agricart-pp-btn" x-on:click.prevent="doForward()">{{ __('agricart.pages.tools.pp_entry_forward') }}</button>
                        </div>
                    </div>
                @endteleport
            @endif

            @for ($page = 1; $page <= $pageCount; $page++)
                <div class="agricart-pp-sheet" wire:key="pp-page-{{ $page }}" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                    @if ($page === 1)
                        <header class="agricart-pp-head">
                            <h1 class="agricart-pp-head__name">{{ $this->shopName() }}</h1>
                            @if (filled($this->shopAddress()))
                                <p class="agricart-pp-head__addr">{{ $this->shopAddress() }}</p>
                            @endif
                            @if (count($this->shopPhones()) > 0)
                                <p class="agricart-pp-head__phones">{{ implode(' · ', $this->shopPhones()) }}</p>
                            @endif
                        </header>
                        <div class="agricart-pp-meta">
                            <div class="agricart-pp-meta__item">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_date', [], $tLocale) }}</span>
                                <span class="agricart-pp-meta__v" dir="ltr">{{ $sheetDate }}</span>
                            </div>
                            <div class="agricart-pp-meta__item">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_sheet_no', [], $tLocale) }}</span>
                                <span class="agricart-pp-meta__v" dir="ltr">{{ $sheetNo }}</span>
                            </div>
                            <div class="agricart-pp-meta__item">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_page', [], $tLocale) }}</span>
                                <span class="agricart-pp-meta__v" dir="ltr">{{ $page }} / {{ $pageCount }}</span>
                            </div>
                            <div class="agricart-pp-meta__item" style="margin-inline-start:auto;border-inline-end:0">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_prepared_by', [], $tLocale) }}</span>
                                <span class="agricart-pp-meta__v" @if ($formLang === 'ur') dir="rtl" @endif>{{ $preparedBy }}</span>
                            </div>
                        </div>
                    @else
                        <div class="agricart-pp-meta">
                            <div class="agricart-pp-meta__item">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_sheet_no') }}</span>
                                <span class="agricart-pp-meta__v" dir="ltr">{{ $sheetNo }}</span>
                            </div>
                            <div class="agricart-pp-meta__item">
                                <span class="agricart-pp-meta__k">{{ __('agricart.pages.tools.pp_form_page') }}</span>
                                <span class="agricart-pp-meta__v" dir="ltr">{{ $page }} / {{ $pageCount }}</span>
                            </div>
                        </div>
                    @endif

                    @php
                        $rowsPerCol = $this->rowsPerColForPage($page);
                        $pageBase = $this->pageBaseForPage($page);
                    @endphp

                    <div class="agricart-pp-body">
                        <table class="agricart-pp-grid">
                            <thead>
                                <tr>
                                    <th class="agricart-pp-grid__no">{{ $hNo }}</th>
                                    <th class="agricart-pp-grid__shop">{{ $hShop }}</th>
                                    <th class="agricart-pp-grid__owed">{{ $hOwed }}</th>
                                    <th class="agricart-pp-grid__pays">{{ $hPays }}</th>
                                    <th class="agricart-pp-grid__rem">{{ $hRem }}</th>
                                    @unless ($this->isViewMode())
                                        <th class="agricart-pp-grid__act no-print">{{ __('agricart.pages.tools.pp_grid_pay') }}</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody>
                                @for ($r = 0; $r < $rowsPerCol; $r++)
                                    @php
                                        $rowNo = $pageBase + $r + 1;
                                        $rowKey = (string) $rowNo;
                                        $cell = $this->cellRow($rowKey);
                                        $filled = $cell['shopkeeper'] !== '' || $cell['owed'] !== '';
                                    @endphp
                                    <tr wire:key="pp-row-{{ $page }}-{{ $rowNo }}">
                                        <td class="agricart-pp-grid__no">{{ $rowNo }}</td>
                                        <td class="agricart-pp-grid__shop">
                                            @if ($this->isViewMode())
                                                {{ $cell['shopkeeper'] }}
                                            @else
                                                <input type="text" class="agricart-pp-inp" wire:model.blur="cells.{{ $rowKey }}.shopkeeper" @if ($formLang === 'ur') dir="rtl" @endif />
                                            @endif
                                        </td>
                                        <td class="agricart-pp-grid__owed">
                                            @if ($this->isViewMode())
                                                <span dir="ltr">{{ $cell['owed'] }}</span>
                                            @else
                                                <input type="text" class="agricart-pp-inp" style="text-align:end" wire:model.blur="cells.{{ $rowKey }}.owed" dir="ltr" inputmode="decimal" />
                                            @endif
                                        </td>
                                        <td class="agricart-pp-grid__pays" dir="ltr">{{ $this->paymentsLabel($cell) }}</td>
                                        <td class="agricart-pp-grid__rem" dir="ltr">{{ $filled ? $this->rowRemainingDisplay($cell) : '' }}</td>
                                        @unless ($this->isViewMode())
                                            <td class="agricart-pp-grid__act no-print">
                                                @if ($filled)
                                                    <button type="button" class="agricart-pp-btn" wire:click="openPayForm('{{ $rowKey }}')">{{ __('agricart.pages.tools.pp_pay') }}</button>
                                                @endif
                                            </td>
                                        @endunless
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    @if ($page === $pageCount)
                        <div class="agricart-pp-totals" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                            <span>
                                <span class="agricart-pp-totals__k">{{ __('agricart.pages.tools.pp_total_owed', [], $tLocale) }}</span>
                                <span class="agricart-pp-totals__v">{{ $totals['owed_display'] }}</span>
                            </span>
                            <span>
                                <span class="agricart-pp-totals__k">{{ __('agricart.pages.tools.pp_total_paid', [], $tLocale) }}</span>
                                <span class="agricart-pp-totals__v">{{ $totals['paid_display'] }}</span>
                            </span>
                            <span>
                                <span class="agricart-pp-totals__k">{{ __('agricart.pages.tools.pp_total_remaining', [], $tLocale) }}</span>
                                <span class="agricart-pp-totals__v">{{ $totals['remaining_display'] }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            @endfor
        </div>

        @include('filament.pages.tools.partials.pending-payments-scripts')
    @else
        @php $sheets = $this->listedSheets(); @endphp
        <div class="agricart-ro-list" x-data x-init="window.dispatchEvent(new CustomEvent('tools-pending-payments-screen',{detail:{screen:'list'}}))">
            <style>
                .agricart-ro-list{display:flex;flex-direction:column;gap:.75rem}
                .agricart-ro-list__hint{font-size:.8125rem;color:#64748b}
                .agricart-ro-list__table-wrap{overflow:auto;border-radius:.75rem;background:#fff;border:1px solid rgba(15,23,42,.08)}
                .agricart-ro-list__table{width:100%;border-collapse:collapse;font-size:.8125rem}
                .agricart-ro-list__table th{text-align:start;padding:.65rem .85rem;font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#64748b;border-bottom:1px solid rgba(15,23,42,.08);background:rgba(148,163,184,.08)}
                .agricart-ro-list__table td{padding:.7rem .85rem;border-bottom:1px solid rgba(15,23,42,.06);vertical-align:middle}
                .agricart-ro-list__order{font-weight:700;direction:ltr}
                .agricart-ro-list__money{font-size:.75rem;line-height:1.45;direction:ltr;white-space:nowrap}
                .agricart-ro-list__money span{display:block;color:#475569}
                .agricart-ro-list__money strong{color:#0f172a;font-weight:700}
                .agricart-ro-list__actions{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
                .agricart-ro-list__empty{padding:1.5rem;text-align:center;color:#64748b}
            </style>
            <p class="agricart-ro-list__hint">{{ __('agricart.pages.tools.pp_list_hint') }}</p>
            <div class="agricart-ro-list__table-wrap">
                @if ($sheets->isEmpty())
                    <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.pp_list_empty') }}</div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.pp_list_col_sheet') }}</th>
                                <th>{{ __('agricart.pages.tools.pp_list_col_date') }}</th>
                                <th>{{ __('agricart.pages.tools.pp_list_col_status') }}</th>
                                <th>{{ __('agricart.pages.tools.pp_list_col_money') }}</th>
                                <th>{{ __('agricart.pages.tools.pp_list_col_prepared') }}</th>
                                <th>{{ __('agricart.pages.tools.pp_list_col_updated') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sheets as $sheet)
                                @php
                                    $isClosed = (string) $sheet->status === 'closed';
                                    $money = $this->listSheetMoney($sheet);
                                @endphp
                                <tr wire:key="pp-list-{{ $sheet->id }}">
                                    <td class="agricart-ro-list__order">{{ $sheet->sheet_no }}</td>
                                    <td dir="ltr">{{ $sheet->sheet_date }}</td>
                                    <td>
                                        @if ($isClosed)
                                            {{ __('agricart.pages.tools.pp_status_closed') }}
                                        @else
                                            {{ __('agricart.pages.tools.pp_status_saved') }}
                                        @endif
                                    </td>
                                    <td class="agricart-ro-list__money">
                                        <span>{{ __('agricart.pages.tools.pp_list_money_owed') }}: <strong>{{ $money['owed_display'] }}</strong></span>
                                        <span>{{ __('agricart.pages.tools.pp_list_money_paid') }}: <strong>{{ $money['paid_display'] }}</strong></span>
                                        <span>{{ __('agricart.pages.tools.pp_list_money_remaining') }}: <strong>{{ $money['remaining_display'] }}</strong></span>
                                    </td>
                                    <td>{{ $sheet->prepared_by ?: '—' }}</td>
                                    <td dir="ltr">{{ optional($sheet->updated_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="agricart-ro-list__actions">
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openSheet({{ $sheet->id }})">{{ __('agricart.pages.tools.pp_list_open') }}</x-filament::button>
                                            @unless ($isClosed)
                                                <x-filament::button size="sm" color="primary" wire:click="editSheet({{ $sheet->id }})">{{ __('agricart.pages.tools.pp_list_edit') }}</x-filament::button>
                                                <x-filament::button size="sm" color="warning" outlined wire:click="closeListedSheet({{ $sheet->id }})" wire:confirm="{{ __('agricart.pages.tools.pp_list_close_confirm') }}">{{ __('agricart.pages.tools.pp_list_close') }}</x-filament::button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
