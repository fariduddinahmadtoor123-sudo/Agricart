<?php

namespace Tests\Feature\Docs;

use App\Services\Documentation\DocumentationPdfExporter;
use Tests\TestCase;

class DocumentationPdfExporterTest extends TestCase
{
    public function test_page_pdf_generates_for_catalog_categories(): void
    {
        $payload = app(DocumentationPdfExporter::class)->page('catalog', 'categories', 'en');

        $this->assertStringEndsWith('.pdf', $payload['filename']);
        $this->assertStringContainsString('%PDF', $payload['pdf']->output());
    }

    public function test_module_pdf_generates_for_finance(): void
    {
        $payload = app(DocumentationPdfExporter::class)->module('finance', 'en');

        $this->assertStringContainsString('finance', $payload['filename']);
        $this->assertStringContainsString('%PDF', $payload['pdf']->output());
    }
}
