<?php

namespace App\Support;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Reliable image previews for Livewire uploads.
 *
 * Livewire temporaryUrl() fails for some formats / rare temp-file edge cases even when
 * the file can still be stored and shown from public storage after save.
 */
final class LivewireImagePreview
{
    public static function url(TemporaryUploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if (is_string($path) && $path !== '' && is_readable($path)) {
            $thumb = self::thumbnailBinary($path);

            if ($thumb !== null) {
                return 'data:image/jpeg;base64,'.base64_encode($thumb);
            }
        }

        try {
            if ($file->isPreviewable()) {
                return $file->temporaryUrl();
            }
        } catch (\Throwable) {
            // Fall through to a full-file data URI when safe.
        }

        return self::inlineDataUri($file);
    }

    private static function inlineDataUri(TemporaryUploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        $mime = $file->getMimeType() ?: 'image/jpeg';

        if (! str_starts_with((string) $mime, 'image/')) {
            return null;
        }

        $size = filesize($path);

        if ($size === false || $size > 350_000) {
            return null;
        }

        $binary = @file_get_contents($path);

        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
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
