<?php

namespace Tests\Feature\Docs;

use App\Support\Documentation\DocumentationRegistry;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class DocumentationRegistryTest extends TestCase
{
    public function test_modules_exclude_docs_and_match_chrome_order(): void
    {
        $keys = array_column(DocumentationRegistry::modules(), 'key');

        $this->assertNotContains('docs', $keys);
        $this->assertSame('dashboard', $keys[0] ?? null);
        $this->assertContains('catalog', $keys);
        $this->assertContains('finance', $keys);
        $this->assertContains('settings', $keys);
    }

    public function test_catalog_categories_article_loads_in_english_and_urdu(): void
    {
        $this->assertTrue(DocumentationRegistry::hasArticle('catalog', 'categories', 'en'));
        $this->assertTrue(DocumentationRegistry::hasArticle('catalog', 'categories', 'ur'));

        $en = DocumentationRegistry::article('catalog', 'categories', 'en');
        $ur = DocumentationRegistry::article('catalog', 'categories', 'ur');

        $this->assertIsArray($en);
        $this->assertIsArray($ur);
        $this->assertNotEmpty($en['sections'] ?? []);
        $this->assertNotEmpty($ur['sections'] ?? []);
    }

    public function test_finance_expense_payments_documented(): void
    {
        $this->assertTrue(DocumentationRegistry::hasArticle('finance', 'expense-payments', 'en'));
        $this->assertTrue(DocumentationRegistry::hasArticle('finance', 'expense-payments', 'ur'));
    }

    public function test_translation_prefix_uses_slash_path(): void
    {
        $this->assertTrue(Lang::has('docs/modules/catalog.categories.sections', 'en'));
    }
}
