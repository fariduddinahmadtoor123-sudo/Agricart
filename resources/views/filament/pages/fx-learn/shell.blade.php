<x-filament-panels::page>
    <div class="fx-learn-shell">
        <header class="fx-learn-shell__header">
            <h1 class="fx-learn-shell__title">{{ $this->pageTitle() }}</h1>
            <p class="fx-learn-shell__intro">{{ $this->pageIntro() }}</p>
        </header>

        <div class="fx-learn-shell__card" role="status">
            <span class="fx-learn-shell__badge">{{ $this->comingSoonBadge() }}</span>
            <p class="fx-learn-shell__card-text">{{ $this->placeholderLabel() }}</p>
        </div>
    </div>
</x-filament-panels::page>
