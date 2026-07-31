<?php

namespace App\Livewire\Concerns;

use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ImportsPortableArchive
{
    /**
     * Persist the Livewire temp upload to a stable path and verify AGS1 header.
     *
     * @return array{path: string, delete: bool}
     */
    protected function materializePortableArchiveUpload(mixed $upload, string $prefix): array
    {
        if (! $upload instanceof TemporaryUploadedFile) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_missing')],
            ]);
        }

        $realPath = $upload->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_missing')],
            ]);
        }

        $size = filesize($realPath);
        $minLen = strlen(PortableArchiveCodec::MAGIC) + 12 + 16 + 1;

        if ($size === false || $size < $minLen) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_truncated')],
            ]);
        }

        $handle = fopen($realPath, 'rb');
        $magic = $handle === false ? false : fread($handle, strlen(PortableArchiveCodec::MAGIC));
        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($magic !== PortableArchiveCodec::MAGIC && $magic !== PortableArchiveCodec::MAGIC_V2) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        $dest = storage_path('app/tmp/'.$prefix.'-'.Str::lower(Str::random(10)).'.'.PortableArchiveCodec::EXTENSION);
        File::ensureDirectoryExists(dirname($dest));

        if (! @copy($realPath, $dest)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_missing')],
            ]);
        }

        return [
            'path' => $dest,
            'delete' => true,
        ];
    }
}
