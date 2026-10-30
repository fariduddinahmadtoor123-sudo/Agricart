@php
    use Filament\Support\Icons\Heroicon;

    /** @var class-string<\App\Filament\Layout\ClusterPageToolbar> $toolbarClass */
    $toolbar = $toolbarClass::config();
    $moduleKey = $moduleKey ?? 'module';
    $livewirePrefix = $toolbar['livewirePrefix'] ?? null;
    $livewireComponent = $toolbar['livewireComponent'] ?? null;
@endphp

@if (filled($toolbar))
    @if (! empty($toolbar['analyticsControls']))
        @include('filament.components.module.analytics-toolbar', [
            'toolbar' => $toolbar,
        ])
    @elseif (($toolbar['resourceSlug'] ?? '') === 'attendance' || ($toolbar['resourceSlug'] ?? '') === 'leave-management' || ($toolbar['resourceSlug'] ?? '') === 'payroll')
        @include('filament.components.module.attendance-toolbar', [
            'toolbar' => $toolbar,
            'moduleKey' => $moduleKey,
        ])
    @else
    @php
        $defaultTab = (string) (($toolbar['segments'][0]['value'] ?? null) ?: 'reorder');
    @endphp
    <div
        @class([
            'agricart-catalog-toolbar',
            'agricart-catalog-toolbar--compact' => ! empty($toolbar['hideSearch']) && ! empty($toolbar['hideFilters']) && ! empty($toolbar['hideColumns']),
            'agricart-catalog-toolbar--segmented' => ! empty($toolbar['segments']),
            'agricart-catalog-toolbar--reorder' => ($toolbar['resourceSlug'] ?? '') === 're-order-center',
            'agricart-catalog-toolbar--expenses' => ($toolbar['resourceSlug'] ?? '') === 'expenses',
            'agricart-catalog-toolbar--tools-reorder' => ! empty($toolbar['toolsReorderChrome']),
            'agricart-catalog-toolbar--tools-ps' => ! empty($toolbar['toolsPurchaseSummaryChrome']),
            'agricart-catalog-toolbar--tools-pp' => ! empty($toolbar['toolsPendingPaymentsChrome']),
            'agricart-catalog-toolbar--tools-bp' => ! empty($toolbar['toolsBillPrintingChrome']),
            'agricart-catalog-toolbar--tools-bc' => ! empty($toolbar['toolsBarcodePrintingChrome']),
            'agricart-catalog-toolbar--tools-he' => ! empty($toolbar['toolsHomeExpensesChrome']),
            'agricart-catalog-toolbar--fx-radar' => ! empty($toolbar['fxLearnRadarChrome']),
            'agricart-catalog-toolbar--fx-terminal' => ! empty($toolbar['fxLearnTerminalChrome']),
        ])
        @if (! empty($toolbar['toolsReorderChrome']))
            x-data="{
                screen: 'list',
                formMode: 'edit',
                syncScreen(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
                    if (data.screen) {
                        this.screen = data.screen;
                    }
                    if (data.formMode) {
                        this.formMode = data.formMode;
                    }
                },
            }"
            x-on:tools-reorder-screen.window="syncScreen($event.detail)"
            x-on:livewire:init="Livewire.on('tools-reorder-screen', (payload) => syncScreen(payload))"
        @elseif (! empty($toolbar['toolsPurchaseSummaryChrome']))
            x-data="{
                screen: 'list',
                formMode: 'edit',
                syncScreen(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
                    if (data.screen) {
                        this.screen = data.screen;
                    }
                    if (data.formMode) {
                        this.formMode = data.formMode;
                    }
                },
            }"
            x-on:tools-purchase-summary-screen.window="syncScreen($event.detail)"
            x-on:livewire:init="Livewire.on('tools-purchase-summary-screen', (payload) => syncScreen(payload))"
        @elseif (! empty($toolbar['toolsPendingPaymentsChrome']))
            x-data="{
                screen: 'list',
                formMode: 'edit',
                syncScreen(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
                    if (data.screen) {
                        this.screen = data.screen;
                    }
                    if (data.formMode) {
                        this.formMode = data.formMode;
                    }
                },
            }"
            x-on:tools-pending-payments-screen.window="syncScreen($event.detail)"
            x-on:livewire:init="Livewire.on('tools-pending-payments-screen', (payload) => syncScreen(payload))"
        @elseif (! empty($toolbar['toolsBillPrintingChrome']))
            x-data="{
                screen: 'list',
                formMode: 'edit',
                syncScreen(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
                    if (data.screen) {
                        this.screen = data.screen;
                    }
                    if (data.formMode) {
                        this.formMode = data.formMode;
                    }
                },
            }"
            x-on:tools-bill-printing-screen.window="syncScreen($event.detail)"
            x-on:livewire:init="Livewire.on('tools-bill-printing-screen', (payload) => syncScreen(payload))"
        @elseif (! empty($toolbar['segments']))
            x-data="{
                activeTab: @js($defaultTab),
                queueCount: 0,
                switchTab(tab) {
                    this.activeTab = tab;
                    window.Livewire.dispatch(@js($livewirePrefix . '-tab'), { tab });
                },
                sync(payload) {
                    const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});

                    if (data.activeTab) {
                        this.activeTab = data.activeTab;
                    }

                    if (data.queueCount !== undefined) {
                        this.queueCount = data.queueCount;
                    }
                },
            }"
            x-on:livewire:init="Livewire.on(@js($livewirePrefix . '-toolbar-sync'), (payload) => sync(payload))"
        @endif
    >
        @if (! empty($toolbar['fxLearnDropdownFilters']))
            @php
                $radarCcy = (string) ($toolbar['fxLearnRadarCcy'] ?? 'all');
                $radarImpact = (string) ($toolbar['fxLearnRadarImpact'] ?? 'all');
            @endphp
            <div
                class="agricart-fxr-selects"
                role="group"
                aria-label="{{ __('agricart.pages.fx-learn.filters_label') }}"
                x-data="{
                    ccy: @js($radarCcy),
                    impact: @js($radarImpact),
                    init() {
                        Livewire.on('fx-learn-radar-filters-sync', (e) => {
                            const d = Array.isArray(e) ? (e[0] ?? {}) : (e ?? {});
                            if (d.ccy) this.ccy = d.ccy;
                            if (d.impact) this.impact = d.impact;
                        });
                    },
                }"
            >
                <label class="agricart-fxr-select">
                    <span class="fi-sr-only">{{ __('agricart.pages.fx-learn.filter_ccy_label') }}</span>
                    <select
                        class="agricart-fxr-select__el"
                        x-model="ccy"
                        x-on:change="window.Livewire?.dispatch('fx-learn-radar-ccy', { ccy: $event.target.value })"
                    >
                        <option value="all">{{ __('agricart.pages.fx-learn.filter_ccy_all') }}</option>
                        <option value="usd">{{ __('agricart.pages.fx-learn.filter_ccy_usd') }}</option>
                    </select>
                </label>
                <label class="agricart-fxr-select">
                    <span class="fi-sr-only">{{ __('agricart.pages.fx-learn.filter_impact_label') }}</span>
                    <select
                        class="agricart-fxr-select__el"
                        x-model="impact"
                        x-on:change="window.Livewire?.dispatch('fx-learn-radar-impact', { impact: $event.target.value })"
                    >
                        <option value="all">{{ __('agricart.pages.fx-learn.filter_impact_all') }}</option>
                        <option value="high">{{ __('agricart.pages.fx-learn.filter_impact_high') }}</option>
                        <option value="med">{{ __('agricart.pages.fx-learn.filter_impact_med') }}</option>
                        <option value="low">{{ __('agricart.pages.fx-learn.filter_impact_low') }}</option>
                    </select>
                </label>
            </div>
        @endif

        @if (! empty($toolbar['fxLearnSignalsDropdowns']))
            @php
                $sigCats = [
                    'forex' => __('agricart.pages.fx-learn.sig_cat_forex'),
                    'commodities' => __('agricart.pages.fx-learn.sig_cat_commodities'),
                    'indices' => __('agricart.pages.fx-learn.sig_cat_indices'),
                    'crypto' => __('agricart.pages.fx-learn.sig_cat_crypto'),
                ];
                $sigTfs = \App\Services\FxLearn\MarketSignalCatalog::intervals();
                $sigCat = (string) ($toolbar['fxLearnSignalsCategory'] ?? 'forex');
                $sigTf = (string) ($toolbar['fxLearnSignalsInterval'] ?? '1h');
            @endphp
            <div
                class="agricart-fxr-selects"
                role="group"
                aria-label="{{ __('agricart.pages.fx-learn.sig_picker_label') }}"
                x-data="{
                    category: @js($sigCat),
                    interval: @js($sigTf),
                    init() {
                        Livewire.on('fx-learn-signals-filters-sync', (e) => {
                            const d = Array.isArray(e) ? (e[0] ?? {}) : (e ?? {});
                            if (d.category) this.category = d.category;
                            if (d.interval) this.interval = d.interval;
                        });
                    },
                }"
            >
                <label class="agricart-fxr-select">
                    <span class="fi-sr-only">{{ __('agricart.pages.fx-learn.sig_cat_label') }}</span>
                    <select
                        class="agricart-fxr-select__el"
                        x-model="category"
                        x-on:change="window.Livewire?.dispatch('fx-learn-signals-category', { category: $event.target.value })"
                    >
                        @foreach ($sigCats as $catId => $catLabel)
                            <option value="{{ $catId }}">{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="agricart-fxr-select">
                    <span class="fi-sr-only">{{ __('agricart.pages.fx-learn.sig_tf_label') }}</span>
                    <select
                        class="agricart-fxr-select__el"
                        x-model="interval"
                        x-on:change="window.Livewire?.dispatch('fx-learn-signals-interval', { interval: $event.target.value })"
                    >
                        @foreach ($sigTfs as $tfRow)
                            <option value="{{ $tfRow['id'] }}">{{ $tfRow['label'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        @endif

        @if (! empty($toolbar['fxLearnTerminalStrip']))
            @php
                $termAccount = is_array($toolbar['fxLearnTerminalAccount'] ?? null) ? $toolbar['fxLearnTerminalAccount'] : [];
                $termSessions = is_array($toolbar['fxLearnTerminalSessions'] ?? null) ? $toolbar['fxLearnTerminalSessions'] : [];
                $termData = is_array($toolbar['fxLearnTerminalData'] ?? null) ? $toolbar['fxLearnTerminalData'] : [];
                $termDataNext = is_array($termData['next'] ?? null) ? $termData['next'] : null;
            @endphp
            <div class="agricart-fxt-chrome">
                {{-- Left: session clocks — green open / red closed --}}
                <div
                    class="agricart-fxt-sessions"
                    role="group"
                    aria-label="{{ __('agricart.pages.fx-learn.term_sessions_label') }}"
                    x-data="{
                        pack: @js($termSessions),
                        rows: @js($termSessions['sessions'] ?? []),
                        overlap: @js(! empty($termSessions['overlap'])),
                        timer: null,
                        init() {
                            Livewire.on('fx-learn-terminal-sessions-sync', (e) => {
                                const d = Array.isArray(e) ? (e[0] ?? {}) : (e ?? {});
                                if (d.sessions) {
                                    this.pack = d.sessions;
                                    this.rows = d.sessions.sessions || [];
                                    this.overlap = !!d.sessions.overlap;
                                    this.tick();
                                }
                            });
                            this.tick();
                            this.timer = setInterval(() => this.tick(), 15000);
                        },
                        destroy() {
                            if (this.timer) clearInterval(this.timer);
                        },
                        clock(tz) {
                            try {
                                return new Intl.DateTimeFormat('en-GB', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false,
                                    timeZone: tz || 'UTC',
                                }).format(new Date());
                            } catch (e) {
                                return '—';
                            }
                        },
                        tick() {
                            const now = Date.now();
                            let openCount = 0;
                            this.rows = (this.rows || []).map((row) => {
                                const startMs = Number(row.start_ms) || 0;
                                const closeMs = Number(row.close_ms) || 0;
                                const open = startMs > 0 && closeMs > startMs && now >= startMs && now < closeMs;
                                if (open) openCount++;
                                return {
                                    ...row,
                                    open,
                                    clock: this.clock(row.tz),
                                };
                            });
                            this.overlap = openCount >= 2;
                        },
                        liqClass() {
                            const k = this.pack?.liquidity || 'off';
                            return {
                                'is-very-high': k === 'very_high',
                                'is-high': k === 'high',
                                'is-medium': k === 'medium',
                                'is-off': k === 'off',
                            };
                        }
                    }"
                    x-on:remove="destroy()"
                >
                    <span
                        class="agricart-fxt-sessions__liq"
                        :class="liqClass()"
                        :title="pack?.summary || ''"
                        x-text="pack?.liquidity_label || ''"
                    ></span>
                    <template x-for="row in rows" :key="row.id">
                        <div
                            class="agricart-fxt-sessions__item"
                            :class="{
                                'is-open': row.open,
                                'is-closed': ! row.open,
                                'is-overlap': row.open && overlap
                            }"
                            :title="row.label"
                        >
                            <span class="agricart-fxt-sessions__code" x-text="row.short"></span>
                            <strong class="agricart-fxt-sessions__clock" dir="ltr" x-text="row.clock || '—'"></strong>
                        </div>
                    </template>
                </div>

                {{-- Center: next economic data + filters --}}
                <div
                    class="agricart-fxt-data"
                    role="group"
                    aria-label="{{ __('agricart.pages.fx-learn.term_data_box') }}"
                    x-data="{
                        ccy: @js((string) ($termData['ccy'] ?? 'all')),
                        impact: @js((string) ($termData['impact'] ?? 'high')),
                        next: @js($termDataNext),
                        countdown: '—',
                        timer: null,
                        emptyLabel: @js(__('agricart.pages.fx-learn.term_data_empty')),
                        prefixLabel: @js(__('agricart.pages.fx-learn.term_data_prefix')),
                        init() {
                            Livewire.on('fx-learn-terminal-data-sync', (e) => {
                                const d = Array.isArray(e) ? (e[0] ?? {}) : (e ?? {});
                                const pack = d.data || d;
                                if (pack.ccy) this.ccy = pack.ccy;
                                if (pack.impact) this.impact = pack.impact;
                                this.next = pack.next ?? null;
                                this.tick();
                            });
                            this.tick();
                            this.timer = setInterval(() => this.tick(), 1000);
                        },
                        destroy() {
                            if (this.timer) clearInterval(this.timer);
                        },
                        setCcy(v) {
                            this.ccy = v;
                            window.Livewire?.dispatch('fx-learn-terminal-data-ccy', { ccy: v });
                        },
                        setImpact(v) {
                            this.impact = v;
                            window.Livewire?.dispatch('fx-learn-terminal-data-impact', { impact: v });
                        },
                        tick() {
                            const at = Number(this.next?.at) || 0;
                            if (! at) {
                                this.countdown = '—';
                                return;
                            }
                            const diff = Math.floor((at - Date.now()) / 1000);
                            const abs = Math.abs(diff);
                            const h = Math.floor(abs / 3600);
                            const m = Math.floor((abs % 3600) / 60);
                            const s = abs % 60;
                            const pad = (n) => String(n).padStart(2, '0');
                            this.countdown = diff >= 0
                                ? (h > 0 ? (h + 'h ' + m + 'm') : (m > 0 ? (m + 'm ' + pad(s) + 's') : (s + 's')))
                                : (h > 0 ? (h + 'h ' + m + 'm ago') : (m + 'm ago'));
                        }
                    }"
                    x-on:remove="destroy()"
                >
                    <div class="agricart-fxt-data__filters" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_data_ccy') }}">
                        <button type="button" class="fxt__chip-btn" :class="{ 'is-on': ccy === 'USD' }" x-on:click="setCcy('USD')">{{ __('agricart.pages.fx-learn.term_data_ccy_usd') }}</button>
                        <button type="button" class="fxt__chip-btn" :class="{ 'is-on': ccy === 'all' }" x-on:click="setCcy('all')">{{ __('agricart.pages.fx-learn.term_data_ccy_all') }}</button>
                    </div>
                    <div class="agricart-fxt-data__filters" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_data_impact') }}">
                        <button type="button" class="fxt__chip-btn" :class="{ 'is-on': impact === 'high' }" x-on:click="setImpact('high')">{{ __('agricart.pages.fx-learn.term_data_impact_high') }}</button>
                        <button type="button" class="fxt__chip-btn" :class="{ 'is-on': impact === 'med' }" x-on:click="setImpact('med')">{{ __('agricart.pages.fx-learn.term_data_impact_med') }}</button>
                        <button type="button" class="fxt__chip-btn" :class="{ 'is-on': impact === 'all' }" x-on:click="setImpact('all')">{{ __('agricart.pages.fx-learn.term_data_impact_all') }}</button>
                    </div>
                    <div class="agricart-fxt-data__ticker" dir="ltr" x-show="next">
                        <span class="fxt__data-kicker" x-text="prefixLabel"></span>
                        <strong class="fxt__data-count" x-text="countdown">—</strong>
                        <span class="fxt__data-sep">·</span>
                        <span class="fxt__data-ccy" x-text="(next?.ccy || '').toUpperCase()"></span>
                        <span class="fxt__data-title" x-text="next?.title || ''"></span>
                        <span
                            class="fxt__data-impact"
                            :class="'is-' + (next?.impact || 'med')"
                            x-text="(next?.impact || '').toUpperCase()"
                        ></span>
                    </div>
                    <div class="agricart-fxt-data__ticker agricart-fxt-data__ticker--empty" x-show="! next" x-cloak>
                        <span x-text="emptyLabel"></span>
                    </div>
                </div>

                {{-- Right: Balance / Equity / Floating / Day / Overall --}}
                <div
                    class="agricart-fxt-account"
                    role="group"
                    aria-label="{{ __('agricart.pages.fx-learn.term_stats_label') }}"
                    x-data="{
                        rows: @js($termAccount),
                        init() {
                            Livewire.on('fx-learn-terminal-account-sync', (e) => {
                                const d = Array.isArray(e) ? (e[0] ?? {}) : (e ?? {});
                                if (d.account) this.rows = d.account;
                            });
                        },
                    }"
                >
                    <template x-for="row in rows" :key="row.id">
                        <div
                            class="agricart-fxt-account__item"
                            :class="{
                                'is-pos': row.tone === 'pos',
                                'is-neg': row.tone === 'neg'
                            }"
                        >
                            <span class="agricart-fxt-account__label" x-text="row.label || ''"></span>
                            <strong class="agricart-fxt-account__value" dir="ltr" x-text="row.value || '—'"></strong>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        @if (empty($toolbar['hideSearch']))
            <div @class([
                'agricart-catalog-toolbar__search',
                'agricart-catalog-toolbar__search--segments' => ! empty($toolbar['segments']),
                'agricart-catalog-toolbar__search--expense-estimate' => ($toolbar['resourceSlug'] ?? '') === 'expenses',
                'agricart-catalog-toolbar__search--fx-center' => ! empty($toolbar['fxLearnRadarChrome']),
            ])>
                @if (! empty($toolbar['segments']))
                    @include('filament.components.module.toolbar-segments', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
                @endif

                @if (($toolbar['resourceSlug'] ?? '') === 'expenses')
                    @include('filament.components.module.expense-estimate-toolbar', ['toolbar' => $toolbar])
                @endif

                @if (empty($toolbar['hideSearchInput']))
                    <label class="fi-sr-only" for="agricart-{{ $moduleKey }}-search-{{ $toolbar['resourceSlug'] }}">
                            {{ $toolbar['searchPlaceholder'] }}
                        </label>

                        <x-filament::input.wrapper
                            :prefix-icon="Heroicon::OutlinedMagnifyingGlass"
                            inline-prefix
                        >
                            <input
                                id="agricart-{{ $moduleKey }}-search-{{ $toolbar['resourceSlug'] }}"
                                type="search"
                                autocomplete="off"
                                placeholder="{{ $toolbar['searchPlaceholder'] }}"
                                class="fi-input fi-input-has-inline-prefix agricart-catalog-toolbar__search-input"
                                @if ($livewirePrefix)
                                    @if (filled($livewireComponent))
                                        x-on:input.debounce.300ms="Livewire.dispatchTo(@js($livewireComponent), @js($livewirePrefix . '-search'), { query: $event.target.value })"
                                    @else
                                        x-on:input.debounce.300ms="window.Livewire.dispatch(@js($livewirePrefix . '-search'), { query: $event.target.value })"
                                    @endif
                                @endif
                        />
                    </x-filament::input.wrapper>
                @endif

                @if (($toolbar['resourceSlug'] ?? '') === 're-order-center')
                    <span
                        id="agricart-reorder-toolbar-code"
                        class="agricart-reorder-toolbar__code"
                        dir="ltr"
                        aria-hidden="true"
                    >—</span>
                @endif
            </div>
        @endif

        @if (! empty($toolbar['toolsReorderChrome']))
            <div
                id="agricart-ro-toolbar-slot"
                class="agricart-ro-toolbar-slot"
                x-show="screen === 'form'"
                x-cloak
            ></div>
        @endif

        @if (! empty($toolbar['toolsPurchaseSummaryChrome']))
            <div
                id="agricart-ps-toolbar-slot"
                class="agricart-ps-toolbar-slot"
                x-show="screen === 'form'"
                x-cloak
            ></div>
        @endif

        @if (! empty($toolbar['toolsPendingPaymentsChrome']))
            <div
                id="agricart-pp-toolbar-slot"
                class="agricart-pp-toolbar-slot"
                x-show="screen === 'form'"
                x-cloak
            ></div>
        @endif

        @if (! empty($toolbar['toolsBillPrintingChrome']))
            <div
                id="agricart-bp-toolbar-slot"
                class="agricart-bp-toolbar-slot"
                x-show="screen === 'form' && formMode === 'edit'"
                x-cloak
            ></div>
        @endif

        @if (! empty($toolbar['toolsBarcodePrintingChrome']))
            <div
                class="agricart-bc-toolbar-slot"
                x-data="{
                    sizes: [],
                    selected: '',
                    pickLabel: @js(__('agricart.pages.tools.bc_pick_size')),
                    emptyLabel: @js(__('agricart.pages.tools.bc_no_saved_sizes')),
                    sync(detail) {
                        const data = Array.isArray(detail) ? (detail[0] ?? {}) : (detail ?? {});
                        this.sizes = Array.isArray(data.sizes) ? data.sizes : [];
                        this.selected = data.selected ?? '';
                        this.$nextTick(() => this.paint());
                    },
                    paint() {
                        const sel = this.$refs.sizeSelect;
                        if (! sel) return;
                        const current = this.selected;
                        sel.innerHTML = '';
                        const placeholder = document.createElement('option');
                        placeholder.value = '';
                        placeholder.textContent = this.sizes.length ? this.pickLabel : this.emptyLabel;
                        sel.appendChild(placeholder);
                        this.sizes.forEach((size) => {
                            const opt = document.createElement('option');
                            opt.value = size.key;
                            opt.textContent = size.label;
                            sel.appendChild(opt);
                        });
                        sel.value = current && this.sizes.some((s) => s.key === current) ? current : '';
                    },
                    choose(value) {
                        this.selected = value;
                        window.Livewire.dispatch('tools-barcode-printing-select-size', { key: value });
                    },
                }"
                x-on:tools-barcode-printing-chrome.window="sync($event.detail)"
                x-on:livewire:init="Livewire.on('tools-barcode-printing-chrome', (payload) => sync(payload))"
            >
                <select
                    class="agricart-bc__size-select"
                    x-ref="sizeSelect"
                    x-on:change="choose($event.target.value)"
                >
                    <option value="">{{ __('agricart.pages.tools.bc_no_saved_sizes') }}</option>
                </select>
            </div>
        @endif

        @if (! empty($toolbar['toolsHomeExpensesChrome']))
            <div
                id="agricart-he-toolbar-slot"
                class="agricart-he-toolbar-slot"
                x-data="{
                    title: '',
                    expected: 'Rs 0',
                    paid: 'Rs 0',
                    remaining: 'Rs 0',
                    viewingClosed: false,
                    paidLabel: @js(__('agricart.pages.tools.he_total_paid')),
                    remainingLabel: @js(__('agricart.pages.tools.he_total_remaining')),
                    normalize(detail) {
                        if (detail == null) return {};
                        if (Array.isArray(detail)) {
                            const first = detail[0];
                            if (first && typeof first === 'object' && ! Array.isArray(first)) {
                                return first;
                            }
                            if (typeof first === 'string' || typeof first === 'number') {
                                return {
                                    title: detail[0] ?? '',
                                    expected: detail[1] ?? 'Rs 0',
                                    paid: detail[2] ?? 'Rs 0',
                                    remaining: detail[3] ?? 'Rs 0',
                                    viewingClosed: !! detail[4],
                                };
                            }
                            return {};
                        }
                        if (typeof detail === 'object') return detail;
                        return {};
                    },
                    sync(detail) {
                        const data = this.normalize(detail);
                        if (data.title != null && data.title !== '') this.title = data.title;
                        if (data.expected != null) this.expected = data.expected;
                        if (data.paid != null) this.paid = data.paid;
                        if (data.remaining != null) this.remaining = data.remaining;
                        if (typeof data.viewingClosed === 'boolean') {
                            this.viewingClosed = data.viewingClosed;
                            window.dispatchEvent(new CustomEvent('tools-home-expenses-viewing', { detail: { viewingClosed: data.viewingClosed } }));
                        }
                    },
                    pullFromPage() {
                        const el = document.getElementById('agricart-he-totals-data');
                        if (! el) return;
                        try {
                            const raw = el.getAttribute('data-payload') || '{}';
                            this.sync(JSON.parse(raw));
                        } catch (e) {}
                    },
                    init() {
                        this.pullFromPage();
                        this.$nextTick(() => this.pullFromPage());
                        queueMicrotask(() => this.pullFromPage());
                        document.addEventListener('tools-home-expenses-totals', (e) => this.sync(e.detail));
                        if (window.Livewire) {
                            Livewire.hook('morph.updated', () => {
                                queueMicrotask(() => this.pullFromPage());
                            });
                        }
                    },
                }"
                x-on:tools-home-expenses-totals.window="sync($event.detail)"
                x-on:livewire:init="Livewire.on('tools-home-expenses-totals', (payload) => sync(payload))"
            >
                <div class="agricart-he__totals">
                    <span class="agricart-he__totals-muted" x-text="title"></span>
                    <span x-text="paidLabel + ': ' + paid"></span>
                    <span class="agricart-he__totals-remaining" x-text="remainingLabel + ': ' + remaining"></span>
                </div>
            </div>
        @endif

        <div
            class="agricart-catalog-toolbar__actions"
            @if (! empty($toolbar['toolsHomeExpensesChrome']))
                x-data="{ viewingClosed: false }"
                x-on:tools-home-expenses-viewing.window="viewingClosed = !!(Array.isArray($event.detail) ? $event.detail[0]?.viewingClosed : $event.detail?.viewingClosed)"
                x-bind:style="viewingClosed ? 'display:none' : null"
            @endif
        >
            @if (empty($toolbar['hideFilters']) || ! empty($toolbar['filters']) || ! empty($toolbar['filterGroups']))
                @include('filament.components.module.filter-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif
            @if (empty($toolbar['hideColumns']) || ! empty($toolbar['columns']))
                @include('filament.components.module.columns-dropdown', ['toolbar' => $toolbar, 'moduleKey' => $moduleKey])
            @endif

            @if (! empty($toolbar['toolbarActions']))
                @include('filament.components.module.toolbar-actions', ['toolbar' => $toolbar])
            @endif

            @if (! empty($toolbar['fxLearnSettings']) && is_array($toolbar['fxLearnSettings']))
                @php
                    $settingsLabel = (string) ($toolbar['fxLearnSettings']['label'] ?? __('agricart.pages.fx-learn.settings'));
                    $settingsEvent = (string) ($toolbar['fxLearnSettings']['event'] ?? 'fx-learn-radar-settings');
                @endphp
                <button
                    type="button"
                    class="agricart-fxr-settings"
                    title="{{ $settingsLabel }}"
                    x-data
                    x-on:click="window.Livewire?.dispatch(@js($settingsEvent))"
                >
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">
                        <path fill-rule="evenodd" d="M7.84 1.804A1 1 0 0 1 8.82 1h2.36a1 1 0 0 1 .98.804l.331 1.652a6.993 6.993 0 0 1 1.929.923l1.548-.34a1 1 0 0 1 1.145.67l.8 2.498a1 1 0 0 1-.355 1.13l-1.27.96a7.05 7.05 0 0 1 0 1.846l1.27.96a1 1 0 0 1 .355 1.13l-.8 2.498a1 1 0 0 1-1.145.67l-1.548-.34a6.993 6.993 0 0 1-1.929.923l-.331 1.652a1 1 0 0 1-.98.804H8.82a1 1 0 0 1-.98-.804l-.331-1.652a6.993 6.993 0 0 1-1.929-.923l-1.548.34a1 1 0 0 1-1.145-.67l-.8-2.498a1 1 0 0 1 .355-1.13l1.27-.96a7.05 7.05 0 0 1 0-1.846l-1.27-.96a1 1 0 0 1-.355-1.13l.8-2.498a1 1 0 0 1 1.145-.67l1.548.34a6.993 6.993 0 0 1 1.929-.923l.331-1.652ZM10 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $settingsLabel }}</span>
                </button>
            @endif

            @if (! empty($toolbar['fxLearnVisitLinks']) && is_array($toolbar['fxLearnVisitLinks']))
                @foreach ($toolbar['fxLearnVisitLinks'] as $link)
                    @php
                        $url = (string) ($link['url'] ?? '');
                        $label = (string) ($link['label'] ?? '');
                        $key = (string) ($link['key'] ?? '');
                    @endphp
                    @continue($url === '' || $label === '')
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        @class([
                            'agricart-fxr-visit',
                            'agricart-fxr-visit--'.$key => $key !== '',
                        ])
                    >
                        <span>{{ $label }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M5.22 14.78a.75.75 0 0 0 1.06 0l5.97-5.97v2.69a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-.75-.75h-4.5a.75.75 0 0 0 0 1.5h2.69l-5.97 5.97a.75.75 0 0 0 0 1.06Z" />
                        </svg>
                    </a>
                @endforeach
            @endif

            @if (filled($toolbar['addLabel']))
                @if (filled($toolbar['addEvent'] ?? null))
                    <x-filament::button
                        color="primary"
                        :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                        tag="button"
                        type="button"
                        @class([
                            'agricart-catalog-toolbar__action',
                            'agricart-catalog-toolbar__action--compact-label' => ($toolbar['compactAddButton'] ?? false) === true,
                        ])
                        data-agricart-livewire-dispatch="{{ $toolbar['addEvent'] }}"
                    >
                        {{ $toolbar['addLabel'] }}
                    </x-filament::button>
                @else
                    <x-filament::button
                        color="primary"
                        :icon="$toolbar['actionIcon'] ?? Heroicon::OutlinedPlus"
                        tag="button"
                        type="button"
                        @class([
                            'agricart-catalog-toolbar__action',
                            'agricart-catalog-toolbar__action--compact-label' => ($toolbar['compactAddButton'] ?? false) === true,
                        ])
                        data-agricart-open-form-panel
                        data-form-title="{{ $toolbar['addLabel'] }}"
                        data-form-resource="{{ $toolbar['resourceLabel'] }}"
                        data-form-key="{{ $toolbar['formKey'] ?? '' }}"
                        data-form-context="{{ $toolbar['formContext'] ?? '' }}"
                    >
                        {{ $toolbar['addLabel'] }}
                    </x-filament::button>
                @endif
            @endif
        </div>
    </div>
    @endif
@endif
