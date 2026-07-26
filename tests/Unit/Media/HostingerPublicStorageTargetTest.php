<?php

namespace Tests\Unit\Media;

use App\Services\Media\HostingerPublicStorageTarget;
use Tests\TestCase;

class HostingerPublicStorageTargetTest extends TestCase
{
    public function test_direct_checkout_resolves_to_public_storage_not_laravel_storage_root(): void
    {
        $targets = app(HostingerPublicStorageTarget::class);

        config(['hostinger.public_html' => base_path()]);

        $this->assertTrue($targets->isDirectCheckout());
        $this->assertSame(
            realpath(public_path('storage')) ?: public_path('storage'),
            realpath($targets->resolve()) ?: $targets->resolve(),
        );
        $this->assertFalse($targets->isLaravelStorageTree((string) $targets->resolve()));
    }

    public function test_split_layout_resolves_to_public_html_storage(): void
    {
        $targets = app(HostingerPublicStorageTarget::class);
        $publicHtml = storage_path('framework/testing/hostinger-split-'.str()->random(6));

        config(['hostinger.public_html' => $publicHtml]);

        $this->assertFalse($targets->isDirectCheckout());
        $this->assertSame(
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml.DIRECTORY_SEPARATOR.'storage'),
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $targets->resolve()),
        );
    }
}
