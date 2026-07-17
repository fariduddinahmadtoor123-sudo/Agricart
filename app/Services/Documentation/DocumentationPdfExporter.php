<?php

namespace App\Services\Documentation;

use App\Support\AgricartTranslator;
use App\Support\Documentation\DocumentationRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Str;

class DocumentationPdfExporter
{
    /**
     * @return array{pdf: DomPdf, filename: string}
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
     * @return array{pdf: DomPdf, filename: string}
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
     * @return array{pdf: DomPdf, filename: string}
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
     * @return array{pdf: DomPdf, filename: string}
     */
    protected function makePdf(
        string $scope,
        string $title,
        string $locale,
        array $chapters,
        string $filename,
        ?string $moduleLabel = null,
    ): array {
        $pdf = Pdf::loadView('documentation.pdf.book', [
            'scope' => $scope,
            'title' => $title,
            'locale' => $locale,
            'moduleLabel' => $moduleLabel,
            'chapters' => $chapters,
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ])->setPaper('a4');

        $pdf->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);

        return [
            'pdf' => $pdf,
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

        return ($slug !== '' ? $slug : 'agricart-docs') . '.pdf';
    }

    protected function normalizeLocale(string $locale): string
    {
        return in_array($locale, ['en', 'ur'], true) ? $locale : 'en';
    }
}
