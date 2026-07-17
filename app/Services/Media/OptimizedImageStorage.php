<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class OptimizedImageStorage
{
    /**
     * Store an upload with industry-standard resize + WebP when the preset allows.
     * Falls back to a normal store() if GD cannot process the file.
     */
    public function store(
        TemporaryUploadedFile|UploadedFile $file,
        string $directory,
        ?string $preset = null,
        ?string $disk = null,
    ): string {
        $disk ??= (string) config('media.disk', 'public');
        $directory = trim($directory, '/');
        $presetKey = $preset ?? $this->presetForDirectory($directory);
        $options = $this->presetOptions($presetKey);

        if (! ($options['convert_webp'] ?? false)) {
            return $file->store($directory, $disk);
        }

        $sourcePath = $file->getRealPath();

        if (! is_string($sourcePath) || $sourcePath === '' || ! is_readable($sourcePath)) {
            return $file->store($directory, $disk);
        }

        try {
            $binary = $this->optimizeToWebp(
                $sourcePath,
                (int) ($options['max_width'] ?? 1600),
                (int) ($options['max_height'] ?? 1600),
                (int) ($options['quality'] ?? 82),
            );
        } catch (\Throwable) {
            return $file->store($directory, $disk);
        }

        $relative = $directory.'/'.Str::random(40).'.webp';

        if (! Storage::disk($disk)->put($relative, $binary)) {
            throw new RuntimeException('Failed to store optimized image.');
        }

        return $relative;
    }

    /**
     * Re-encode an existing stored image path; returns the new relative path.
     */
    public function optimizeStoredPath(string $relativePath, ?string $preset = null, ?string $disk = null): ?string
    {
        $disk ??= (string) config('media.disk', 'public');
        $storage = Storage::disk($disk);

        if (! $storage->exists($relativePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            return $relativePath;
        }

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], true)) {
            return $relativePath;
        }

        $directory = trim(str_replace('\\', '/', dirname($relativePath)), '.');
        $presetKey = $preset ?? $this->presetForDirectory($directory);
        $options = $this->presetOptions($presetKey);

        if (! ($options['convert_webp'] ?? false)) {
            return $relativePath;
        }

        $absolute = $storage->path($relativePath);

        try {
            $binary = $this->optimizeToWebp(
                $absolute,
                (int) ($options['max_width'] ?? 1600),
                (int) ($options['max_height'] ?? 1600),
                (int) ($options['quality'] ?? 82),
            );
        } catch (\Throwable) {
            return $relativePath;
        }

        $newRelative = ($directory === '' ? '' : $directory.'/').Str::random(40).'.webp';

        if (! $storage->put($newRelative, $binary)) {
            return $relativePath;
        }

        if ($newRelative !== $relativePath) {
            $storage->delete($relativePath);
        }

        return $newRelative;
    }

    public function presetForDirectory(string $directory): string
    {
        $directory = trim($directory, '/');
        $map = config('media.directory_presets', []);

        if (isset($map[$directory])) {
            return (string) $map[$directory];
        }

        foreach ($map as $prefix => $preset) {
            if (str_starts_with($directory, rtrim((string) $prefix, '/').'/')) {
                return (string) $preset;
            }
        }

        return 'document';
    }

    /**
     * @return array{max_width: int|null, max_height: int|null, quality: int|null, convert_webp: bool}
     */
    public function presetOptions(string $preset): array
    {
        $presets = config('media.presets', []);
        $options = $presets[$preset] ?? $presets['document'] ?? [
            'max_width' => null,
            'max_height' => null,
            'quality' => null,
            'convert_webp' => false,
        ];

        return [
            'max_width' => $options['max_width'] ?? null,
            'max_height' => $options['max_height'] ?? null,
            'quality' => $options['quality'] ?? null,
            'convert_webp' => (bool) ($options['convert_webp'] ?? false),
        ];
    }

    private function optimizeToWebp(string $absolutePath, int $maxWidth, int $maxHeight, int $quality): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP support is not available.');
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Unable to read image.');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Unable to decode image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            throw new RuntimeException('Invalid image dimensions.');
        }

        $scale = min(1.0, $maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        if ($scale < 1.0) {
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($source);
            $source = $canvas;
        }

        ob_start();
        $ok = imagewebp($source, null, max(40, min(95, $quality)));
        imagedestroy($source);
        $binary = ob_get_clean();

        if (! $ok || ! is_string($binary) || $binary === '') {
            throw new RuntimeException('Unable to encode WebP.');
        }

        return $binary;
    }
}
