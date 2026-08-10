@php
    $title = $brief['title'] ?? '';
    $overview = $brief['overview'] ?? $brief['article'] ?? [];
    if (! is_array($overview)) {
        $overview = filled($overview) ? [(string) $overview] : [];
    }
    $notes = is_array($brief['notes'] ?? null) ? $brief['notes'] : [];
    $prompt = $brief['prompt'] ?? '';
    $placeholder = $labels['section_placeholder'] ?? '— To be filled later —';

    $overviewParagraphs = array_values(array_filter(
        array_map(static fn ($p) => is_string($p) ? trim($p) : '', $overview),
        static fn (string $p): bool => $p !== '',
    ));

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

@if ($title !== '')
    <h1>{{ $title }}</h1>
@endif

<div class="brief-section brief-section--current">
    <h2><span class="section-number">1</span>{{ $labels['overview'] ?? $labels['current'] ?? 'Overview' }}</h2>
    @if ($overviewParagraphs === [])
        <p class="placeholder">{{ $labels['overview_empty'] ?? $placeholder }}</p>
    @else
        @foreach ($overviewParagraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    @endif
</div>

<div class="brief-section brief-section--prompt">
    <h2><span class="section-number">2</span>{{ $labels['prompt'] ?? 'AI Prompt' }}</h2>
    @if (filled(trim((string) $prompt)))
        <pre class="prompt">{{ trim($prompt) }}</pre>
    @else
        <p class="placeholder">{{ $labels['prompt_empty'] ?? $placeholder }}</p>
    @endif
</div>

<div class="brief-section brief-section--improvements">
    <h2><span class="section-number">3</span>{{ $labels['notes'] ?? $labels['improvements'] ?? 'Notes' }}</h2>
    @if ($noteLines === [])
        <p class="placeholder">{{ $labels['notes_empty'] ?? $placeholder }}</p>
    @else
        <ul>
            @foreach ($noteLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif
</div>
