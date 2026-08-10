<?php

namespace Tests\Feature\Docs;

use App\Support\Documentation\DocumentationRegistry;
use Tests\TestCase;

class DocumentationBriefTest extends TestCase
{
    public function test_customer_form_brief_has_copy_prompt(): void
    {
        $this->assertTrue(DocumentationRegistry::hasBrief('contacts', 'customers', 'form', 'ur'));

        $brief = DocumentationRegistry::brief('contacts', 'customers', 'form', 'ur');

        $this->assertIsArray($brief);
        $this->assertNotEmpty($brief['prompt'] ?? null);
        $this->assertArrayHasKey('notes', $brief);
        $this->assertSame([], $brief['notes']);
        $this->assertContains('form', DocumentationRegistry::briefKinds('contacts', 'customers', 'ur'));
        $this->assertContains('list', DocumentationRegistry::briefKinds('contacts', 'customers', 'ur'));
    }

    public function test_category_form_brief_exists(): void
    {
        $this->assertTrue(DocumentationRegistry::pageHasAnyBrief('catalog', 'categories'));
        $this->assertNotNull(DocumentationRegistry::brief('catalog', 'categories', 'form', 'ur'));
    }

    public function test_supplier_form_brief_has_three_user_guide_sections(): void
    {
        $ur = DocumentationRegistry::brief('contacts', 'suppliers', 'form', 'ur');
        $en = DocumentationRegistry::brief('contacts', 'suppliers', 'form', 'en');

        $this->assertIsArray($ur);
        $this->assertIsArray($en);

        $this->assertSame([], $ur['overview'] ?? $ur['article'] ?? null);
        $this->assertNotEmpty($en['overview'] ?? []);
        $this->assertSame([], $ur['notes'] ?? null);
        $this->assertSame([], $en['notes'] ?? null);

        $this->assertStringContainsString('supplier_code', (string) ($ur['prompt'] ?? ''));
        $this->assertStringContainsString('SUP-', (string) ($en['prompt'] ?? ''));

        $this->assertStringContainsString('Overview', __('documentation.brief.overview_heading', [], 'en'));
        $this->assertStringContainsString('AI Prompt', __('documentation.brief.prompt_heading', [], 'en'));
        $this->assertStringContainsString('Notes', __('documentation.brief.notes_heading', [], 'en'));
        $this->assertStringContainsString('Overview', __('documentation.brief.overview_heading', [], 'ur'));
    }

    public function test_transport_companies_brief_exists_in_english_and_urdu(): void
    {
        $this->assertTrue(DocumentationRegistry::pageHasAnyBrief('contacts', 'transport-companies'));

        $en = DocumentationRegistry::brief('contacts', 'transport-companies', 'form', 'en');
        $ur = DocumentationRegistry::brief('contacts', 'transport-companies', 'form', 'ur');

        $this->assertIsArray($en);
        $this->assertIsArray($ur);
        $this->assertNotEmpty($en['overview'] ?? []);
        $this->assertSame([], $ur['overview'] ?? []);
        $this->assertStringContainsString('Destination', (string) ($en['prompt'] ?? ''));
        $this->assertStringContainsString('transport-companies', (string) ($ur['prompt'] ?? ''));
    }

    public function test_every_registered_page_has_a_downloadable_handoff_brief(): void
    {
        foreach (DocumentationRegistry::modules() as $module) {
            foreach (DocumentationRegistry::pagesForModule($module['key']) as $page) {
                if (! DocumentationRegistry::pageHasAnyBrief($module['key'], $page['key'])) {
                    continue;
                }

                $kinds = DocumentationRegistry::briefKinds($module['key'], $page['key'], 'ur');

                $this->assertNotEmpty(
                    $kinds,
                    "{$module['key']}/{$page['key']} has no Urdu brief.",
                );

                foreach ($kinds as $kind) {
                    $brief = DocumentationRegistry::brief(
                        $module['key'],
                        $page['key'],
                        $kind,
                        'ur',
                    );

                    $this->assertIsArray($brief);
                    $this->assertNotEmpty(
                        $brief['prompt'] ?? null,
                        "{$module['key']}/{$page['key']}/{$kind} has no AI prompt.",
                    );
                    $this->assertArrayHasKey('notes', $brief);
                }
            }
        }
    }
}
