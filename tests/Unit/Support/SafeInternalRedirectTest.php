<?php

namespace Tests\Unit\Support;

use App\Support\SafeInternalRedirect;
use Tests\TestCase;

class SafeInternalRedirectTest extends TestCase
{
    public function test_accepts_relative_paths(): void
    {
        $this->assertSame('/checkout', SafeInternalRedirect::normalize('/checkout'));
        $this->assertSame('/cart?x=1', SafeInternalRedirect::normalize('/cart?x=1'));
    }

    public function test_rejects_protocol_relative_and_external(): void
    {
        $this->assertSame('/', SafeInternalRedirect::normalize('//evil.test/phish', '/'));
        $this->assertSame('/', SafeInternalRedirect::normalize('https://evil.test/phish', '/'));
        $this->assertSame('/', SafeInternalRedirect::normalize("/%0d%0a", '/'));
    }

    public function test_accepts_same_host_absolute_urls_as_path(): void
    {
        config(['app.url' => 'https://agricart.test']);

        $this->get('/');

        $this->assertSame(
            '/checkout',
            SafeInternalRedirect::normalize('https://agricart.test/checkout', '/'),
        );
    }
}
