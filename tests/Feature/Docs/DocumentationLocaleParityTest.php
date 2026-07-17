<?php

namespace Tests\Feature\Docs;

use App\Support\Documentation\DocumentationRegistry;
use Tests\TestCase;

class DocumentationLocaleParityTest extends TestCase
{
    /**
     * @return list<string>
     */
    protected function modulesWithBothLocales(): array
    {
        return [
            'catalog',
            'contacts',
            'finance',
            'purchasing',
            'sales',
            'reports',
            'store',
            'staff',
            'settings',
            'approvals',
            'marketplace',
            'dashboard',
            'logistics',
            'service',
        ];
    }

    public function test_en_and_ur_share_page_keys_and_section_ids_for_documented_pages(): void
    {
        foreach ($this->modulesWithBothLocales() as $module) {
            $enPages = include lang_path("en/docs/modules/{$module}.php");
            $urPages = include lang_path("ur/docs/modules/{$module}.php");

            $this->assertIsArray($enPages, "EN missing for {$module}");
            $this->assertIsArray($urPages, "UR missing for {$module}");
            $this->assertSame(
                array_keys($enPages),
                array_keys($urPages),
                "Page keys differ for module {$module}",
            );

            foreach ($enPages as $pageKey => $enArticle) {
                if (! is_array($enArticle) || ! isset($enArticle['sections'])) {
                    continue;
                }

                $this->assertTrue(
                    DocumentationRegistry::hasArticle($module, $pageKey, 'en'),
                    "Registry should see EN article {$module}.{$pageKey}",
                );

                $urArticle = $urPages[$pageKey] ?? null;
                $this->assertIsArray($urArticle);
                $this->assertArrayHasKey('sections', $urArticle);

                $enIds = array_values(array_filter(array_column($enArticle['sections'], 'id')));
                $urIds = array_values(array_filter(array_column($urArticle['sections'], 'id')));

                if ($enIds !== []) {
                    $this->assertSame(
                        $enIds,
                        $urIds,
                        "Section ids differ for {$module}.{$pageKey}",
                    );
                }
            }
        }
    }
}
