@php
    use Filament\Support\Icons\Heroicon;

    $progressSteps = \App\Filament\Layout\ModuleFormPanel::progressSteps();
@endphp

<div
    x-data="{
        isOpen: false,
        minimized: false,
        size: 'normal',
        title: 'Add Record',
        resourceLabel: 'Record',
        currentStep: 1,
        progressSteps: @js($progressSteps),
        get totalSteps() {
            return this.progressSteps.length;
        },
        get progressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },
        open(detail = {}) {
            this.title = detail.title ?? 'Add Record';
            this.resourceLabel = detail.resource ?? 'Record';
            this.currentStep = 1;
            this.isOpen = true;
            this.minimized = false;
            this.size = 'normal';
            document.body.classList.add('agricart-form-panel-open');
        },
        close() {
            this.isOpen = false;
            this.minimized = false;
            this.size = 'normal';
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
        },
        handleAction(key) {
            if (key === 'save_next' && this.currentStep < this.totalSteps) {
                this.currentStep += 1;
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
            };
        },
    }"
    x-on:open-form-panel.window="open($event.detail)"
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
                        title="Minimize"
                        x-on:click="minimize()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedMinus" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">Minimize</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="Stretch"
                        x-on:click="toggleStretch()"
                        x-bind:class="{ 'is-active': size === 'stretched' }"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingOut" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">Stretch</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="Maximize"
                        x-on:click="toggleMaximize()"
                        x-bind:class="{ 'is-active': size === 'maximized' }"
                    >
                        <x-filament::icon
                            :icon="Heroicon::OutlinedWindow"
                            class="agricart-form-panel__window-icon"
                        />
                        <span class="fi-sr-only">Maximize</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                        title="Close"
                        x-on:click="close()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">Close</span>
                    </button>
                </div>
            </header>

            <div class="agricart-form-panel__body">
                <div class="agricart-form-panel__sections">
                    @include('filament.components.form-panel.sample-fields')
                </div>
            </div>

            <footer class="agricart-form-panel__footer">
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
                    title="Restore"
                    x-on:click="restore()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingIn" class="agricart-form-panel__window-icon" />
                </button>
                <button
                    type="button"
                    class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                    title="Close"
                    x-on:click="close()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                </button>
            </div>
        </div>
    </div>
</div>
