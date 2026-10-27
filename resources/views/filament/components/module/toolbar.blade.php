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
        @if (empty($toolbar['hideSearch']))
            <div @class([
                'agricart-catalog-toolbar__search',
                'agricart-catalog-toolbar__search--segments' => ! empty($toolbar['segments']),
                'agricart-catalog-toolbar__search--expense-estimate' => ($toolbar['resourceSlug'] ?? '') === 'expenses',
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
