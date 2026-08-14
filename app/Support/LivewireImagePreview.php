<?php

namespace App\Support;

use App\Services\Media\PublicMediaMirror;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Reliable image previews for Livewire uploads.
 *
 * Catalog images ($onPublicDisk): write a small file on the public disk (same
 * /storage path the storefront uses). Livewire temporaryUrl() and data URIs
 * often fail in the Filament panel on Hostinger.
 *
 * Identity documents: keep an inline data URI so CNIC scans are not copied
 * into public storage.
 */
final class LivewireImagePreview
{
    public static function url(TemporaryUploadedFile $file, bool $onPublicDisk = false): ?string
    {
        $absolute = $file->getRealPath();
        $jpeg = is_string($absolute) && $absolute !== '' && is_readable($absolute)
            ? self::thumbnailBinary($absolute)
            : null;

        if ($onPublicDisk) {
            if ($jpeg !== null) {
                return self::publish('livewire-previews/'.Str::uuid()->toString().'.jpg', $jpeg);
            }

            $bytes = self::readableBytes($file, 2_000_000);

            if ($bytes === null) {
                return null;
            }

            $ext = strtolower((string) ($file->guessExtension() ?: 'jpg'));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
                $ext = 'jpg';
            }

            return self::publish('livewire-previews/'.Str::uuid()->toString().'.'.$ext, $bytes);
        }

        if ($jpeg !== null) {
            return 'data:image/jpeg;base64,'.base64_encode($jpeg);
        }

        return self::inlineDataUri($file);
    }

    private static function publish(string $relative, string $contents): ?string
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('livewire-previews');

        if (! $disk->put($relative, $contents)) {
            return null;
        }

        try {
            app(PublicMediaMirror::class)->publish($relative, 'public');
        } catch (Throwable) {
            // Local/fake disks have no Apache mirror.
        }

        return PublicStorage::webUrl($relative);
    }

    private static function inlineDataUri(TemporaryUploadedFile $file): ?string
    {
        $bytes = self::readableBytes($file, 350_000);

        if ($bytes === null) {
            return null;
        }

        $mime = $file->getMimeType() ?: 'image/jpeg';

        if (! str_starts_with((string) $mime, 'image/')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private static function readableBytes(TemporaryUploadedFile $file, int $maxBytes): ?string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        $size = filesize($path);

        if ($size === false || $size > $maxBytes) {
            return null;
        }

        $binary = @file_get_contents($path);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    private static function thumbnailBinary(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $raw = @file_get_contents($path);

        if ($raw === false || $raw === '') {
            return null;
        }

        $source = @imagecreatefromstring($raw);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        $max = 240;
        $scale = min($max / $width, $max / $height, 1.0);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($thumb === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($thumb, null, 82);
        $out = ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumb);

        return is_string($out) && $out !== '' ? $out : null;
    }
}
