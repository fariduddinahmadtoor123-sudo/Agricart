<x-filament-panels::page>
    @include('filament.pages.tools.partials.purchase-summary-styles')

    @if ($screen === 'form')
        @php
            $canMarkLines = $this->canMarkLineStatus();
            $showMarkCols = true;
            $unresolvedLines = $canMarkLines ? $this->unresolvedLineCount() : 0;
            $amountSummary = $this->summaryAmountSummary();
            $tLocale = $formLang === 'ur' ? 'ur' : 'en';
        @endphp
        @if ($canMarkLines)
            <div @class(['agricart-ro-fulfill-banner', 'no-print', 'is-ok' => $unresolvedLines === 0])>
                @if ($unresolvedLines > 0)
                    {{ __('agricart.pages.tools.ps_line_status_hint') }}
                    — {{ __('agricart.pages.tools.ps_line_unresolved', ['count' => $unresolvedLines]) }}
                @else
                    {{ __('agricart.pages.tools.ps_line_all_marked') }}
                @endif
                <span class="agricart-ro-fulfill-legend">
                    <span><i class="is-g"></i> {{ __('agricart.pages.tools.ps_line_done') }}</span>
                    <span><i class="is-r"></i> {{ __('agricart.pages.tools.ps_line_forward') }}</span>
                </span>
            </div>
        @endif

        <div
            class="agricart-ro-sheet-wrap"
            data-ps-shopkeepers='@json($this->shopkeeperOptions())'
            @if (! $this->isViewMode())
                x-data="toolsPsDraft(@js(['userId' => (int) auth()->id()]))"
            @else
                x-data
            @endif
            x-init="
                window.__agricartPsShopkeepers = JSON.parse($el.getAttribute('data-ps-shopkeepers') || '[]');
                window.dispatchEvent(new CustomEvent('tools-purchase-summary-screen',{detail:{screen:'form',formMode:@js($this->isViewMode() ? 'view' : 'edit')}}));
            "
            wire:key="ps-form-{{ $shopkeepersTick }}-{{ $formMode }}-{{ $persistStatus }}-{{ $editingSummaryId ?? 0 }}"
        >
            @if (! $this->isViewMode())
                @teleport('#agricart-ps-toolbar-slot')
                    <div class="agricart-ro-chrome print:hidden" dir="ltr" x-data="toolsPsEntry(@js(['lang' => $formLang]))" x-on:tools-purchase-summary-lang-changed.window="setLang($event.detail.lang)">
                        <div class="agricart-ro-entry" style="flex:1 1 auto;width:100%" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                            <div class="agricart-ro-phrasepick agricart-ro-phrasepick--entry">
                                <input
                                    type="text"
                                    class="agricart-ro-entry__input agricart-ps-entry__input"
                                    wire:model.live="entryText"
                                    x-ref="entry"
                                    placeholder="{{ __('agricart.pages.tools.ps_entry_placeholder') }}"
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
                                    <div class="agricart-ro-phrasepick__menu" x-show="phraseOpen" x-cloak x-bind:style="entryMenuStyle()">
                                        <template x-for="(p, i) in entryFiltered" :key="'ep-'+i+'-'+p">
                                            <button type="button" class="agricart-ro-phrasepick__opt" :class="{ 'is-active': i === phraseActive }" x-on:mousedown.prevent="pickEntryPhrase(p)" x-text="p"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <button type="button" class="agricart-ro-iconbtn" :class="{ 'is-listening': listening }" x-on:click="toggleMic()" title="{{ __('agricart.pages.tools.ps_entry_mic') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                            </button>
                            <button type="button" class="agricart-ro-iconbtn" x-on:click="copyEntry()" title="{{ __('agricart.pages.tools.ps_entry_copy') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <button type="button" class="agricart-ro-iconbtn" wire:click="clearEntry" title="{{ __('agricart.pages.tools.ps_entry_clear') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            </button>
                            <input type="text" class="agricart-ro-entry__fwd" x-ref="fwd" wire:model.live="forwardTo" inputmode="numeric" placeholder="#" title="{{ __('agricart.pages.tools.ps_entry_forward_to') }}" dir="ltr" x-on:keydown.enter.prevent="doForward()" />
                            <button type="button" class="agricart-ro-btn" x-on:click.prevent="doForward()">{{ __('agricart.pages.tools.ps_entry_forward') }}</button>
                        </div>
                    </div>
                @endteleport
            @endif

            @for ($page = 1; $page <= $pageCount; $page++)
                <div class="agricart-ro-sheet" wire:key="ps-page-{{ $page }}" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                    @if ($page === 1)
                        <header class="agricart-ro-head">
                            <h1 class="agricart-ro-head__name">{{ $this->shopName() }}</h1>
                            @if (filled($this->shopAddress()))
                                <p class="agricart-ro-head__addr">{{ $this->shopAddress() }}</p>
                            @endif
                            @if (count($this->shopPhones()) > 0)
                                <p class="agricart-ro-head__phones">{{ implode(' · ', $this->shopPhones()) }}</p>
                            @endif
                        </header>
                        <div class="agricart-ro-meta">
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.ps_form_date', [], $tLocale) }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $summaryDate }}</span>
                            </div>
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.ps_form_summary_no', [], $tLocale) }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $summaryNo }}</span>
                            </div>
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.ps_form_page', [], $tLocale) }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $page }} / {{ $pageCount }}</span>
                            </div>
                            <div class="agricart-ro-meta__item agricart-ro-meta__item--prepared" style="margin-inline-start:auto">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.ps_form_prepared_by', [], $tLocale) }}</span>
                                <span class="agricart-ro-meta__v" @if ($formLang === 'ur') dir="rtl" @endif title="{{ $preparedBy }}">{{ $preparedBy }}</span>
                            </div>
                        </div>
                    @else
                        <div class="agricart-ro-strip">
                            @if (filled($this->shopName()))
                                <span class="agricart-ro-strip__shop">{{ $this->shopName() }}</span>
                            @endif
                            <span class="agricart-ro-strip__item">
                                <span class="agricart-ro-strip__k">{{ __('agricart.pages.tools.ps_form_summary_no') }}</span>
                                <span class="agricart-ro-strip__v agricart-ro-strip__v--ltr">{{ $summaryNo }}</span>
                            </span>
                            <span class="agricart-ro-strip__item">
                                <span class="agricart-ro-strip__k">{{ __('agricart.pages.tools.ps_form_page') }}</span>
                                <span class="agricart-ro-strip__v agricart-ro-strip__v--ltr">{{ $page }} / {{ $pageCount }}</span>
                            </span>
                        </div>
                    @endif

                    @php
                        $rowsPerCol = $this->rowsPerColForPage($page);
                        $gridCols = \App\Filament\Clusters\Tools\PurchaseSummaryPage::GRID_COLS;
                        $pageBase = $this->pageBaseForPage($page);
                        $tLocale = $formLang === 'ur' ? 'ur' : 'en';
                        $hNo = $formLang === 'ur' ? __('agricart.pages.tools.ps_grid_no_ur') : __('agricart.pages.tools.ps_grid_no_en');
                        $hPayment = $formLang === 'ur' ? __('agricart.pages.tools.ps_grid_payment_ur') : __('agricart.pages.tools.ps_grid_payment_en');
                        $hShopkeeper = $formLang === 'ur' ? __('agricart.pages.tools.ps_grid_shopkeeper_ur') : __('agricart.pages.tools.ps_grid_shopkeeper_en');
                        $skSearchPh = __('agricart.pages.tools.ps_shopkeeper_search', [], $tLocale);
                        $pendingLabel = $formLang === 'ur' ? __('agricart.pages.tools.ps_foot_pending_ur') : __('agricart.pages.tools.ps_foot_pending_en');
                        $hSources = __('agricart.pages.tools.ps_payment_sources', [], $tLocale);
                        $hAddSource = __('agricart.pages.tools.ps_add_source', [], $tLocale);
                        $hSourceDate = __('agricart.pages.tools.ps_source_date', [], $tLocale);
                        $hSourceLabel = __('agricart.pages.tools.ps_source_label', [], $tLocale);
                        $hSourceAmount = __('agricart.pages.tools.ps_source_amount', [], $tLocale);
                        $hTotalPayment = __('agricart.pages.tools.ps_total_payment', [], $tLocale);
                        $hTotalSpent = __('agricart.pages.tools.ps_total_spent', [], $tLocale);
                        $hTotalBalance = __('agricart.pages.tools.ps_total_balance', [], $tLocale);
                        $pendingHint = __('agricart.pages.tools.ps_pending_hint', [], $tLocale);
                        $hPendingClear = __('agricart.pages.tools.ps_pending_clear', [], $tLocale);
                        $hClearedTitle = __('agricart.pages.tools.ps_cleared_pending_title', [], $tLocale);
                        $isContPage = $page > 1;
                    @endphp

                    <div @class(['agricart-ro-sheet__body', 'agricart-ro-sheet__body--cont' => $isContPage])>
                        @for ($col = 0; $col < $gridCols; $col++)
                            @php $rowStart = $pageBase + ($col * $rowsPerCol) + 1; @endphp
                            <div class="agricart-ro-grid-col">
                                <table @class(['agricart-ro-grid', 'agricart-ro-grid--ps-marks' => $showMarkCols, 'agricart-ro-grid--cont' => $isContPage])>
                                    <thead>
                                        <tr>
                                            @if ($showMarkCols)
                                                <th class="agricart-ro-grid__mark agricart-ro-grid__mark--ok">✓</th>
                                            @endif
                                            <th class="agricart-ro-grid__no">{{ $hNo }}</th>
                                            <th class="agricart-ro-grid__shopkeeper">{{ $hShopkeeper }}</th>
                                            <th class="agricart-ro-grid__payment">{{ $hPayment }}</th>
                                            @if ($showMarkCols)
                                                <th class="agricart-ro-grid__mark agricart-ro-grid__mark--next">→</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for ($r = 0; $r < $rowsPerCol; $r++)
                                            @php
                                                $rowNo = $rowStart + $r;
                                                $rowKey = (string) $rowNo;
                                                $cell = $this->cellRow($rowKey);
                                                $lineFilled = $cell['payment'] !== '' || $cell['shopkeeper'] !== '';
                                                $lineStatus = $cell['status'] ?? '';
                                            @endphp
                                            <tr wire:key="ps-row-{{ $page }}-{{ $rowNo }}-{{ $lineStatus }}" @class(['agricart-ro-row--done' => $lineStatus === 'done', 'agricart-ro-row--forward' => $lineStatus === 'forward'])>
                                                @if ($showMarkCols)
                                                    <td class="agricart-ro-grid__mark agricart-ro-grid__mark--ok">
                                                        <span class="agricart-ro-mark-box" aria-hidden="true"></span>
                                                        @if ($canMarkLines)
                                                            <div class="agricart-ro-line-acts">
                                                                <button
                                                                    type="button"
                                                                    class="agricart-ro-dot agricart-ro-dot--ok {{ $lineStatus === 'done' ? 'is-on' : '' }}"
                                                                    wire:click.prevent="markLineStatus('{{ $rowKey }}', '{{ $lineStatus === 'done' ? 'clear' : 'done' }}')"
                                                                    title="{{ __('agricart.pages.tools.ps_line_done') }}"
                                                                ></button>
                                                            </div>
                                                        @elseif ($lineFilled && $lineStatus === 'done')
                                                            <span class="agricart-ro-dot agricart-ro-dot--ok is-on is-locked"></span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="agricart-ro-grid__no">{{ $rowNo }}</td>
                                                @if ($this->isViewMode())
                                                    <td class="agricart-ro-grid__shopkeeper" @if ($formLang === 'ur') dir="rtl" @endif>{{ $cell['shopkeeper'] }}</td>
                                                    <td class="agricart-ro-grid__payment" dir="ltr">{{ $cell['payment'] }}</td>
                                                @else
                                                    <td
                                                        class="agricart-ro-grid__shopkeeper"
                                                        wire:key="ps-sk-{{ $rowKey }}-{{ md5($cell['shopkeeper']) }}"
                                                        wire:ignore.self
                                                        x-data="psShopkeeperPick({ row: @js($rowKey), value: @js($cell['shopkeeper']), placeholder: @js($skSearchPh) })"
                                                        x-on:tools-purchase-summary-shopkeepers-updated.window="open = open && filtered.length > 0"
                                                    >
                                                        <div class="agricart-ro-phrasepick">
                                                            <input type="text" class="agricart-ro-grid__inp" x-ref="inp" x-model="q" x-on:input="onType($event)" x-on:focus="onFocus()" x-on:blur="onBlur()" x-on:keydown.escape.prevent="closeMenu()" x-on:keydown.enter.prevent="pickFirst()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" autocomplete="off" @if ($formLang === 'ur') dir="rtl" @endif />
                                                            <template x-teleport="body">
                                                                <div class="agricart-ro-phrasepick__menu" x-show="open" x-cloak x-bind:style="menuStyle()">
                                                                    <template x-for="(p, i) in filtered" :key="'sk-'+i+'-'+p">
                                                                        <button type="button" class="agricart-ro-phrasepick__opt" :class="{ 'is-active': i === active }" x-on:mousedown.prevent="pick(p)" x-text="p"></button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="agricart-ro-grid__payment">
                                                        <input type="text" class="agricart-ro-grid__inp" wire:model.blur="cells.{{ $rowKey }}.payment" dir="ltr" />
                                                    </td>
                                                @endif
                                                @if ($showMarkCols)
                                                    <td class="agricart-ro-grid__mark agricart-ro-grid__mark--next">
                                                        <span class="agricart-ro-mark-box" aria-hidden="true"></span>
                                                        @if ($canMarkLines)
                                                            <div class="agricart-ro-line-acts">
                                                                <button
                                                                    type="button"
                                                                    class="agricart-ro-dot agricart-ro-dot--next {{ $lineStatus === 'forward' ? 'is-on' : '' }}"
                                                                    wire:click.prevent="markLineStatus('{{ $rowKey }}', '{{ $lineStatus === 'forward' ? 'clear' : 'forward' }}')"
                                                                    title="{{ __('agricart.pages.tools.ps_line_forward') }}"
                                                                ></button>
                                                            </div>
                                                        @elseif ($lineFilled && $lineStatus === 'forward')
                                                            <span class="agricart-ro-dot agricart-ro-dot--next is-on is-locked"></span>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        @endfor
                    </div>

                    @if ($page === 1)
                        <footer class="agricart-ro-foot">
                            <div class="agricart-ps-pending">
                                <div class="agricart-ps-pending__head">
                                    <strong>{{ $pendingLabel }}</strong>
                                    <span class="agricart-ps-pending__hint no-print">{{ $pendingHint }}</span>
                                </div>
                                <table class="agricart-ps-pending__table">
                                    @php
                                        $pendingSlots = $pendingBills !== [] ? $pendingBills : array_fill(0, \App\Filament\Clusters\Tools\PurchaseSummaryPage::PENDING_PER_ROW, '');
                                        $pendingChunks = array_chunk($pendingSlots, \App\Filament\Clusters\Tools\PurchaseSummaryPage::PENDING_PER_ROW, true);
                                    @endphp
                                    @foreach ($pendingChunks as $chunk)
                                        <tr>
                                            @foreach ($chunk as $fi => $pendingVal)
                                                <td>
                                                    <span class="agricart-ps-pending__no" dir="ltr">{{ $fi + 1 }}</span>
                                                    @if ($this->isViewMode())
                                                        <span class="agricart-ps-pending__txt" @if ($formLang === 'ur') dir="rtl" @endif>{{ $pendingVal }}</span>
                                                    @else
                                                        <input type="text" class="agricart-ro-foot__inp agricart-ps-pending__inp" wire:model.blur="pendingBills.{{ $fi }}" @if ($formLang === 'ur') dir="rtl" @endif />
                                                        @if (trim((string) $pendingVal) !== '')
                                                            <button
                                                                type="button"
                                                                class="agricart-ro-btn agricart-ps-pending__clear no-print"
                                                                wire:click="clearPendingBill({{ $fi }})"
                                                                title="{{ $hPendingClear }}"
                                                            >✓</button>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </table>
                                @if ($clearedPendingBills !== [])
                                    <div class="agricart-ps-cleared" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                                        <strong class="agricart-ps-cleared__title">{{ $hClearedTitle }}</strong>
                                        <ul class="agricart-ps-cleared__list">
                                            @foreach ($clearedPendingBills as $cleared)
                                                <li>
                                                    {{ __('agricart.pages.tools.ps_cleared_pending_line', [
                                                        'bill' => $cleared['label'] ?? '',
                                                        'date' => $cleared['date'] ?? '',
                                                    ], $tLocale) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="agricart-ps-sources" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                                <div class="agricart-ps-sources__head">
                                    <strong>{{ $hSources }}</strong>
                                    @unless ($this->isViewMode())
                                        <button type="button" class="agricart-ro-btn no-print" wire:click="addPaymentSource">{{ $hAddSource }}</button>
                                    @endunless
                                </div>
                                <table class="agricart-ps-sources__table">
                                    <thead>
                                        <tr>
                                            <th style="width:18%">{{ $hSourceDate }}</th>
                                            <th>{{ $hSourceLabel }}</th>
                                            <th style="width:22%">{{ $hSourceAmount }}</th>
                                            @unless ($this->isViewMode())
                                                <th class="agricart-ps-sources__acts no-print"></th>
                                            @endunless
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($paymentSources as $si => $source)
                                            <tr wire:key="ps-src-{{ $si }}">
                                                <td>
                                                    @if ($this->isViewMode())
                                                        <span dir="ltr">{{ $source['date'] ?? '' }}</span>
                                                    @else
                                                        <input type="text" class="agricart-ro-foot__inp" wire:model.blur="paymentSources.{{ $si }}.date" dir="ltr" />
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($this->isViewMode())
                                                        <span @if ($formLang === 'ur') dir="rtl" @endif>{{ $source['label'] ?? '' }}</span>
                                                    @else
                                                        <input type="text" class="agricart-ro-foot__inp" wire:model.blur="paymentSources.{{ $si }}.label" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif />
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- Amounts stay LTR in both EN/UR (numbers / secret code). --}}
                                                    @if ($this->isViewMode())
                                                        <span dir="ltr">{{ $source['amount'] ?? '' }}</span>
                                                    @else
                                                        <input type="text" class="agricart-ro-foot__inp" wire:model.blur="paymentSources.{{ $si }}.amount" dir="ltr" />
                                                    @endif
                                                </td>
                                                @unless ($this->isViewMode())
                                                    <td class="agricart-ps-sources__acts no-print">
                                                        <button type="button" class="agricart-ro-btn" wire:click="removePaymentSource({{ $si }})">×</button>
                                                    </td>
                                                @endunless
                                            </tr>
                                        @empty
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                @unless ($this->isViewMode())
                                                    <td class="agricart-ps-sources__acts no-print"></td>
                                                @endunless
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($amountSummary['sources'] > 0 || $amountSummary['spent'] > 0)
                                <div class="agricart-ps-balance" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                                    <span>
                                        <span class="agricart-ro-totals__k">{{ $hTotalPayment }}</span>
                                        <span class="agricart-ro-totals__v" dir="ltr">{{ $amountSummary['sources_encoded'] !== '' ? $amountSummary['sources_encoded'] : $amountSummary['sources_plain'] }}</span>
                                    </span>
                                    <span>
                                        <span class="agricart-ro-totals__k">{{ $hTotalSpent }}</span>
                                        <span class="agricart-ro-totals__v" dir="ltr">{{ $amountSummary['spent_encoded'] !== '' ? $amountSummary['spent_encoded'] : $amountSummary['spent_plain'] }}</span>
                                    </span>
                                    <span>
                                        <span class="agricart-ro-totals__k">{{ $hTotalBalance }}</span>
                                        <span class="agricart-ro-totals__v" dir="ltr">{{ $amountSummary['balance_encoded'] !== '' ? $amountSummary['balance_encoded'] : $amountSummary['balance_plain'] }}</span>
                                    </span>
                                </div>
                            @endif
                        </footer>
                    @endif
                </div>
            @endfor
        </div>

        @include('filament.pages.tools.partials.purchase-summary-scripts')
    @else
        @php $summaries = $this->listedSummaries(); @endphp
        <div class="agricart-ro-list" x-data x-init="window.dispatchEvent(new CustomEvent('tools-purchase-summary-screen',{detail:{screen:'list'}}))">
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
            <p class="agricart-ro-list__hint">{{ __('agricart.pages.tools.ps_list_hint') }}</p>
            <div class="agricart-ro-list__table-wrap">
                @if ($summaries->isEmpty())
                    <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.ps_list_empty') }}</div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.ps_list_col_summary') }}</th>
                                <th>{{ __('agricart.pages.tools.ps_list_col_date') }}</th>
                                <th>{{ __('agricart.pages.tools.ps_list_col_status') }}</th>
                                <th>{{ __('agricart.pages.tools.ps_list_col_money') }}</th>
                                <th>{{ __('agricart.pages.tools.ps_list_col_prepared') }}</th>
                                <th>{{ __('agricart.pages.tools.ps_list_col_updated') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summaries as $summary)
                                @php
                                    $isClosed = (string) $summary->status === 'closed';
                                    $unresolved = $isClosed ? 0 : $this->unresolvedFilledLineCount(is_array($summary->cells) ? $summary->cells : []);
                                    $money = $this->listMoneySummary($summary);
                                @endphp
                                <tr wire:key="ps-list-{{ $summary->id }}">
                                    <td class="agricart-ro-list__order">{{ $summary->summary_no }}</td>
                                    <td dir="ltr">{{ $summary->summary_date }}</td>
                                    <td>
                                        @if ($isClosed)
                                            {{ __('agricart.pages.tools.ps_status_closed') }}
                                        @elseif ($unresolved > 0)
                                            {{ __('agricart.pages.tools.ps_status_saved') }}
                                            <span style="display:block;font-size:11px;color:#b45309">{{ __('agricart.pages.tools.ps_line_unresolved', ['count' => $unresolved]) }}</span>
                                        @else
                                            {{ __('agricart.pages.tools.ps_status_saved') }}
                                            <span style="display:block;font-size:11px;color:#15803d">{{ __('agricart.pages.tools.ps_line_all_marked') }}</span>
                                        @endif
                                    </td>
                                    <td class="agricart-ro-list__money">
                                        <span>{{ __('agricart.pages.tools.ps_list_money_given') }}: <strong>{{ $money['sources_display'] }}</strong></span>
                                        <span>{{ __('agricart.pages.tools.ps_list_money_spent') }}: <strong>{{ $money['spent_display'] }}</strong></span>
                                        <span>{{ __('agricart.pages.tools.ps_list_money_balance') }}: <strong>{{ $money['balance_display'] }}</strong></span>
                                        @if ($money['pending_count'] > 0)
                                            <span>{{ __('agricart.pages.tools.ps_list_money_pending', ['count' => $money['pending_count']]) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $summary->prepared_by ?: '—' }}</td>
                                    <td dir="ltr">{{ optional($summary->updated_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="agricart-ro-list__actions">
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openSummary({{ $summary->id }})">{{ __('agricart.pages.tools.ps_list_open') }}</x-filament::button>
                                            @unless ($isClosed)
                                                <x-filament::button size="sm" color="primary" wire:click="editSummary({{ $summary->id }})">{{ __('agricart.pages.tools.ps_list_edit') }}</x-filament::button>
                                                <x-filament::button size="sm" color="warning" outlined wire:click="closeListedSummary({{ $summary->id }})" wire:confirm="{{ __('agricart.pages.tools.ps_list_close_confirm') }}">{{ __('agricart.pages.tools.ps_list_close') }}</x-filament::button>
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
