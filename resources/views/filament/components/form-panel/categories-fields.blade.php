@php
    use App\Filament\Forms\CategoryForm;

    $sectionsByKey = collect(CategoryForm::sections())->keyBy('key');
    $discovery = $sectionsByKey->get('discovery');
    $sectionsWithoutTitle = ['discovery', 'core'];
    $scrollAnchors = CategoryForm::stepScrollAnchors();
@endphp

<div class="agricart-category-form">
    {{-- Frozen hero: search + icons + EN/UR names — do not change layout --}}
    @if ($discovery)
        <div class="agricart-category-form__hero" id="agricart-cat-section-{{ $scrollAnchors[1] }}">
            @php
                $discoveryFields = collect($discovery['fields']);
                $searchField = $discoveryFields->firstWhere('type', 'category_search');
                $iconsField = $discoveryFields->firstWhere('type', 'visual_mapping');
                $namesField = $discoveryFields->firstWhere('type', 'category_names');
            @endphp

            @if ($searchField)
                @include('filament.components.form-panel.category-field', ['field' => $searchField])
            @endif

            <div class="agricart-category-form__hero-row">
                @if ($iconsField)
                    <div class="agricart-category-form__hero-icons">
                        @include('filament.components.form-panel.category-field', ['field' => $iconsField])
                    </div>
                @endif

                @if ($namesField)
                    <div class="agricart-category-form__hero-names">
                        @include('filament.components.form-panel.category-field', ['field' => $namesField])
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- All remaining fields — visible on scroll --}}
    <div class="agricart-category-form__details">
        @foreach (CategoryForm::detailSectionKeys() as $sectionKey)
            @php
                $section = $sectionsByKey->get($sectionKey);

                if (! $section) {
                    continue;
                }

                $showTitle = ! in_array($sectionKey, $sectionsWithoutTitle, true);
                $anchorId = in_array($sectionKey, array_values($scrollAnchors), true)
                    ? 'agricart-cat-section-' . $sectionKey
                    : null;
            @endphp

            <section
                @if ($anchorId) id="{{ $anchorId }}" @endif
                @class([
                    'agricart-form-panel__section',
                    'agricart-category-form__section',
                    'agricart-category-form__section--' . $sectionKey,
                ])
            >
                @if ($showTitle && filled($section['label'] ?? null))
                    <h3 class="agricart-form-panel__section-title">{{ $section['label'] }}</h3>
                @endif

                <div class="agricart-form-panel__fields">
                    @foreach ($section['fields'] as $field)
                        @include('filament.components.form-panel.category-field', ['field' => $field])
                    @endforeach
                </div>

                @if ($sectionKey === 'system')
                    <p class="agricart-form-panel__hint agricart-category-form__ai-hint">
                        {{ __('agricart.categories.hints.ai_enrichment') }}
                    </p>
                @endif
            </section>
        @endforeach
    </div>
</div>
