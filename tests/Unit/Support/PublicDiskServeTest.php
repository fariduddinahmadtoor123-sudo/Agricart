<?php

namespace Tests\Unit\Support;

use App\Support\PublicDiskServe;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicDiskServeTest extends TestCase
{
    #[DataProvider('serveDecisions')]
    public function test_serve_decision(
        string $mode,
        bool $symlinkPresent,
        ?string $hostingerPublicHtml,
        bool $expected,
    ): void {
        $this->assertSame(
            $expected,
            PublicDiskServe::enabled($mode, $symlinkPresent, $hostingerPublicHtml),
        );
    }

    /**
     * @return array<string, array{0: string, 1: bool, 2: ?string, 3: bool}>
     */
    public static function serveDecisions(): array
    {
        return [
            'explicit true' => ['true', true, null, true],
            'explicit false' => ['false', false, '/var/www/public_html', false],
            'auto with symlink locally' => ['auto', true, null, false],
            'auto without symlink' => ['auto', false, null, true],
            'auto hostinger with symlink' => ['auto', true, '/home/u/domains/x/public_html', true],
            'auto hostinger without symlink' => ['auto', false, '/home/u/domains/x/public_html', true],
        ];
    }
}
