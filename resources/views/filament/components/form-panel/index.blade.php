@php
    use App\Filament\Forms\CategoryForm;
    use Filament\Support\Icons\Heroicon;

    $progressSteps = \App\Filament\Layout\ModuleFormPanel::progressSteps();
    $formPanelDefaults = [
        'addRecord' => __('agricart.form_panel.add_record'),
        'record' => __('agricart.form_panel.record'),
        'minimize' => __('agricart.form_panel.minimize'),
        'stretch' => __('agricart.form_panel.stretch'),
        'maximize' => __('agricart.form_panel.maximize'),
        'close' => __('agricart.form_panel.close'),
        'restore' => __('agricart.form_panel.restore'),
    ];
@endphp

<div
    x-data="{
        isOpen: false,
        minimized: false,
        size: 'normal',
        title: @js($formPanelDefaults['addRecord']),
        resourceLabel: @js($formPanelDefaults['record']),
        formKey: null,
        currentStep: 1,
        progressSteps: @js($progressSteps),
        defaults: @js($formPanelDefaults),
        categoryVisualMapping: { slots: [null, null, null, null, null], selected: null },
        categoryFaqs: [],
        categoryAiEnriching: false,
        categorySaving: false,
        selectCategoryVisualIcon(index) {
            this.categoryVisualMapping.selected = index;
        },
        addCategoryFaq() {
            this.categoryFaqs.push({ question_en: '', question_ur: '', answer_en: '', answer_ur: '' });
        },
        removeCategoryFaq(index) {
            this.categoryFaqs.splice(index, 1);
        },
        resetCategoryForm() {
            this.categoryVisualMapping = { slots: [null, null, null, null, null], selected: null };
            this.categoryFaqs = [];
        },
        get totalSteps() {
            return this.progressSteps.length;
        },
        get progressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },
        open(detail = {}) {
            this.title = detail.title ?? this.defaults.addRecord;
            this.resourceLabel = detail.resource ?? this.defaults.record;
            this.formKey = detail.formKey ?? null;
            this.currentStep = 1;

            if (this.formKey === 'categories') {
                this.resetCategoryForm();
                window.dispatchEvent(new CustomEvent('category-form-open', {
                    detail: {
                        categoryId: detail.categoryId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            this.isOpen = true;
            this.minimized = false;
            this.size = ['ai-settings', 'ai-log-detail', 'categories'].includes(this.formKey) ? 'stretched' : 'normal';
            document.body.classList.add('agricart-form-panel-open');

            if (this.formKey === 'ai-log-detail' && detail.logId) {
                window.Livewire.dispatch('ai-log-detail-open', { logId: detail.logId });
            }
        },
        close() {
            this.isOpen = false;
            this.minimized = false;
            this.size = 'normal';
            this.formKey = null;
            this.categoryAiEnriching = false;
            this.categorySaving = false;
            document.body.classList.remove('agricart-form-panel-open');
        },
        minimize() {
            this.minimized = true;
        },
        restore() {
            this.minimized = false;
        },
        toggleMaximize() {
            this.size = this.size === 'maximized' ? 'normal' : 'maximized';
            this.minimized = false;
        },
        toggleStretch() {
            this.size = this.size === 'stretched' ? 'normal' : 'stretched';
            this.minimized = false;
        },
        goToStep(step) {
            this.currentStep = Math.min(Math.max(step, 1), this.totalSteps);

            if (this.formKey === 'categories') {
                this.$nextTick(() => this.scrollToCategorySection(this.currentStep));
            }
        },
        scrollToCategorySection(step) {
            const anchors = @js(CategoryForm::stepScrollAnchors());
            const anchor = anchors[step] ?? 'hero';
            const target = document.getElementById('agricart-cat-section-' + anchor);

            if (! target) {
                return;
            }

            const body = target.closest('.agricart-form-panel__body');

            if (body) {
                body.scrollTo({
                    top: target.offsetTop - body.offsetTop - 8,
                    behavior: 'smooth',
                });

                return;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        handleAction(key) {
            if (this.formKey === 'categories') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.categoryAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('category-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (key === 'save_next' && this.currentStep < this.totalSteps) {
                this.currentStep += 1;

                if (this.formKey === 'categories') {
                    this.$nextTick(() => this.scrollToCategorySection(this.currentStep));
                }

                return;
            }

            if (key === 'ai') {
                return;
            }

            this.close();
        },
        panelClasses() {
            return {
                'agricart-form-panel--normal': this.size === 'normal',
                'agricart-form-panel--stretched': this.size === 'stretched',
                'agricart-form-panel--maximized': this.size === 'maximized',
                'agricart-form-panel--wide': this.formKey === 'ai-settings' || this.formKey === 'ai-log-detail' || this.formKey === 'categories',
            };
        },
    }"
    x-on:open-form-panel.window="open($event.detail)"
    x-on:close-form-panel.window="close()"
    x-on:category-form-busy.window="categoryAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); categorySaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:keydown.escape.window="if (isOpen && !minimized) close()"
>
    {{-- Backdrop + panel --}}
    <div
        x-cloak
        x-show="isOpen && !minimized"
        x-transition.opacity
        class="agricart-form-panel-overlay"
        x-on:click.self="close()"
    >
        <div
            class="agricart-form-panel"
            x-bind:class="panelClasses()"
            x-on:click.stop
            role="dialog"
            aria-modal="true"
            x-bind:aria-label="title"
        >
            <header class="agricart-form-panel__header">
                <div class="agricart-form-panel__header-main">
                    <h2 class="agricart-form-panel__title" x-text="title"></h2>
                    <p class="agricart-form-panel__subtitle" x-text="resourceLabel"></p>
                </div>

                <div class="agricart-form-panel__window-controls">
                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.minimize') }}"
                        x-on:click="minimize()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedMinus" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.minimize') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.stretch') }}"
                        x-on:click="toggleStretch()"
                        x-bind:class="{ 'is-active': size === 'stretched' }"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingOut" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.stretch') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.maximize') }}"
                        x-on:click="toggleMaximize()"
                        x-bind:class="{ 'is-active': size === 'maximized' }"
                    >
                        <x-filament::icon
                            :icon="Heroicon::OutlinedWindow"
                            class="agricart-form-panel__window-icon"
                        />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.maximize') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                        title="{{ __('agricart.form_panel.close') }}"
                        x-on:click="close()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.close') }}</span>
                    </button>
                </div>
            </header>

            <div class="agricart-form-panel__body">
                <div class="agricart-form-panel__sections">
                    <div x-show="formKey === 'ai-settings'" x-cloak>
                        <livewire:ai-settings.form />
                    </div>

                    <div x-show="formKey === 'ai-log-detail'" x-cloak>
                        <livewire:ai-logs.detail />
                    </div>

                    <div x-show="formKey === 'categories'" x-cloak>
                        <livewire:categories.form />
                    </div>

                    <div x-show="formKey !== 'ai-settings' && formKey !== 'ai-log-detail' && formKey !== 'categories'">
                        @include('filament.components.form-panel.sample-fields')
                    </div>
                </div>
            </div>

            <footer class="agricart-form-panel__footer" x-show="formKey !== 'ai-settings' && formKey !== 'ai-log-detail'">
                @include('filament.components.form-panel.footer')
            </footer>
        </div>
    </div>

    {{-- Minimized dock --}}
    <div
        x-cloak
        x-show="isOpen && minimized"
        class="agricart-form-panel-minimized"
    >
        <div class="agricart-form-panel-minimized__inner">
            <button
                type="button"
                class="agricart-form-panel-minimized__restore"
                x-on:click="restore()"
            >
                <x-filament::icon :icon="Heroicon::OutlinedWindow" class="agricart-form-panel__window-icon" />
                <span x-text="title"></span>
            </button>

            <div class="agricart-form-panel-minimized__controls">
                <button
                    type="button"
                    class="agricart-form-panel__window-btn"
                    title="{{ __('agricart.form_panel.restore') }}"
                    x-on:click="restore()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingIn" class="agricart-form-panel__window-icon" />
                </button>
                <button
                    type="button"
                    class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                    title="{{ __('agricart.form_panel.close') }}"
                    x-on:click="close()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    if (! window.__agricartFormPanelTriggerBound) {
        window.__agricartFormPanelTriggerBound = true;

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-agricart-open-form-panel]');

            if (! trigger) {
                return;
            }

            event.preventDefault();

            const formKey = trigger.getAttribute('data-form-key');

            window.dispatchEvent(new CustomEvent('open-form-panel', {
                detail: {
                    title: trigger.getAttribute('data-form-title') || @js($formPanelDefaults['addRecord']),
                    resource: trigger.getAttribute('data-form-resource') || @js($formPanelDefaults['record']),
                    formKey: formKey && formKey !== '' ? formKey : null,
                    categoryId: trigger.getAttribute('data-category-id') ? Number(trigger.getAttribute('data-category-id')) : null,
                    mode: trigger.getAttribute('data-form-mode') || 'create',
                },
            }));
        });
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('category-form-busy', (payload) => {
            const detail = payload?.enriching !== undefined ? payload : (Array.isArray(payload) ? payload[0] : {});

            window.dispatchEvent(new CustomEvent('category-form-busy', {
                detail,
                bubbles: true,
            }));
        });
    });
</script>
