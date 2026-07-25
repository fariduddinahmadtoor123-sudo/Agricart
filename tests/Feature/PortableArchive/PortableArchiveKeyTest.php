<?php

namespace Tests\Feature\PortableArchive;

use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PortableArchiveKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_requires_configured_portable_archive_key(): void
    {
        config([
            'agricart.portable_archive_key' => '',
            'agricart.supplier_archive_key' => '',
        ]);

        $this->app['env'] = 'production';

        $this->expectException(ValidationException::class);

        try {
            app(PortableArchiveCodec::class)->archiveKey();
        } catch (ValidationException $exception) {
            $this->assertSame(
                __('agricart.portable.key_required'),
                collect($exception->errors())->flatten()->first(),
            );

            throw $exception;
        }
    }

    public function test_testing_environment_allows_dev_fallback_key(): void
    {
        config([
            'agricart.portable_archive_key' => '',
            'agricart.supplier_archive_key' => '',
        ]);

        $this->app['env'] = 'testing';

        $key = app(PortableArchiveCodec::class)->archiveKey();

        $this->assertSame(
            hash('sha256', 'agricart.portable.archive.v1', true),
            $key,
        );
    }
}
