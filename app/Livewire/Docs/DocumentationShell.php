<?php

namespace App\Livewire\Docs;

use App\Filament\Clusters\Docs\GuidePage;
use App\Services\Documentation\DocumentationPdfExporter;
use App\Support\Documentation\DocumentationRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentationShell extends Component
{
    #[Url(as: 'm')]
    public string $moduleKey = '';

    #[Url(as: 'p')]
    public string $pageKey = '';

    #[Url(as: 'lang')]
    public string $localeTab = 'ur';

    /** guide = detailed rebuild docs, brief = simple Urdu handoff for new build */
    #[Url(as: 'mode')]
    public string $viewMode = 'brief';

    /** list | form | page */
    #[Url(as: 'kind')]
    public string $briefKind = '';

    public function mount(): void
    {
        $accessible = array_column(DocumentationRegistry::accessibleModules(), 'key');

        if (
            $this->moduleKey === ''
            || ! DocumentationRegistry::moduleExists($this->moduleKey)
            || ! in_array($this->moduleKey, $accessible, true)
        ) {
            $this->moduleKey = $accessible[0] ?? DocumentationRegistry::defaultModuleKey();
        }

        if ($this->pageKey === '' || ! DocumentationRegistry::pageExists($this->moduleKey, $this->pageKey)) {
            $this->pageKey = DocumentationRegistry::defaultPageKey($this->moduleKey);
        }

        if (! in_array($this->localeTab, ['en', 'ur'], true)) {
            $this->localeTab = 'ur';
        }

        if (! in_array($this->viewMode, ['guide', 'brief'], true)) {
            $this->viewMode = 'brief';
        }

        $this->syncBriefKind();
    }

    public function selectModule(string $moduleKey): void
    {
        if (! DocumentationRegistry::moduleExists($moduleKey) || ! DocumentationRegistry::userCanReadModule($moduleKey)) {
            return;
        }

        $this->moduleKey = $moduleKey;
        $this->pageKey = DocumentationRegistry::defaultPageKey($moduleKey);
        $this->briefKind = '';
        $this->syncBriefKind();
    }

    public function selectPage(string $pageKey): void
    {
        if (! DocumentationRegistry::pageExists($this->moduleKey, $pageKey)) {
            return;
        }

        $this->pageKey = $pageKey;
        $this->briefKind = '';
        $this->syncBriefKind();
    }

    public function setLocaleTab(string $tab): void
    {
        if (! in_array($tab, ['en', 'ur'], true)) {
            return;
        }

        $this->localeTab = $tab;
        $this->syncBriefKind();
    }

    public function setViewMode(string $mode): void
    {
        // User guide is the only reader mode (detailed handbook hidden from UI).
        $this->viewMode = 'brief';
        $this->syncBriefKind();
    }

    public function setBriefKind(string $kind): void
    {
        $kinds = DocumentationRegistry::briefKinds($this->moduleKey, $this->pageKey, $this->localeTab);

        if (! in_array($kind, $kinds, true)) {
            return;
        }

        $this->briefKind = $kind;
    }

    public function downloadPagePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->page($this->moduleKey, $this->pageKey, $this->localeTab);

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadModulePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->module($this->moduleKey, $this->localeTab);

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadBookPdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->book($this->localeTab);

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadBriefPagePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->briefPage(
            $this->moduleKey,
            $this->pageKey,
            $this->localeTab,
            $this->briefKind !== '' ? $this->briefKind : null,
        );

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadBriefModulePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->briefModule($this->moduleKey, $this->localeTab);

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadBriefBookPdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->briefBook($this->localeTab);

        return response()->streamDownload(
            fn () => print ($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function pageMeta(): array
    {
        foreach (DocumentationRegistry::pagesForModule($this->moduleKey) as $page) {
            if ($page['key'] === $this->pageKey) {
                return $page;
            }
        }

        return [
            'key' => $this->pageKey,
            'label' => DocumentationRegistry::pageLabel($this->moduleKey, $this->pageKey),
            'implemented' => false,
            'documented' => false,
            'has_brief' => false,
            'sort' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function article(): ?array
    {
        return DocumentationRegistry::article($this->moduleKey, $this->pageKey, $this->localeTab);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function brief(): ?array
    {
        if ($this->briefKind === '') {
            return null;
        }

        return DocumentationRegistry::brief($this->moduleKey, $this->pageKey, $this->briefKind, $this->localeTab);
    }

    /**
     * @return list<string>
     */
    public function briefKinds(): array
    {
        return DocumentationRegistry::briefKinds($this->moduleKey, $this->pageKey, $this->localeTab);
    }

    public function guideUrl(string $moduleKey, string $pageKey): string
    {
        return GuidePage::guideUrl($moduleKey, $pageKey);
    }

    protected function syncBriefKind(): void
    {
        $kinds = $this->briefKinds();

        if ($kinds === []) {
            $this->briefKind = '';

            return;
        }

        if ($this->briefKind === '' || ! in_array($this->briefKind, $kinds, true)) {
            $this->briefKind = in_array('form', $kinds, true) ? 'form' : $kinds[0];
        }
    }

    public function render(): View
    {
        return view('livewire.docs.documentation-shell', [
            'modules' => DocumentationRegistry::accessibleModules(),
            'pages' => DocumentationRegistry::pagesForModule($this->moduleKey),
            'meta' => $this->pageMeta(),
            'article' => $this->article(),
            'brief' => $this->brief(),
            'briefKinds' => $this->briefKinds(),
        ]);
    }
}
