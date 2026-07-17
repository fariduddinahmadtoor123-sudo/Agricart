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
                            @if (! empty($page['has_brief']))
                                <span class="agricart-docs-shell__dot is-brief" title="{{ __('documentation.badges.brief') }}"></span>
                            @elseif ($page['documented'])
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
                <div class="agricart-docs-shell__header-left">
                    <div class="agricart-docs-shell__mode-tabs">
                        <button
                            type="button"
                            wire:click="setViewMode('brief')"
                            @class(['agricart-docs-shell__mode-tab', 'is-active' => $viewMode === 'brief'])
                        >
                            {{ __('documentation.modes.brief', [], $localeTab) }}
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('guide')"
                            @class(['agricart-docs-shell__mode-tab', 'is-active' => $viewMode === 'guide'])
                        >
                            {{ __('documentation.modes.guide', [], $localeTab) }}
                        </button>
                    </div>

                    <div class="agricart-docs-shell__locale-tabs">
                        <button
                            type="button"
                            wire:click="setLocaleTab('ur')"
                            @class(['agricart-docs-shell__locale-tab', 'is-active' => $localeTab === 'ur'])
                        >
                            {{ __('documentation.locale.urdu', [], $localeTab) }}
                        </button>
                        <button
                            type="button"
                            wire:click="setLocaleTab('en')"
                            @class(['agricart-docs-shell__locale-tab', 'is-active' => $localeTab === 'en'])
                        >
                            {{ __('documentation.locale.english', [], $localeTab) }}
                        </button>
                    </div>
                </div>

                <div class="agricart-docs-shell__downloads" role="group" aria-label="{{ __('documentation.pdf.group_label', [], $localeTab) }}">
                    @if ($viewMode === 'brief')
                        <button type="button" class="agricart-docs-shell__download-btn" wire:click="downloadBriefPagePdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.brief_page_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadBriefPagePdf">{{ __('documentation.pdf.brief_page', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadBriefPagePdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                        <button type="button" class="agricart-docs-shell__download-btn" wire:click="downloadBriefModulePdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.brief_module_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadBriefModulePdf">{{ __('documentation.pdf.brief_module', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadBriefModulePdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                        <button type="button" class="agricart-docs-shell__download-btn agricart-docs-shell__download-btn--primary" wire:click="downloadBriefBookPdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.brief_book_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadBriefBookPdf">{{ __('documentation.pdf.brief_book', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadBriefBookPdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                    @else
                        <button type="button" class="agricart-docs-shell__download-btn" wire:click="downloadPagePdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.page_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadPagePdf">{{ __('documentation.pdf.page', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadPagePdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                        <button type="button" class="agricart-docs-shell__download-btn" wire:click="downloadModulePdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.module_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadModulePdf">{{ __('documentation.pdf.module', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadModulePdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                        <button type="button" class="agricart-docs-shell__download-btn agricart-docs-shell__download-btn--primary" wire:click="downloadBookPdf" wire:loading.attr="disabled" wire:target="downloadBriefPagePdf,downloadBriefModulePdf,downloadBriefBookPdf,downloadPagePdf,downloadModulePdf,downloadBookPdf" title="{{ __('documentation.pdf.book_hint', [], $localeTab) }}">
                            <span wire:loading.remove wire:target="downloadBookPdf">{{ __('documentation.pdf.book', [], $localeTab) }}</span>
                            <span wire:loading wire:target="downloadBookPdf">{{ __('documentation.pdf.preparing', [], $localeTab) }}</span>
                        </button>
                    @endif
                </div>
            </header>

            @if ($viewMode === 'brief')
                <div class="agricart-docs-shell__howto" role="note">
                    {{ __('documentation.brief.howto', [], $localeTab) }}
                </div>
                @if (count($briefKinds) > 1)
                    <div class="agricart-docs-shell__kind-tabs">
                        @foreach ($briefKinds as $kind)
                            <button
                                type="button"
                                wire:click="setBriefKind('{{ $kind }}')"
                                @class(['agricart-docs-shell__kind-tab', 'is-active' => $briefKind === $kind])
                            >
                                {{ __('documentation.brief.kinds.' . $kind, [], $localeTab) }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($brief)
                    @include('documentation.partials.brief', ['brief' => $brief, 'localeTab' => $localeTab])
                @else
                    <div class="agricart-docs-shell__fallback">
                        <h2 class="agricart-docs-shell__title">{{ $meta['label'] }}</h2>
                        <p>{{ __('documentation.brief.missing', [], $localeTab) }}</p>
                        <p>{{ __('documentation.brief.missing_hint', [], $localeTab) }}</p>
                    </div>
                @endif
            @elseif ($article)
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
