@php
    $locale = in_array(($localeTab ?? 'ur'), ['en', 'ur'], true) ? $localeTab : 'ur';
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
    $suggestLabels = [
        'keep' => __('documentation.brief.suggest_keep', [], $locale),
        'add' => __('documentation.brief.suggest_add', [], $locale),
        'remove' => __('documentation.brief.suggest_remove', [], $locale),
        'optional' => __('documentation.brief.suggest_optional', [], $locale),
    ];
@endphp

@php
    $specBoxes = [
        ['key' => 'ui_flow', 'heading' => 'ui_flow_heading', 'items' => $uiFlow, 'ordered' => true],
        ['key' => 'functional', 'heading' => 'functional_heading', 'items' => $functional, 'ordered' => true],
        ['key' => 'fields', 'heading' => 'fields_heading', 'items' => $fields, 'ordered' => false, 'fields' => true],
        ['key' => 'validation', 'heading' => 'validation_heading', 'items' => $validation, 'ordered' => false],
        ['key' => 'database', 'heading' => 'database_heading', 'items' => $database, 'ordered' => false],
        ['key' => 'api', 'heading' => 'api_heading', 'items' => $api, 'ordered' => false],
        ['key' => 'permissions', 'heading' => 'permissions_heading', 'items' => $permissions, 'ordered' => false],
        ['key' => 'edge_cases', 'heading' => 'edge_cases_heading', 'items' => $edgeCases, 'ordered' => false],
        ['key' => 'business_rules', 'heading' => 'business_rules_heading', 'items' => $businessRules ?? [], 'ordered' => false, 'show' => $businessRules !== null],
        ['key' => 'non_functional', 'heading' => 'non_functional_heading', 'items' => $nonFunctional ?? [], 'ordered' => false, 'show' => $nonFunctional !== null],
        ['key' => 'dependencies', 'heading' => 'dependencies_heading', 'items' => $dependencies, 'ordered' => false],
        ['key' => 'technical', 'heading' => 'technical_heading', 'items' => $technical, 'ordered' => false],
    ];
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

    @if (filled($summary))
        <p class="agricart-docs-brief__summary">{{ $summary }}</p>
    @endif

    <section class="agricart-docs-brief__box agricart-docs-brief__box--current">
        <header class="agricart-docs-brief__box-head">
            <span class="agricart-docs-brief__section-number">1</span>
            <h2>{{ __('documentation.brief.article_heading', [], $locale) }}</h2>
        </header>
        <div class="agricart-docs-brief__box-body">
            @foreach ($article as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach

            @foreach ($specBoxes as $box)
                @continue(! ($box['show'] ?? ! empty($box['items'])))
                <div class="agricart-docs-brief__inner-box">
                    <h3>{{ __('documentation.brief.'.$box['heading'], [], $locale) }}</h3>
                    @if (empty($box['items']))
                        <p class="agricart-docs-brief__placeholder">{{ __('documentation.brief.section_placeholder', [], $locale) }}</p>
                    @elseif (! empty($box['fields']))
                        <ul class="agricart-docs-brief__fields">
                            @foreach ($box['items'] as $field)
                                <li>
                                    <strong>{{ $field['label'] ?? ($field['name'] ?? '') }}</strong>
                                    @if (! empty($field['name']))
                                        <code>{{ $field['name'] }}</code>
                                    @endif
                                    @if (! empty($field['note']))
                                        — {{ $field['note'] }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @elseif (! empty($box['ordered']))
                        <ol class="agricart-docs-brief__spec-list">
                            @foreach ($box['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ol>
                    @else
                        <ul class="agricart-docs-brief__spec-list">
                            @foreach ($box['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    @if ($improvements !== [])
        <section class="agricart-docs-brief__box agricart-docs-brief__box--improve">
            <header class="agricart-docs-brief__box-head">
                <span class="agricart-docs-brief__section-number">2</span>
                <h2>{{ __('documentation.brief.improvements_heading', [], $locale) }}</h2>
            </header>
            <div class="agricart-docs-brief__box-body">
                <p class="agricart-docs-brief__hint">{{ __('documentation.brief.improvements_hint', [], $locale) }}</p>
                <div class="agricart-docs-brief__improve-grid">
                    @foreach ($improvements as $item)
                        @php
                            $suggest = $item['suggest'] ?? 'optional';
                        @endphp
                        <div class="agricart-docs-brief__improve-card">
                            <span class="agricart-docs-brief__tick" aria-hidden="true">☐</span>
                            <span class="agricart-docs-brief__improve-text">{{ $item['text'] ?? '' }}</span>
                            <span @class(['agricart-docs-brief__tag', 'is-' . $suggest])>
                                {{ $suggestLabels[$suggest] ?? $suggest }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (filled($prompt))
        <section class="agricart-docs-brief__box agricart-docs-brief__box--prompt">
            <header class="agricart-docs-brief__box-head">
                <div class="agricart-docs-brief__prompt-header">
                    <h2>
                        <span class="agricart-docs-brief__section-number">3</span>
                        {{ __('documentation.brief.prompt_heading', [], $locale) }}
                    </h2>
                    <button type="button" class="agricart-docs-brief__copy" x-on:click="copyPrompt()">
                        <span x-show="!copied">{{ __('documentation.brief.copy_prompt', [], $locale) }}</span>
                        <span x-cloak x-show="copied">{{ __('documentation.brief.copied', [], $locale) }}</span>
                    </button>
                </div>
            </header>
            <div class="agricart-docs-brief__box-body">
                <p class="agricart-docs-brief__hint">{{ __('documentation.brief.prompt_hint', [], $locale) }}</p>
                <pre class="agricart-docs-brief__prompt" x-ref="promptText">{{ trim($prompt) }}</pre>
            </div>
        </section>
    @endif
</div>
