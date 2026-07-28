<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Media\PublicMediaMirror;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class ProductImageWatermarkService
{
    public const SOURCE_WATERMARKED = 'watermarked';

    public const SOURCE_ORIGINAL = 'original';

    public function __construct(
        private readonly OptimizedImageStorage $optimizer,
        private readonly MediaLibraryService $library,
        private readonly PublicMediaMirror $publicMirror,
    ) {}

    /**
     * @return array{public: string, original: string}
     */
    public function processUpload(
        TemporaryUploadedFile|UploadedFile $file,
        string $kind = 'main',
    ): array {
        $kind = $kind === 'gallery' ? 'gallery' : 'main';
        $privateDisk = $this->privateDisk();
        $publicDisk = $this->publicDisk();
        $privateDir = $kind === 'gallery' ? 'products/gallery/originals' : 'products/originals';
        $publicDir = $kind === 'gallery' ? 'products/gallery' : 'products';
        $preset = $kind === 'gallery' ? 'gallery' : 'catalog';

        $originalPath = $this->optimizer->store($file, $privateDir, $preset, $privateDisk);
        $absoluteOriginal = Storage::disk($privateDisk)->path($originalPath);

        if (! is_readable($absoluteOriginal)) {
            throw new RuntimeException('Private product original was not readable after upload.');
        }

        $publicBinary = $this->buildPublicBinary($absoluteOriginal);
        $publicPath = $publicDir.'/'.Str::random(40).'.webp';

        Storage::disk($publicDisk)->makeDirectory($publicDir);

        if (! Storage::disk($publicDisk)->put($publicPath, $publicBinary)) {
            Storage::disk($privateDisk)->delete($originalPath);

            throw new RuntimeException('Failed to store watermarked product image.');
        }

        $this->library->registerPath(
            $publicPath,
            $file->getClientOriginalName(),
            null,
            'products',
            $publicDisk,
            ['role' => 'watermarked', 'kind' => $kind],
        );
        $this->publicMirror->publish($publicPath, $publicDisk);

        return [
            'public' => $publicPath,
            'original' => $originalPath,
        ];
    }

    /**
     * @return array{processed: int, skipped: int, failed: int}
     */
    public function applyToAll(bool $force = false): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0];
        $settings = StoreSetting::current();

        if ($settings->product_watermark_enabled && $this->resolveWatermarkLogoAbsolutePath($settings) === null) {
            throw new RuntimeException(__('agricart.product_watermark.errors.logo_required'));
        }

        Product::query()
            ->orderBy('id')
            ->chunkById(50, function ($products) use ($force, &$stats): void {
                foreach ($products as $product) {
                    try {
                        $changed = $this->applyToProduct($product, $force);

                        if ($changed) {
                            $stats['processed']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $stats['failed']++;
                    }
                }
            });

        return $stats;
    }

    public function applyToProduct(Product $product, bool $force = false): bool
    {
        $hasMain = filled($product->image_path) || filled($product->image_original_path);
        $hasGallery = (is_array($product->gallery_images) && $product->gallery_images !== [])
            || (is_array($product->gallery_original_images) && $product->gallery_original_images !== []);

        if (! $hasMain && ! $hasGallery) {
            return false;
        }

        $settings = StoreSetting::current();
        $version = (int) ($settings->product_watermark_version ?: 1);

        if (
            ! $force
            && (int) ($product->images_watermark_version ?? 0) === $version
            && filled($product->image_path)
            && filled($product->image_original_path)
        ) {
            return false;
        }

        $mainChanged = $this->ensurePairForPath(
            publicPath: $product->image_path,
            originalPath: $product->image_original_path,
            kind: 'main',
            force: $force,
        );

        $galleryPublic = array_values(array_filter(
            is_array($product->gallery_images) ? $product->gallery_images : [],
            fn (mixed $path): bool => is_string($path) && filled($path),
        ));
        $galleryOriginal = array_values(array_filter(
            is_array($product->gallery_original_images) ? $product->gallery_original_images : [],
            fn (mixed $path): bool => is_string($path) && filled($path),
        ));

        $newGalleryPublic = [];
        $newGalleryOriginal = [];
        $galleryChanged = false;

        foreach ($galleryPublic as $index => $publicPath) {
            $pair = $this->ensurePairForPath(
                publicPath: $publicPath,
                originalPath: $galleryOriginal[$index] ?? null,
                kind: 'gallery',
                force: $force,
            );

            if ($pair !== null) {
                $newGalleryPublic[] = $pair['public'];
                $newGalleryOriginal[] = $pair['original'];

                if ($pair['public'] !== $publicPath || ($galleryOriginal[$index] ?? null) !== $pair['original']) {
                    $galleryChanged = true;
                }
            }
        }

        $updates = [
            'images_watermark_version' => $version,
        ];

        if ($mainChanged !== null) {
            $updates['image_path'] = $mainChanged['public'];
            $updates['image_original_path'] = $mainChanged['original'];
        }

        if ($galleryChanged || count($newGalleryOriginal) !== count($galleryOriginal)) {
            $updates['gallery_images'] = $newGalleryPublic;
            $updates['gallery_original_images'] = $newGalleryOriginal;
        }

        $product->forceFill($updates)->save();

        return true;
    }

    /**
     * @return array{disk: string, path: string, source: string, url: ?string}
     */
    public function resolveForPlatform(Product $product, string $platform): array
    {
        $platform = strtolower(trim($platform));
        $prefs = $this->marketplacePreferences();
        $source = $prefs[$platform] ?? self::SOURCE_WATERMARKED;

        if (! in_array($source, [self::SOURCE_ORIGINAL, self::SOURCE_WATERMARKED], true)) {
            $source = self::SOURCE_WATERMARKED;
        }

        if ($source === self::SOURCE_ORIGINAL && filled($product->image_original_path)) {
            return [
                'disk' => $this->privateDisk(),
                'path' => (string) $product->image_original_path,
                'source' => self::SOURCE_ORIGINAL,
                'url' => null,
            ];
        }

        $publicPath = (string) ($product->image_path ?? '');

        return [
            'disk' => $this->publicDisk(),
            'path' => $publicPath,
            'source' => self::SOURCE_WATERMARKED,
            'url' => filled($publicPath) ? Storage::disk($this->publicDisk())->url($publicPath) : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function marketplacePreferences(?StoreSetting $settings = null): array
    {
        $settings ??= StoreSetting::current();
        $defaults = config('media.product_watermark.default_marketplace_preferences', []);
        $stored = is_array($settings->marketplace_image_preferences)
            ? $settings->marketplace_image_preferences
            : [];

        $merged = [];

        foreach (config('media.product_watermark.marketplace_platforms', []) as $platform) {
            $value = $stored[$platform] ?? $defaults[$platform] ?? self::SOURCE_WATERMARKED;
            $merged[$platform] = in_array($value, [self::SOURCE_ORIGINAL, self::SOURCE_WATERMARKED], true)
                ? $value
                : self::SOURCE_WATERMARKED;
        }

        return $merged;
    }

    public function bumpWatermarkVersion(StoreSetting $settings): void
    {
        $settings->forceFill([
            'product_watermark_version' => max(1, (int) $settings->product_watermark_version) + 1,
        ])->save();
    }

    public function deletePair(?string $publicPath, ?string $originalPath): void
    {
        if (filled($publicPath)) {
            $this->library->deleteFileIfUnused($publicPath, $this->publicDisk());
        }

        if (filled($originalPath)) {
            $this->library->deleteFileIfUnused($originalPath, $this->privateDisk());
        }
    }

    /**
     * @return array{public: string, original: string}|null
     */
    private function ensurePairForPath(
        ?string $publicPath,
        ?string $originalPath,
        string $kind,
        bool $force,
    ): ?array {
        if (! filled($publicPath) && ! filled($originalPath)) {
            return null;
        }

        $privateDisk = $this->privateDisk();
        $publicDisk = $this->publicDisk();
        $privateDir = $kind === 'gallery' ? 'products/gallery/originals' : 'products/originals';
        $publicDir = $kind === 'gallery' ? 'products/gallery' : 'products';

        if (! filled($originalPath) && filled($publicPath) && Storage::disk($publicDisk)->exists($publicPath)) {
            $extension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION) ?: 'webp');
            $originalPath = $privateDir.'/'.Str::random(40).'.'.$extension;
            Storage::disk($privateDisk)->makeDirectory($privateDir);
            Storage::disk($privateDisk)->put(
                $originalPath,
                Storage::disk($publicDisk)->get($publicPath) ?: '',
            );
            $this->library->registerPath(
                $originalPath,
                basename($publicPath),
                null,
                'products',
                $privateDisk,
                ['role' => 'original', 'kind' => $kind],
            );
        }

        if (! filled($originalPath) || ! Storage::disk($privateDisk)->exists($originalPath)) {
            if (! filled($publicPath) || ! Storage::disk($publicDisk)->exists($publicPath)) {
                return null;
            }

            return [
                'public' => $publicPath,
                'original' => $originalPath ?? '',
            ];
        }

        $absoluteOriginal = Storage::disk($privateDisk)->path($originalPath);
        $publicBinary = $this->buildPublicBinary($absoluteOriginal);
        $newPublicPath = $publicDir.'/'.Str::random(40).'.webp';
        Storage::disk($publicDisk)->makeDirectory($publicDir);
        Storage::disk($publicDisk)->put($newPublicPath, $publicBinary);
        $this->library->registerPath(
            $newPublicPath,
            basename($originalPath),
            null,
            'products',
            $publicDisk,
            ['role' => 'watermarked', 'kind' => $kind],
        );
        $this->publicMirror->publish($newPublicPath, $publicDisk);

        if (filled($publicPath) && $publicPath !== $newPublicPath) {
            $this->library->deleteFileIfUnused($publicPath, $publicDisk);
        }

        return [
            'public' => $newPublicPath,
            'original' => $originalPath,
        ];
    }

    private function buildPublicBinary(string $absoluteOriginal): string
    {
        $settings = StoreSetting::current();

        if (! $settings->product_watermark_enabled) {
            return $this->readAsWebpBinary($absoluteOriginal);
        }

        $logoPath = $this->resolveWatermarkLogoAbsolutePath($settings);

        if ($logoPath === null) {
            return $this->readAsWebpBinary($absoluteOriginal);
        }

        try {
            return $this->compositeWatermark(
                $absoluteOriginal,
                $logoPath,
                (string) ($settings->product_watermark_position ?: config('media.product_watermark.default_position')),
                (int) ($settings->product_watermark_size_percent ?: config('media.product_watermark.default_size_percent')),
                (int) ($settings->product_watermark_opacity ?: config('media.product_watermark.default_opacity')),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->readAsWebpBinary($absoluteOriginal);
        }
    }

    private function resolveWatermarkLogoAbsolutePath(?StoreSetting $settings = null): ?string
    {
        $settings ??= StoreSetting::current();
        $candidates = array_values(array_filter([
            $settings->product_watermark_logo_path,
            $settings->footer_logo_path,
            $settings->logo_path,
        ]));

        foreach ($candidates as $path) {
            if (! is_string($path) || ! filled($path)) {
                continue;
            }

            if (Storage::disk($this->publicDisk())->exists($path)) {
                $absolute = Storage::disk($this->publicDisk())->path($path);

                if (is_readable($absolute)) {
                    return $absolute;
                }
            }
        }

        return null;
    }

    private function readAsWebpBinary(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            $contents = file_get_contents($absolutePath);

            if ($contents === false || $contents === '') {
                throw new RuntimeException('Unable to read product original.');
            }

            return $contents;
        }

        $options = $this->optimizer->presetOptions('catalog');

        return $this->encodePathToWebp(
            $absolutePath,
            (int) ($options['max_width'] ?? 1600),
            (int) ($options['max_height'] ?? 1600),
            (int) ($options['quality'] ?? 82),
        );
    }

    private function compositeWatermark(
        string $baseAbsolute,
        string $logoAbsolute,
        string $position,
        int $sizePercent,
        int $opacity,
    ): string {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP support is not available.');
        }

        $baseContents = file_get_contents($baseAbsolute);
        $logoContents = file_get_contents($logoAbsolute);

        if ($baseContents === false || $baseContents === '' || $logoContents === false || $logoContents === '') {
            throw new RuntimeException('Unable to read watermark source images.');
        }

        $base = @imagecreatefromstring($baseContents);
        $logo = @imagecreatefromstring($logoContents);

        if ($base === false || $logo === false) {
            if (is_resource($base) || $base instanceof \GdImage) {
                imagedestroy($base);
            }
            if (is_resource($logo) || $logo instanceof \GdImage) {
                imagedestroy($logo);
            }

            throw new RuntimeException('Unable to decode watermark images.');
        }

        imagealphablending($base, true);
        imagesavealpha($base, true);

        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);

        $sizePercent = max(5, min(40, $sizePercent));
        $opacity = max(10, min(100, $opacity));
        $targetLogoWidth = max(1, (int) round($baseWidth * ($sizePercent / 100)));
        $scale = $targetLogoWidth / max(1, $logoWidth);
        $targetLogoHeight = max(1, (int) round($logoHeight * $scale));

        $scaledLogo = imagecreatetruecolor($targetLogoWidth, $targetLogoHeight);
        imagealphablending($scaledLogo, false);
        imagesavealpha($scaledLogo, true);
        $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
        imagefilledrectangle($scaledLogo, 0, 0, $targetLogoWidth, $targetLogoHeight, $transparent);
        imagecopyresampled(
            $scaledLogo,
            $logo,
            0,
            0,
            0,
            0,
            $targetLogoWidth,
            $targetLogoHeight,
            $logoWidth,
            $logoHeight,
        );
        imagedestroy($logo);

        $margin = max(8, (int) round(min($baseWidth, $baseHeight) * 0.02));
        [$destX, $destY] = match ($position) {
            'bottom-left' => [$margin, $baseHeight - $targetLogoHeight - $margin],
            'top-right' => [$baseWidth - $targetLogoWidth - $margin, $margin],
            'top-left' => [$margin, $margin],
            'center' => [
                (int) round(($baseWidth - $targetLogoWidth) / 2),
                (int) round(($baseHeight - $targetLogoHeight) / 2),
            ],
            default => [
                $baseWidth - $targetLogoWidth - $margin,
                $baseHeight - $targetLogoHeight - $margin,
            ],
        };

        $this->imageCopyMergeAlpha($base, $scaledLogo, $destX, $destY, $opacity);
        imagedestroy($scaledLogo);

        ob_start();
        $ok = imagewebp($base, null, 82);
        imagedestroy($base);
        $binary = ob_get_clean();

        if (! $ok || ! is_string($binary) || $binary === '') {
            throw new RuntimeException('Unable to encode watermarked WebP.');
        }

        return $binary;
    }

    private function imageCopyMergeAlpha(\GdImage $dst, \GdImage $src, int $dstX, int $dstY, int $opacityPercent): void
    {
        $width = imagesx($src);
        $height = imagesy($src);
        $opacityPercent = max(0, min(100, $opacityPercent));

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $srcColor = imagecolorat($src, $x, $y);
                $srcA = ($srcColor >> 24) & 0x7F;
                $srcR = ($srcColor >> 16) & 0xFF;
                $srcG = ($srcColor >> 8) & 0xFF;
                $srcB = $srcColor & 0xFF;

                if ($srcA >= 127) {
                    continue;
                }

                $dx = $dstX + $x;
                $dy = $dstY + $y;

                if ($dx < 0 || $dy < 0 || $dx >= imagesx($dst) || $dy >= imagesy($dst)) {
                    continue;
                }

                $dstColor = imagecolorat($dst, $dx, $dy);
                $dstR = ($dstColor >> 16) & 0xFF;
                $dstG = ($dstColor >> 8) & 0xFF;
                $dstB = $dstColor & 0xFF;

                $srcAlpha = (127 - $srcA) / 127;
                $blend = $srcAlpha * ($opacityPercent / 100);

                if ($blend <= 0) {
                    continue;
                }

                $outR = (int) round(($srcR * $blend) + ($dstR * (1 - $blend)));
                $outG = (int) round(($srcG * $blend) + ($dstG * (1 - $blend)));
                $outB = (int) round(($srcB * $blend) + ($dstB * (1 - $blend)));
                $color = imagecolorallocatealpha($dst, $outR, $outG, $outB, 0);
                imagesetpixel($dst, $dx, $dy, $color);
            }
        }
    }

    private function encodePathToWebp(string $absolutePath, int $maxWidth, int $maxHeight, int $quality): string
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
        $scale = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
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

    private function privateDisk(): string
    {
        return (string) config('media.product_watermark.private_disk', 'local');
    }

    private function publicDisk(): string
    {
        return (string) config('media.product_watermark.public_disk', 'public');
    }
}
