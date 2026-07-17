<div class="agricart-docs-shell">
    <nav class="agricart-docs-shell__modules" aria-label="{{ __('documentation.nav.modules') }}">
        @foreach ($modules as $module)
            <button
                type="button"
                wire:click="selectModule('{{ $module['key'] }}')"
                @class([
                    'agricart-docs-shell__module-tab',
                    'is-active' => $moduleKey === $module['key'],
                    'is-muted' => $module['comingSoon'],
                ])
            >
                {{ $module['label'] }}
            </button>
        @endforeach
    </nav>

    <div class="agricart-docs-shell__body">
        <aside class="agricart-docs-shell__sidebar" aria-label="{{ __('documentation.nav.pages') }}">
            <ul class="agricart-docs-shell__page-list">
                @foreach ($pages as $page)
                    <li>
                        <button
                            type="button"
                            wire:click="selectPage('{{ $page['key'] }}')"
                            @class([
                                'agricart-docs-shell__page-link',
                                'is-active' => $pageKey === $page['key'],
                            ])
                        >
                            <span>{{ $page['label'] }}</span>
                            @if ($page['documented'])
                                <span class="agricart-docs-shell__dot is-documented" title="{{ __('documentation.badges.documented') }}"></span>
                            @elseif ($page['implemented'])
                                <span class="agricart-docs-shell__dot is-live" title="{{ __('documentation.badges.live') }}"></span>
                            @else
                                <span class="agricart-docs-shell__dot is-planned" title="{{ __('documentation.badges.planned') }}"></span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </aside>

        <article class="agricart-docs-shell__article">
            <header class="agricart-docs-shell__article-header">
                <div class="agricart-docs-shell__locale-tabs">
                    <button
                        type="button"
                        wire:click="setLocaleTab('en')"
                        @class(['agricart-docs-shell__locale-tab', 'is-active' => $localeTab === 'en'])
                    >
                        {{ __('documentation.locale.english') }}
                    </button>
                    <button
                        type="button"
                        wire:click="setLocaleTab('ur')"
                        @class(['agricart-docs-shell__locale-tab', 'is-active' => $localeTab === 'ur'])
                    >
                        {{ __('documentation.locale.urdu') }}
                    </button>
                </div>

                <div class="agricart-docs-shell__downloads" role="group" aria-label="{{ __('documentation.pdf.group_label') }}">
                    <button
                        type="button"
                        class="agricart-docs-shell__download-btn"
                        wire:click="downloadPagePdf"
                        wire:loading.attr="disabled"
                        wire:target="downloadPagePdf,downloadModulePdf,downloadBookPdf"
                        title="{{ __('documentation.pdf.page_hint') }}"
                    >
                        <span wire:loading.remove wire:target="downloadPagePdf">{{ __('documentation.pdf.page') }}</span>
                        <span wire:loading wire:target="downloadPagePdf">{{ __('documentation.pdf.preparing') }}</span>
                    </button>
                    <button
                        type="button"
                        class="agricart-docs-shell__download-btn"
                        wire:click="downloadModulePdf"
                        wire:loading.attr="disabled"
                        wire:target="downloadPagePdf,downloadModulePdf,downloadBookPdf"
                        title="{{ __('documentation.pdf.module_hint') }}"
                    >
                        <span wire:loading.remove wire:target="downloadModulePdf">{{ __('documentation.pdf.module') }}</span>
                        <span wire:loading wire:target="downloadModulePdf">{{ __('documentation.pdf.preparing') }}</span>
                    </button>
                    <button
                        type="button"
                        class="agricart-docs-shell__download-btn agricart-docs-shell__download-btn--primary"
                        wire:click="downloadBookPdf"
                        wire:loading.attr="disabled"
                        wire:target="downloadPagePdf,downloadModulePdf,downloadBookPdf"
                        title="{{ __('documentation.pdf.book_hint') }}"
                    >
                        <span wire:loading.remove wire:target="downloadBookPdf">{{ __('documentation.pdf.book') }}</span>
                        <span wire:loading wire:target="downloadBookPdf">{{ __('documentation.pdf.preparing') }}</span>
                    </button>
                </div>
            </header>

            @if ($article)
                @include('documentation.partials.article', ['article' => $article, 'localeTab' => $localeTab])
            @elseif ($localeTab === 'ur' && \App\Support\Documentation\DocumentationRegistry::hasArticle($moduleKey, $pageKey, 'en'))
                <div class="agricart-docs-shell__fallback">
                    <h2 class="agricart-docs-shell__title">{{ $meta['label'] }}</h2>
                    <p>{{ __('documentation.fallback.no_ur_translation') }}</p>
                </div>
            @else
                <div class="agricart-docs-shell__fallback">
                    <h2 class="agricart-docs-shell__title">{{ $meta['label'] }}</h2>
                    @if ($meta['implemented'])
                        <p>{{ __('documentation.fallback.implemented_pending') }}</p>
                    @else
                        <p>{{ __('documentation.fallback.not_implemented') }}</p>
                    @endif
                </div>
            @endif
        </article>
    </div>
</div>
