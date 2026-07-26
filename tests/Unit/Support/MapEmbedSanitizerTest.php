<?php

namespace Tests\Unit\Support;

use App\Support\MapEmbedSanitizer;
use Tests\TestCase;

class MapEmbedSanitizerTest extends TestCase
{
    public function test_allows_google_maps_iframe(): void
    {
        $html = '<iframe src="https://www.google.com/maps/embed?pb=test" width="600" height="450" style="border:0;" allowfullscreen></iframe>';

        $clean = MapEmbedSanitizer::sanitize($html);

        $this->assertIsString($clean);
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=test', $clean);
        $this->assertStringNotContainsString('<script', $clean);
    }

    public function test_rejects_script_injection(): void
    {
        $html = '<iframe src="https://www.google.com/maps/embed?pb=x"></iframe><script>alert(1)</script>';

        $this->assertNull(MapEmbedSanitizer::sanitize($html));
    }

    public function test_rejects_javascript_src(): void
    {
        $html = '<iframe src="javascript:alert(1)"></iframe>';

        $this->assertNull(MapEmbedSanitizer::sanitize($html));
    }
}
