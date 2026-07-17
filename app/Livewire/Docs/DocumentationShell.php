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
    public string $localeTab = 'en';

    public function mount(): void
    {
        if ($this->moduleKey === '' || ! DocumentationRegistry::moduleExists($this->moduleKey)) {
            $this->moduleKey = DocumentationRegistry::defaultModuleKey();
        }

        if ($this->pageKey === '' || ! DocumentationRegistry::pageExists($this->moduleKey, $this->pageKey)) {
            $this->pageKey = DocumentationRegistry::defaultPageKey($this->moduleKey);
        }

        if (! in_array($this->localeTab, ['en', 'ur'], true)) {
            $this->localeTab = 'en';
        }
    }

    public function selectModule(string $moduleKey): void
    {
        if (! DocumentationRegistry::moduleExists($moduleKey)) {
            return;
        }

        $this->moduleKey = $moduleKey;
        $this->pageKey = DocumentationRegistry::defaultPageKey($moduleKey);
    }

    public function selectPage(string $pageKey): void
    {
        if (! DocumentationRegistry::pageExists($this->moduleKey, $pageKey)) {
            return;
        }

        $this->pageKey = $pageKey;
    }

    public function setLocaleTab(string $tab): void
    {
        if (! in_array($tab, ['en', 'ur'], true)) {
            return;
        }

        $this->localeTab = $tab;
    }

    public function downloadPagePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->page($this->moduleKey, $this->pageKey, $this->localeTab);

        return response()->streamDownload(
            fn () => print($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadModulePdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->module($this->moduleKey, $this->localeTab);

        return response()->streamDownload(
            fn () => print($payload['pdf']->output()),
            $payload['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadBookPdf(DocumentationPdfExporter $exporter): StreamedResponse
    {
        $payload = $exporter->book($this->localeTab);

        return response()->streamDownload(
            fn () => print($payload['pdf']->output()),
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

    public function guideUrl(string $moduleKey, string $pageKey): string
    {
        return GuidePage::guideUrl($moduleKey, $pageKey);
    }

    public function render(): View
    {
        return view('livewire.docs.documentation-shell', [
            'modules' => DocumentationRegistry::modules(),
            'pages' => DocumentationRegistry::pagesForModule($this->moduleKey),
            'meta' => $this->pageMeta(),
            'article' => $this->article(),
        ]);
    }
}
