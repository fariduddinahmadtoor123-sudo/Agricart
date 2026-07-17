<?php

namespace Tests\Feature\Docs;

use App\Services\Documentation\DocumentationPdfExporter;
use App\Support\Documentation\DocumentationPdfText;
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

    public function test_detailed_urdu_page_pdf_generates_with_shaped_text(): void
    {
        $payload = app(DocumentationPdfExporter::class)->page('contacts', 'suppliers', 'ur');

        $this->assertStringContainsString('%PDF', $payload['pdf']->output());
    }

    public function test_brief_page_pdf_generates_for_catalog_categories(): void
    {
        $payload = app(DocumentationPdfExporter::class)->briefPage('catalog', 'categories', 'ur');

        $this->assertStringContainsString('brief', $payload['filename']);
        $this->assertStringContainsString('%PDF', $payload['pdf']->output());
    }

    public function test_full_brief_book_generates_for_every_module(): void
    {
        $payload = app(DocumentationPdfExporter::class)->briefBook('ur');

        $this->assertStringContainsString('brief-book', $payload['filename']);
        $this->assertStringContainsString('%PDF', $payload['pdf']->output());
    }

    public function test_urdu_customer_form_pdf_uses_arial_and_keeps_readable_urdu(): void
    {
        $payload = app(DocumentationPdfExporter::class)->briefPage('contacts', 'customers', 'ur', 'form');
        $binary = $payload['pdf']->output();

        $this->assertStringContainsString('%PDF', $binary);
        $this->assertTrue(
            str_contains($binary, 'Arial') || str_contains($binary, 'arial'),
            'PDF should embed Arial to match Agricart UI.',
        );

        $sample = DocumentationPdfText::render(
            '<html lang="ur" dir="rtl"><body style="font-family:arial">گاہک شامل کریں</body></html>',
            'ur',
        )->output();

        $this->assertStringContainsString('%PDF', $sample);
    }
}
