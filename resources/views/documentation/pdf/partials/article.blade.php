@php
    $title = $article['title'] ?? ($article['meta']['title'] ?? '');
    $summary = $article['summary'] ?? ($article['meta']['summary'] ?? null);
    $meta = $article['meta'] ?? [];
    $sections = $article['sections'] ?? [];
@endphp

@if (filled($summary))
    <p class="chapter__summary">{{ $summary }}</p>
@endif

@if ($meta !== [])
    <div class="meta-box">
        @if (! empty($meta['routes']))
            <p><strong>{{ $labels['routes'] }}:</strong> {{ implode(', ', (array) $meta['routes']) }}</p>
        @endif
        @if (! empty($meta['permissions']))
            <p><strong>{{ $labels['permissions'] }}:</strong> {{ implode(', ', (array) $meta['permissions']) }}</p>
        @endif
        @if (! empty($meta['related']))
            <p><strong>{{ $labels['related'] }}:</strong> {{ implode(', ', (array) $meta['related']) }}</p>
        @endif
    </div>
@endif

@foreach ($sections as $section)
    @php
        $sectionId = $section['id'] ?? null;
        $type = $section['type'] ?? 'prose';
        $heading = $section['heading'] ?? null;
        $paragraphs = $section['paragraphs'] ?? [];
        $list = $section['list'] ?? [];
        $fields = $section['fields'] ?? [];
        $steps = $section['steps'] ?? [];
        $classes = $section['classes'] ?? [];
        $acceptance = $section['acceptance'] ?? [];
        $isRebuild = $type === 'rebuild_notes' || $sectionId === 'rebuild_notes' || $sectionId === 'rebuild-notes';
    @endphp

    <div @class(['section', 'section--rebuild' => $isRebuild])>
        @if (filled($heading))
            <h2>{{ $heading }}</h2>
        @endif

        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach

        @if ($list !== [])
            <ul>
                @foreach ($list as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif

        @if ($fields !== [])
            <table>
                <thead>
                    <tr>
                        <th>{{ $labels['field_name'] }}</th>
                        <th>{{ $labels['field_label'] }}</th>
                        <th>{{ $labels['field_type'] }}</th>
                        <th>{{ $labels['field_required'] }}</th>
                        <th>{{ $labels['field_rules'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fields as $field)
                        @php
                            $rules = $field['rules'] ?? ($field['notes'] ?? '');
                            if (is_array($rules)) {
                                $rules = implode('; ', array_map('strval', $rules));
                            }
                        @endphp
                        <tr>
                            <td><code>{{ $field['name'] ?? '' }}</code></td>
                            <td>{{ $field['label'] ?? '' }}</td>
                            <td>{{ $field['type'] ?? '' }}</td>
                            <td>{{ ! empty($field['required']) ? $labels['yes'] : $labels['no'] }}</td>
                            <td>{{ $rules }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($steps !== [])
            <ol>
                @foreach ($steps as $step)
                    <li>{{ is_array($step) ? ($step['text'] ?? implode(' ', $step)) : $step }}</li>
                @endforeach
            </ol>
        @endif

        @if ($classes !== [])
            @foreach ($classes as $key => $value)
                <p>
                    <strong>{{ is_string($key) ? str($key)->headline() : $key }}:</strong>
                    @if (is_array($value))
                        {{ implode(', ', $value) }}
                    @else
                        {{ $value }}
                    @endif
                </p>
            @endforeach
        @endif

        @if ($acceptance !== [])
            <ul>
                @foreach ($acceptance as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
