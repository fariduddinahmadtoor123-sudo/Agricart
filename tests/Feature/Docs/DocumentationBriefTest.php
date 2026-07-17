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
        $this->assertNotEmpty($brief['improvements'] ?? []);
        $this->assertContains('form', DocumentationRegistry::briefKinds('contacts', 'customers', 'ur'));
        $this->assertContains('list', DocumentationRegistry::briefKinds('contacts', 'customers', 'ur'));
    }

    public function test_category_form_brief_exists(): void
    {
        $this->assertTrue(DocumentationRegistry::pageHasAnyBrief('catalog', 'categories'));
        $this->assertNotNull(DocumentationRegistry::brief('catalog', 'categories', 'form', 'ur'));
    }

    public function test_supplier_form_brief_has_three_sections_in_urdu_and_english(): void
    {
        $ur = DocumentationRegistry::brief('contacts', 'suppliers', 'form', 'ur');
        $en = DocumentationRegistry::brief('contacts', 'suppliers', 'form', 'en');

        $this->assertIsArray($ur);
        $this->assertIsArray($en);
        $this->assertNotEmpty($ur['article'] ?? []);
        $this->assertGreaterThan(10, count($ur['fields'] ?? []));
        $this->assertNotEmpty($ur['ui_flow'] ?? []);
        $this->assertNotEmpty($ur['functional_requirements'] ?? []);
        $this->assertNotEmpty($ur['validation'] ?? []);
        $this->assertNotEmpty($ur['database'] ?? []);
        $this->assertNotEmpty($ur['api'] ?? []);
        $this->assertNotEmpty($ur['permissions'] ?? []);
        $this->assertNotEmpty($ur['edge_cases'] ?? []);
        $this->assertArrayHasKey('business_rules', $ur);
        $this->assertCount(5, $ur['business_rules']);
        $this->assertArrayHasKey('non_functional_requirements', $ur);
        $this->assertCount(5, $ur['non_functional_requirements']);
        $this->assertGreaterThanOrEqual(15, count($ur['dependencies'] ?? []));
        $this->assertNotEmpty($ur['improvements'] ?? []);
        $this->assertStringContainsString('supplier_code', (string) ($ur['prompt'] ?? ''));
        $this->assertStringContainsString('Functional Requirements', (string) ($ur['prompt'] ?? ''));
        $this->assertStringContainsString('SUP-', (string) ($en['prompt'] ?? ''));
        $this->assertNotEmpty($en['ui_flow'] ?? []);
        $this->assertStringContainsString('باکس', __('documentation.brief.article_heading', [], 'ur'));
        $this->assertStringContainsString('Box 1', __('documentation.brief.article_heading', [], 'en'));
    }

    public function test_every_registered_page_has_a_downloadable_handoff_brief(): void
    {
        foreach (DocumentationRegistry::modules() as $module) {
            foreach (DocumentationRegistry::pagesForModule($module['key']) as $page) {
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
                        "{$module['key']}/{$page['key']}/{$kind} has no handoff prompt.",
                    );
                }
            }
        }
    }
}
