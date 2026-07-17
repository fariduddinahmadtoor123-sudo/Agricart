<?php

namespace App\Services\Documentation;

use App\Support\AgricartTranslator;
use App\Support\Documentation\DocumentationPdfText;
use App\Support\Documentation\DocumentationRegistry;
use Illuminate\Support\Str;

class DocumentationPdfExporter
{
    /**
     * @return array{pdf: object, filename: string}
     */
    public function page(string $moduleKey, string $pageKey, string $locale = 'en'): array
    {
        $locale = $this->normalizeLocale($locale);
        $chapters = [$this->buildChapter($moduleKey, $pageKey, $locale)];

        return $this->makePdf(
            scope: 'page',
            title: $chapters[0]['title'],
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-docs', $moduleKey, $pageKey, $locale]),
        );
    }

    /**
     * @return array{pdf: object, filename: string}
     */
    public function module(string $moduleKey, string $locale = 'en'): array
    {
        $locale = $this->normalizeLocale($locale);

        if (! DocumentationRegistry::moduleExists($moduleKey)) {
            abort(404, 'Documentation module not found.');
        }

        $chapters = [];

        foreach (DocumentationRegistry::pagesForModule($moduleKey) as $page) {
            $chapters[] = $this->buildChapter($moduleKey, $page['key'], $locale, $page);
        }

        $moduleLabel = AgricartTranslator::module($moduleKey);

        return $this->makePdf(
            scope: 'module',
            title: $moduleLabel,
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-docs', $moduleKey, $locale]),
            moduleLabel: $moduleLabel,
        );
    }

    /**
     * @return array{pdf: object, filename: string}
     */
    public function book(string $locale = 'en'): array
    {
        $locale = $this->normalizeLocale($locale);
        $chapters = [];

        foreach (DocumentationRegistry::modules() as $module) {
            foreach (DocumentationRegistry::pagesForModule($module['key']) as $page) {
                $chapters[] = $this->buildChapter($module['key'], $page['key'], $locale, $page, $module['label']);
            }
        }

        return $this->makePdf(
            scope: 'book',
            title: __('documentation.pdf.book_title', [], $locale),
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-docs-book', $locale]),
        );
    }

    /**
     * @return array{pdf: object, filename: string}
     */
    public function briefPage(
        string $moduleKey,
        string $pageKey,
        string $locale = 'en',
        ?string $kind = null,
    ): array {
        $locale = $this->normalizeLocale($locale);
        $chapters = $this->briefChaptersForPage($moduleKey, $pageKey, $locale, $kind);

        if ($chapters === []) {
            abort(404, 'No simple brief for this page.');
        }

        $pageLabel = DocumentationRegistry::pageLabel($moduleKey, $pageKey);

        return $this->makeBriefPdf(
            scope: 'page',
            title: $pageLabel,
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-brief', $moduleKey, $pageKey, $kind ?? 'all', $locale]),
        );
    }

    /**
     * @return array{pdf: object, filename: string}
     */
    public function briefModule(string $moduleKey, string $locale = 'en'): array
    {
        $locale = $this->normalizeLocale($locale);

        if (! DocumentationRegistry::moduleExists($moduleKey)) {
            abort(404, 'Documentation module not found.');
        }

        $chapters = [];

        foreach (DocumentationRegistry::pagesForModule($moduleKey) as $page) {
            foreach ($this->briefChaptersForPage($moduleKey, $page['key'], $locale) as $chapter) {
                $chapters[] = $chapter;
            }
        }

        if ($chapters === []) {
            abort(404, 'No simple briefs in this module.');
        }

        $moduleLabel = AgricartTranslator::module($moduleKey);

        return $this->makeBriefPdf(
            scope: 'module',
            title: $moduleLabel,
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-brief', $moduleKey, $locale]),
        );
    }

    /**
     * @return array{pdf: object, filename: string}
     */
    public function briefBook(string $locale = 'en'): array
    {
        $locale = $this->normalizeLocale($locale);
        $chapters = [];

        foreach (DocumentationRegistry::modules() as $module) {
            foreach (DocumentationRegistry::pagesForModule($module['key']) as $page) {
                foreach ($this->briefChaptersForPage($module['key'], $page['key'], $locale) as $chapter) {
                    $chapters[] = $chapter;
                }
            }
        }

        if ($chapters === []) {
            abort(404, 'No simple briefs published.');
        }

        return $this->makeBriefPdf(
            scope: 'book',
            title: __('documentation.pdf.brief_book_title', [], $locale),
            locale: $locale,
            chapters: $chapters,
            filename: $this->filename(['agricart-brief-book', $locale]),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function briefChaptersForPage(
        string $moduleKey,
        string $pageKey,
        string $locale,
        ?string $onlyKind = null,
    ): array {
        $chapters = [];

        foreach (DocumentationRegistry::briefKinds($moduleKey, $pageKey, $locale) as $kind) {
            if ($onlyKind !== null && $kind !== $onlyKind) {
                continue;
            }

            $brief = DocumentationRegistry::brief($moduleKey, $pageKey, $kind, $locale);

            if ($brief === null) {
                continue;
            }

            $chapters[] = [
                'module_key' => $moduleKey,
                'module_label' => AgricartTranslator::module($moduleKey),
                'page_key' => $pageKey,
                'page_label' => DocumentationRegistry::pageLabel($moduleKey, $pageKey),
                'kind' => $kind,
                'kind_label' => __('documentation.brief.kinds.'.$kind, [], $locale),
                'brief' => $brief,
            ];
        }

        return $chapters;
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     * @return array{pdf: object, filename: string}
     */
    protected function makeBriefPdf(
        string $scope,
        string $title,
        string $locale,
        array $chapters,
        string $filename,
    ): array {
        $generatedAt = now()->timezone(config('app.timezone'))->format('Y-m-d H:i');

        $html = view('documentation.pdf.brief-book', [
            'scope' => $scope,
            'title' => $title,
            'locale' => $locale,
            'chapters' => $chapters,
            'generatedAt' => $generatedAt,
            'labels' => [
                'subtitle' => __('documentation.pdf.brief_book_subtitle', [], $locale),
                'generated_at' => __('documentation.pdf.generated_at', ['date' => $generatedAt], $locale),
                'scope' => __('documentation.pdf.scope_'.$scope, [], $locale),
                'toc' => __('documentation.pdf.toc', [], $locale),
                'current' => __('documentation.brief.article_heading', [], $locale),
                'fields' => __('documentation.brief.fields_heading', [], $locale),
                'ui_flow' => __('documentation.brief.ui_flow_heading', [], $locale),
                'functional' => __('documentation.brief.functional_heading', [], $locale),
                'validation' => __('documentation.brief.validation_heading', [], $locale),
                'database' => __('documentation.brief.database_heading', [], $locale),
                'api' => __('documentation.brief.api_heading', [], $locale),
                'permissions' => __('documentation.brief.permissions_heading', [], $locale),
                'edge_cases' => __('documentation.brief.edge_cases_heading', [], $locale),
                'business_rules' => __('documentation.brief.business_rules_heading', [], $locale),
                'non_functional' => __('documentation.brief.non_functional_heading', [], $locale),
                'dependencies' => __('documentation.brief.dependencies_heading', [], $locale),
                'section_placeholder' => __('documentation.brief.section_placeholder', [], $locale),
                'improvements' => __('documentation.brief.improvements_heading', [], $locale),
                'technical' => __('documentation.brief.technical_heading', [], $locale),
                'prompt' => __('documentation.brief.prompt_heading', [], $locale),
                'footer' => __('documentation.pdf.footer', [], $locale),
            ],
        ])->render();

        return [
            'pdf' => DocumentationPdfText::render($html, $locale),
            'filename' => $filename,
        ];
    }

    /**
     * @param  array{key?: string, label?: string, documented?: bool, implemented?: bool}|null  $pageMeta
     * @return array{
     *     module_key: string,
     *     module_label: string,
     *     page_key: string,
     *     page_label: string,
     *     title: string,
     *     article: array<string, mixed>|null,
     *     documented: bool,
     *     implemented: bool,
     * }
     */
    protected function buildChapter(
        string $moduleKey,
        string $pageKey,
        string $locale,
        ?array $pageMeta = null,
        ?string $moduleLabel = null,
    ): array {
        $pageMeta ??= [
            'key' => $pageKey,
            'label' => DocumentationRegistry::pageLabel($moduleKey, $pageKey),
            'documented' => DocumentationRegistry::hasArticle($moduleKey, $pageKey, 'en')
                || DocumentationRegistry::hasArticle($moduleKey, $pageKey, 'ur'),
            'implemented' => false,
        ];

        $article = DocumentationRegistry::article($moduleKey, $pageKey, $locale);

        if ($article === null && $locale === 'ur') {
            $article = DocumentationRegistry::article($moduleKey, $pageKey, 'en');
        }

        $pageLabel = $pageMeta['label'] ?? DocumentationRegistry::pageLabel($moduleKey, $pageKey);
        $title = is_array($article)
            ? (string) ($article['title'] ?? ($article['meta']['title'] ?? $pageLabel))
            : $pageLabel;

        return [
            'module_key' => $moduleKey,
            'module_label' => $moduleLabel ?? AgricartTranslator::module($moduleKey),
            'page_key' => $pageKey,
            'page_label' => $pageLabel,
            'title' => $title,
            'article' => $article,
            'documented' => (bool) ($pageMeta['documented'] ?? false),
            'implemented' => (bool) ($pageMeta['implemented'] ?? false),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     * @return array{pdf: object, filename: string}
     */
    protected function makePdf(
        string $scope,
        string $title,
        string $locale,
        array $chapters,
        string $filename,
        ?string $moduleLabel = null,
    ): array {
        $generatedAt = now()->timezone(config('app.timezone'))->format('Y-m-d H:i');

        $html = view('documentation.pdf.book', [
            'scope' => $scope,
            'title' => $title,
            'locale' => $locale,
            'moduleLabel' => $moduleLabel,
            'chapters' => $chapters,
            'generatedAt' => $generatedAt,
            'labels' => [
                'module_subtitle' => __('documentation.pdf.module_subtitle', ['module' => $moduleLabel], $locale),
                'generated_at' => __('documentation.pdf.generated_at', ['date' => $generatedAt], $locale),
                'language' => $locale === 'ur'
                    ? __('documentation.locale.urdu', [], $locale)
                    : __('documentation.locale.english', [], $locale),
                'scope' => __('documentation.pdf.scope_'.$scope, [], $locale),
                'toc' => __('documentation.pdf.toc', [], $locale),
                'implemented_pending' => __('documentation.fallback.implemented_pending', [], $locale),
                'not_implemented' => __('documentation.fallback.not_implemented', [], $locale),
                'footer' => __('documentation.pdf.footer', [], $locale),
                'routes' => __('documentation.meta.routes', [], $locale),
                'permissions' => __('documentation.meta.permissions', [], $locale),
                'related' => __('documentation.meta.related', [], $locale),
                'field_name' => __('documentation.fields.name', [], $locale),
                'field_label' => __('documentation.fields.label', [], $locale),
                'field_type' => __('documentation.fields.type', [], $locale),
                'field_required' => __('documentation.fields.required', [], $locale),
                'field_rules' => __('documentation.fields.rules', [], $locale),
                'yes' => __('documentation.fields.yes', [], $locale),
                'no' => __('documentation.fields.no', [], $locale),
            ],
        ])->render();

        return [
            'pdf' => DocumentationPdfText::render($html, $locale),
            'filename' => $filename,
        ];
    }

    /**
     * @param  list<string>  $parts
     */
    protected function filename(array $parts): string
    {
        $slug = collect($parts)
            ->map(fn (string $part): string => Str::slug($part, '-'))
            ->filter()
            ->implode('_');

        return ($slug !== '' ? $slug : 'agricart-docs').'.pdf';
    }

    protected function normalizeLocale(string $locale): string
    {
        return in_array($locale, ['en', 'ur'], true) ? $locale : 'en';
    }
}
