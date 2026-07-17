@php
    $title = $brief['title'] ?? '';
    $summary = $brief['summary'] ?? null;
    $article = $brief['article'] ?? [];
    $fields = $brief['fields'] ?? [];
    $uiFlow = $brief['ui_flow'] ?? [];
    $functional = $brief['functional_requirements'] ?? [];
    $validation = $brief['validation'] ?? [];
    $database = $brief['database'] ?? [];
    $api = $brief['api'] ?? [];
    $permissions = $brief['permissions'] ?? [];
    $edgeCases = $brief['edge_cases'] ?? [];
    $businessRules = array_key_exists('business_rules', $brief) ? ($brief['business_rules'] ?? []) : null;
    $nonFunctional = array_key_exists('non_functional_requirements', $brief) ? ($brief['non_functional_requirements'] ?? []) : null;
    $dependencies = $brief['dependencies'] ?? [];
    $improvements = $brief['improvements'] ?? [];
    $technical = $brief['technical'] ?? [];
    $prompt = $brief['prompt'] ?? '';
    $placeholder = $labels['section_placeholder'] ?? '— To be filled later —';

    $specBoxes = [
        ['heading' => $labels['ui_flow'] ?? 'UI Flow', 'items' => $uiFlow, 'ordered' => true],
        ['heading' => $labels['functional'] ?? 'Functional Requirements', 'items' => $functional, 'ordered' => true],
        ['heading' => $labels['fields'] ?? 'Fields', 'items' => $fields, 'fields' => true],
        ['heading' => $labels['validation'] ?? 'Validation', 'items' => $validation],
        ['heading' => $labels['database'] ?? 'Database', 'items' => $database],
        ['heading' => $labels['api'] ?? 'API', 'items' => $api],
        ['heading' => $labels['permissions'] ?? 'Permissions', 'items' => $permissions],
        ['heading' => $labels['edge_cases'] ?? 'Edge Cases', 'items' => $edgeCases],
        ['heading' => $labels['business_rules'] ?? 'Business Rules', 'items' => $businessRules ?? [], 'show' => $businessRules !== null],
        ['heading' => $labels['non_functional'] ?? 'Non-Functional Requirements', 'items' => $nonFunctional ?? [], 'show' => $nonFunctional !== null],
        ['heading' => $labels['dependencies'] ?? 'Dependencies / Related Modules', 'items' => $dependencies],
        ['heading' => $labels['technical'] ?? 'Technical', 'items' => $technical],
    ];
@endphp

@if ($title !== '')
    <h1>{{ $title }}</h1>
@endif

@if (filled($summary))
    <p class="summary">{{ $summary }}</p>
@endif

<div class="brief-section brief-section--current">
    <h2><span class="section-number">1</span>{{ $labels['current'] }}</h2>
    @foreach ($article as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach

    @foreach ($specBoxes as $box)
        @continue(! ($box['show'] ?? ! empty($box['items'])))
        <div class="inner-box">
            <h3>{{ $box['heading'] }}</h3>
            @if (empty($box['items']))
                <p class="placeholder">{{ $placeholder }}</p>
            @elseif (! empty($box['fields']))
                <ul>
                    @foreach ($box['items'] as $field)
                        <li>
                            <strong>{{ $field['label'] ?? ($field['name'] ?? '') }}</strong>
                            @if (! empty($field['name']))
                                ({{ $field['name'] }})
                            @endif
                            @if (! empty($field['note']))
                                — {{ $field['note'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif (! empty($box['ordered']))
                <ol>
                    @foreach ($box['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            @else
                <ul>
                    @foreach ($box['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</div>

@if ($improvements !== [])
    <div class="brief-section brief-section--improvements">
        <h2><span class="section-number">2</span>{{ $labels['improvements'] }}</h2>
        @foreach ($improvements as $item)
            <div class="improve-card">☐ {{ $item['text'] ?? '' }}</div>
        @endforeach
    </div>
@endif

@if (filled($prompt))
    <div class="brief-section brief-section--prompt">
        <h2><span class="section-number">3</span>{{ $labels['prompt'] }}</h2>
        <pre class="prompt">{{ trim($prompt) }}</pre>
    </div>
@endif
