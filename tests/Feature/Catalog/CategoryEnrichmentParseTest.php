<?php

namespace Tests\Feature\Catalog;

use App\Services\Catalog\CategoryEnrichmentService;
use Tests\TestCase;

class CategoryEnrichmentParseTest extends TestCase
{
    public function test_parse_response_accepts_markdown_fenced_json(): void
    {
        $service = app(CategoryEnrichmentService::class);
        $parsed = $service->parseResponse(<<<'JSON'
```json
{"name_en":"Boom Filters","short_description_en":"Filters for boom sprayers"}
```
JSON);

        $this->assertSame('Boom Filters', $parsed['name_en']);
        $this->assertSame('Filters for boom sprayers', $parsed['short_description_en']);
    }

    public function test_parse_response_repairs_truncated_object(): void
    {
        $service = app(CategoryEnrichmentService::class);
        $parsed = $service->parseResponse(
            '{"name_en":"Boom Nipples","short_description_en":"Nipples for boom lines","seo_title_en":"Boom Nipples Pakistan"',
        );

        $this->assertNotNull($parsed);
        $this->assertSame('Boom Nipples', $parsed['name_en']);
    }

    public function test_parse_response_rejects_empty_payload(): void
    {
        $service = app(CategoryEnrichmentService::class);

        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse('not json at all'));
    }
}
