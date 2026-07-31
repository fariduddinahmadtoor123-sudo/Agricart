<?php

namespace Tests\Feature\PortableArchive;

use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PortableArchiveStreamingSealTest extends TestCase
{
    public function test_ags2_chunked_seal_round_trips_large_payload(): void
    {
        config(['agricart.portable_archive_key' => 'test-portable-key-for-streaming']);

        $codec = app(PortableArchiveCodec::class);
        $workDir = storage_path('app/tmp/ags2-test-'.uniqid());
        File::ensureDirectoryExists($workDir);

        // ~1.5 MB payload forces more than one AGS2 chunk.
        $blob = str_repeat('Agricart portable archive stress '.PHP_EOL, 50_000);
        File::put($workDir.'/payload.bin', $blob);
        File::put($workDir.'/manifest.json', json_encode(['format' => 'test', 'version' => 1]));

        try {
            $sealed = $codec->sealWorkDir($workDir, 'ags2-stress-test.ags');
            $this->assertFileExists($sealed['path']);
            $this->assertStringStartsWith(PortableArchiveCodec::MAGIC_V2, (string) file_get_contents($sealed['path'], false, null, 0, 4));

            $pack = $codec->unsealToExtracted($sealed['path']);
            $restored = (string) file_get_contents($pack['extracted'].'/payload.bin');
            $this->assertSame($blob, $restored);

            File::deleteDirectory($pack['work_dir']);
            @unlink($sealed['path']);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    public function test_legacy_ags1_archives_still_unseal(): void
    {
        config(['agricart.portable_archive_key' => 'test-portable-key-for-streaming']);

        $codec = app(PortableArchiveCodec::class);
        $key = $codec->archiveKey();
        $zipPath = storage_path('app/tmp/ags1-legacy-'.uniqid().'.zip');
        $agsPath = storage_path('app/tmp/ags1-legacy-'.uniqid().'.ags');

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $zip->addFromString('hello.txt', 'legacy-ags1');
        $zip->close();

        $plain = (string) file_get_contents($zipPath);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        $this->assertNotFalse($cipher);
        file_put_contents($agsPath, PortableArchiveCodec::MAGIC.$iv.$tag.$cipher);

        try {
            $pack = $codec->unsealToExtracted($agsPath);
            $this->assertSame('legacy-ags1', file_get_contents($pack['extracted'].'/hello.txt'));
            File::deleteDirectory($pack['work_dir']);
        } finally {
            @unlink($zipPath);
            @unlink($agsPath);
        }
    }
}
