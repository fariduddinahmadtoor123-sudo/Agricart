@php
    $title = $article['title'] ?? ($article['meta']['title'] ?? '');
    $summary = $article['summary'] ?? ($article['meta']['summary'] ?? null);
    $meta = $article['meta'] ?? [];
    $sections = $article['sections'] ?? [];
@endphp

<div
    @class([
        'agricart-docs-article',
        'agricart-docs-article--ur' => ($localeTab ?? 'en') === 'ur',
    ])
    dir="{{ ($localeTab ?? 'en') === 'ur' ? 'rtl' : 'ltr' }}"
>
    @if ($title !== '')
        <h1 class="agricart-docs-article__title">{{ $title }}</h1>
    @endif

    @if (filled($summary))
        <p class="agricart-docs-article__summary">{{ $summary }}</p>
    @endif

    @if ($meta !== [])
        <div class="agricart-docs-article__meta">
            @if (! empty($meta['routes']))
                <p><strong>{{ __('documentation.meta.routes') }}:</strong> {{ implode(', ', (array) $meta['routes']) }}</p>
            @endif
            @if (! empty($meta['permissions']))
                <p><strong>{{ __('documentation.meta.permissions') }}:</strong> {{ implode(', ', (array) $meta['permissions']) }}</p>
            @endif
            @if (! empty($meta['related']))
                <p><strong>{{ __('documentation.meta.related') }}:</strong> {{ implode(', ', (array) $meta['related']) }}</p>
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
            $screenshot = (bool) ($section['screenshot_placeholder'] ?? false);
        @endphp

        <section
            @class([
                'agricart-docs-article__section',
                'agricart-docs-article__section--' . $type,
                'agricart-docs-article__section--rebuild' => $type === 'rebuild_notes' || $sectionId === 'rebuild_notes',
            ])
            @if (filled($sectionId)) id="doc-{{ $sectionId }}" @endif
        >
            @if (filled($heading))
                <h2 class="agricart-docs-article__heading">{{ $heading }}</h2>
            @endif

            @foreach ($paragraphs as $paragraph)
                <p class="agricart-docs-article__paragraph">{{ $paragraph }}</p>
            @endforeach

            @if ($list !== [])
                <ul class="agricart-docs-article__list">
                    @foreach ($list as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($fields !== [])
                <div class="agricart-docs-fields">
                    <table class="agricart-docs-fields__table">
                        <thead>
                            <tr>
                                <th>{{ __('documentation.fields.name') }}</th>
                                <th>{{ __('documentation.fields.label') }}</th>
                                <th>{{ __('documentation.fields.type') }}</th>
                                <th>{{ __('documentation.fields.required') }}</th>
                                <th>{{ __('documentation.fields.rules') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fields as $field)
                                <tr>
                                    <td><code>{{ $field['name'] ?? '' }}</code></td>
                                    <td>{{ $field['label'] ?? '' }}</td>
                                    <td>{{ $field['type'] ?? '' }}</td>
                                    <td>{{ ! empty($field['required']) ? __('documentation.fields.yes') : __('documentation.fields.no') }}</td>
                                    <td>
                                        @php
                                            $rules = $field['rules'] ?? ($field['notes'] ?? '');
                                            if (is_array($rules)) {
                                                $rules = implode('; ', array_map(strval(...), $rules));
                                            }
                                        @endphp
                                        {{ $rules }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($steps !== [])
                <ol class="agricart-docs-article__steps">
                    @foreach ($steps as $step)
                        <li>{{ is_array($step) ? ($step['text'] ?? implode(' ', $step)) : $step }}</li>
                    @endforeach
                </ol>
            @endif

            @if ($classes !== [])
                <dl class="agricart-docs-technical">
                    @foreach ($classes as $key => $value)
                        <div class="agricart-docs-technical__row">
                            <dt>{{ is_string($key) ? str($key)->headline() : $key }}</dt>
                            <dd>
                                @if (is_array($value))
                                    <ul class="agricart-docs-article__list">
                                        @foreach ($value as $item)
                                            <li><code>{{ $item }}</code></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <code>{{ $value }}</code>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if ($acceptance !== [])
                <ul class="agricart-docs-article__list agricart-docs-article__list--acceptance">
                    @foreach ($acceptance as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($screenshot)
                <div class="agricart-docs-screenshot-placeholder" aria-hidden="true">
                    {{ __('documentation.screenshot_placeholder') }}
                </div>
            @endif
        </section>
    @endforeach
</div>
