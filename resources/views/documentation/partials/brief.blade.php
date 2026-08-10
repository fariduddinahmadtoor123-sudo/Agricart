@php
    $locale = in_array(($localeTab ?? 'ur'), ['en', 'ur'], true) ? $localeTab : 'ur';
    $title = $brief['title'] ?? '';
    $overview = $brief['overview'] ?? $brief['article'] ?? [];
    if (! is_array($overview)) {
        $overview = filled($overview) ? [(string) $overview] : [];
    }
    $notes = is_array($brief['notes'] ?? null) ? $brief['notes'] : [];
    $prompt = $brief['prompt'] ?? '';
@endphp

<div
    class="agricart-docs-brief"
    dir="{{ $locale === 'ur' ? 'rtl' : 'ltr' }}"
    x-data="{
        copied: false,
        async copyPrompt() {
            const text = this.$refs.promptText.innerText;
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                const range = document.createRange();
                range.selectNodeContents(this.$refs.promptText);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
    }"
>
    @if ($title !== '')
        <h1 class="agricart-docs-brief__title">{{ $title }}</h1>
    @endif

    {{-- 1. Overview — user guide (EN / UR via locale tab) --}}
    <section class="agricart-docs-brief__box agricart-docs-brief__box--current">
        <header class="agricart-docs-brief__box-head">
            <span class="agricart-docs-brief__section-number">1</span>
            <h2>{{ __('documentation.brief.overview_heading', [], $locale) }}</h2>
        </header>
        <div class="agricart-docs-brief__box-body">
            @php
                $overviewParagraphs = array_values(array_filter(
                    array_map(static fn ($p) => is_string($p) ? trim($p) : '', $overview),
                    static fn (string $p): bool => $p !== '',
                ));
            @endphp
            @if ($overviewParagraphs === [])
                <p class="agricart-docs-brief__placeholder">{{ __('documentation.brief.overview_empty', [], $locale) }}</p>
            @else
                @foreach ($overviewParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            @endif
        </div>
    </section>

    {{-- 2. AI Prompt --}}
    <section class="agricart-docs-brief__box agricart-docs-brief__box--prompt">
        <header class="agricart-docs-brief__box-head">
            <div class="agricart-docs-brief__prompt-header">
                <h2>
                    <span class="agricart-docs-brief__section-number">2</span>
                    {{ __('documentation.brief.prompt_heading', [], $locale) }}
                </h2>
                @if (filled(trim((string) $prompt)))
                    <button type="button" class="agricart-docs-brief__copy" x-on:click="copyPrompt()">
                        <span x-show="!copied">{{ __('documentation.brief.copy_prompt', [], $locale) }}</span>
                        <span x-cloak x-show="copied">{{ __('documentation.brief.copied', [], $locale) }}</span>
                    </button>
                @endif
            </div>
        </header>
        <div class="agricart-docs-brief__box-body">
            <p class="agricart-docs-brief__hint">{{ __('documentation.brief.prompt_hint', [], $locale) }}</p>
            @if (filled(trim((string) $prompt)))
                <pre class="agricart-docs-brief__prompt" x-ref="promptText">{{ trim($prompt) }}</pre>
            @else
                <p class="agricart-docs-brief__placeholder">{{ __('documentation.brief.prompt_empty', [], $locale) }}</p>
            @endif
        </div>
    </section>

    {{-- 3. Notes — future wishes; editable in lang files, not hard-locked --}}
    <section class="agricart-docs-brief__box agricart-docs-brief__box--improve">
        <header class="agricart-docs-brief__box-head">
            <span class="agricart-docs-brief__section-number">3</span>
            <h2>{{ __('documentation.brief.notes_heading', [], $locale) }}</h2>
        </header>
        <div class="agricart-docs-brief__box-body">
            <p class="agricart-docs-brief__hint">{{ __('documentation.brief.notes_hint', [], $locale) }}</p>
            @php
                $noteLines = array_values(array_filter(
                    array_map(static function ($note): string {
                        if (is_array($note)) {
                            return trim((string) ($note['text'] ?? $note['note'] ?? ''));
                        }

                        return is_string($note) ? trim($note) : '';
                    }, $notes),
                    static fn (string $line): bool => $line !== '',
                ));
            @endphp
            @if ($noteLines === [])
                <p class="agricart-docs-brief__placeholder">{{ __('documentation.brief.notes_empty', [], $locale) }}</p>
            @else
                <ul class="agricart-docs-brief__spec-list">
                    @foreach ($noteLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
