<?php

namespace App\Services\Catalog;

use App\Models\Brand;
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

class BrandImageWatermarkService
{
    public function __construct(
        private readonly OptimizedImageStorage $optimizer,
        private readonly MediaLibraryService $library,
        private readonly PublicMediaMirror $publicMirror,
    ) {}

    /**
     * @return array{public: string, original: string}
     */
    public function processUpload(TemporaryUploadedFile|UploadedFile $file): array
    {
        $privateDisk = $this->privateDisk();
        $publicDisk = $this->publicDisk();
        $privateDir = 'brands/originals';
        $publicDir = 'brands';

        $originalPath = $this->optimizer->store($file, $privateDir, 'catalog', $privateDisk);
        $absoluteOriginal = Storage::disk($privateDisk)->path($originalPath);

        if (! is_readable($absoluteOriginal)) {
            throw new RuntimeException('Private brand original was not readable after upload.');
        }

        $publicBinary = $this->buildPublicBinary($absoluteOriginal);
        $publicPath = $publicDir.'/'.Str::random(40).'.webp';

        Storage::disk($publicDisk)->makeDirectory($publicDir);

        if (! Storage::disk($publicDisk)->put($publicPath, $publicBinary)) {
            Storage::disk($privateDisk)->delete($originalPath);

            throw new RuntimeException('Failed to store watermarked brand logo.');
        }

        $this->library->registerPath(
            $publicPath,
            $file->getClientOriginalName(),
            null,
            'brands',
            $publicDisk,
            ['role' => 'watermarked'],
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

        if ($settings->brand_watermark_enabled && ! $this->hasWatermarkSource($settings)) {
            throw new RuntimeException(__('agricart.brand_watermark.errors.source_required'));
        }

        Brand::query()
            ->orderBy('id')
            ->chunkById(50, function ($brands) use ($force, &$stats): void {
                foreach ($brands as $brand) {
                    try {
                        if ($this->applyToBrand($brand, $force)) {
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

    public function applyToBrand(Brand $brand, bool $force = false): bool
    {
        if (! filled($brand->image_path) && ! filled($brand->image_original_path)) {
            return false;
        }

        $settings = StoreSetting::current();
        $version = (int) ($settings->brand_watermark_version ?: 1);

        if (
            ! $force
            && (int) ($brand->image_watermark_version ?? 0) === $version
            && filled($brand->image_path)
            && filled($brand->image_original_path)
        ) {
            return false;
        }

        $pair = $this->ensurePairForPath(
            publicPath: $brand->image_path,
            originalPath: $brand->image_original_path,
            force: $force,
        );

        if ($pair === null) {
            return false;
        }

        $brand->forceFill([
            'image_path' => $pair['public'],
            'image_original_path' => $pair['original'],
            'image_watermark_version' => $version,
        ])->save();

        return true;
    }

    public function bumpWatermarkVersion(StoreSetting $settings): void
    {
        $settings->forceFill([
            'brand_watermark_version' => max(1, (int) $settings->brand_watermark_version) + 1,
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

    public function defaultText(): string
    {
        return (string) config('media.brand_watermark.default_text', 'Available at Agricart.pk');
    }

    /**
     * @return array{public: string, original: string}|null
     */
    private function ensurePairForPath(?string $publicPath, ?string $originalPath, bool $force): ?array
    {
        if (! filled($publicPath) && ! filled($originalPath)) {
            return null;
        }

        $privateDisk = $this->privateDisk();
        $publicDisk = $this->publicDisk();
        $privateDir = 'brands/originals';
        $publicDir = 'brands';

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
                'brands',
                $privateDisk,
                ['role' => 'original'],
            );
        }

        if (! filled($originalPath) || ! Storage::disk($privateDisk)->exists($originalPath)) {
            return filled($publicPath) ? [
                'public' => $publicPath,
                'original' => (string) ($originalPath ?? ''),
            ] : null;
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
            'brands',
            $publicDisk,
            ['role' => 'watermarked'],
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

        if (! $settings->brand_watermark_enabled) {
            return $this->readAsWebpBinary($absoluteOriginal);
        }

        if (! $this->hasWatermarkSource($settings)) {
            return $this->readAsWebpBinary($absoluteOriginal);
        }

        try {
            return $this->compositeWatermark(
                $absoluteOriginal,
                $settings,
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->readAsWebpBinary($absoluteOriginal);
        }
    }

    private function hasWatermarkSource(?StoreSetting $settings = null): bool
    {
        $settings ??= StoreSetting::current();

        return $this->resolveWatermarkLogoAbsolutePath($settings) !== null
            || filled(trim((string) ($settings->brand_watermark_text ?: $this->defaultText())));
    }

    private function resolveWatermarkLogoAbsolutePath(?StoreSetting $settings = null): ?string
    {
        $settings ??= StoreSetting::current();

        // Only an explicitly uploaded brand watermark badge — never auto-use store/footer logos
        // (that caused the Agricart icon to sit on top of "Available at Agricart.pk").
        $path = $settings->brand_watermark_logo_path;

        if (! is_string($path) || ! filled($path)) {
            return null;
        }

        if (Storage::disk($this->publicDisk())->exists($path)) {
            $absolute = Storage::disk($this->publicDisk())->path($path);

            if (is_readable($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    private function compositeWatermark(string $baseAbsolute, StoreSetting $settings): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP support is not available.');
        }

        $baseContents = file_get_contents($baseAbsolute);

        if ($baseContents === false || $baseContents === '') {
            throw new RuntimeException('Unable to read brand original.');
        }

        $base = @imagecreatefromstring($baseContents);

        if ($base === false) {
            throw new RuntimeException('Unable to decode brand logo.');
        }

        imagealphablending($base, true);
        imagesavealpha($base, true);

        $position = (string) ($settings->brand_watermark_position ?: config('media.brand_watermark.default_position'));
        $sizePercent = (int) ($settings->brand_watermark_size_percent ?: config('media.brand_watermark.default_size_percent'));
        $opacity = (int) ($settings->brand_watermark_opacity ?: config('media.brand_watermark.default_opacity'));
        $logoPath = $this->resolveWatermarkLogoAbsolutePath($settings);
        $text = trim((string) ($settings->brand_watermark_text ?: $this->defaultText()));

        if ($logoPath !== null && $text !== '') {
            $this->overlayStackedBadge($base, $logoPath, $text, $position, $sizePercent, $opacity);
        } elseif ($logoPath !== null) {
            $this->overlayLogo($base, $logoPath, $position, $sizePercent, $opacity);
        } elseif ($text !== '') {
            $this->overlayText($base, $text, $position, $opacity);
        }

        ob_start();
        $ok = imagewebp($base, null, 85);
        imagedestroy($base);
        $binary = ob_get_clean();

        if (! $ok || ! is_string($binary) || $binary === '') {
            throw new RuntimeException('Unable to encode watermarked brand WebP.');
        }

        return $binary;
    }

    /**
     * Place badge image above the text banner (or below for top positions) so they never overlap.
     */
    private function overlayStackedBadge(
        \GdImage $base,
        string $logoAbsolute,
        string $text,
        string $position,
        int $sizePercent,
        int $opacity,
    ): void {
        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $margin = max(8, (int) round(min($baseWidth, $baseHeight) * 0.03));
        $gap = max(4, (int) round(min($baseWidth, $baseHeight) * 0.012));
        $opacity = max(10, min(100, $opacity));

        $textMetrics = $this->measureTextBanner($baseWidth, $margin, $text);
        $logoMetrics = $this->measureBadgeLogo($baseWidth, $logoAbsolute, $sizePercent);

        if ($logoMetrics === null) {
            $this->overlayText($base, $text, $position, $opacity);

            return;
        }

        $stackWidth = max($textMetrics['width'], $logoMetrics['width']);
        $stackHeight = $logoMetrics['height'] + $gap + $textMetrics['height'];
        [$stackX, $stackY] = $this->destination($position, $baseWidth, $baseHeight, $stackWidth, $stackHeight, $margin);

        $logoAbove = ! in_array($position, ['top-left', 'top-right'], true);
        $logoX = $stackX + (int) round(($stackWidth - $logoMetrics['width']) / 2);
        $textX = $stackX + (int) round(($stackWidth - $textMetrics['width']) / 2);

        if ($logoAbove) {
            $logoY = $stackY;
            $textY = $stackY + $logoMetrics['height'] + $gap;
        } else {
            $textY = $stackY;
            $logoY = $stackY + $textMetrics['height'] + $gap;
        }

        $this->drawBadgeLogoAt($base, $logoAbsolute, $logoX, $logoY, $logoMetrics['width'], $logoMetrics['height'], $opacity);
        $this->drawTextBannerAt($base, $text, $textX, $textY, $textMetrics, $opacity);
    }

    /**
     * @return array{width: int, height: int, fontSize: int, padX: int, padY: int, textHeight: int, fontPath: ?string}|null
     */
    private function measureTextBanner(int $baseWidth, int $margin, string $text): array
    {
        $fontPath = $this->resolveFontPath();
        $fontSize = max(10, (int) round($baseWidth * 0.045));

        if ($fontPath !== null && function_exists('imagettfbbox')) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs((int) ($box[2] - $box[0]));
            $textHeight = abs((int) ($box[1] - $box[7]));
            $padX = (int) round($fontSize * 0.55);
            $padY = (int) round($fontSize * 0.45);

            return [
                'width' => min($baseWidth - ($margin * 2), $textWidth + ($padX * 2)),
                'height' => $textHeight + ($padY * 2),
                'fontSize' => $fontSize,
                'padX' => $padX,
                'padY' => $padY,
                'textHeight' => $textHeight,
                'fontPath' => $fontPath,
            ];
        }

        $font = 5;
        $padX = 8;
        $padY = 6;

        return [
            'width' => min($baseWidth - ($margin * 2), (imagefontwidth($font) * strlen($text)) + ($padX * 2)),
            'height' => imagefontheight($font) + ($padY * 2),
            'fontSize' => $font,
            'padX' => $padX,
            'padY' => $padY,
            'textHeight' => imagefontheight($font),
            'fontPath' => null,
        ];
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function measureBadgeLogo(int $baseWidth, string $logoAbsolute, int $sizePercent): ?array
    {
        $logoContents = file_get_contents($logoAbsolute);

        if ($logoContents === false || $logoContents === '') {
            return null;
        }

        $logo = @imagecreatefromstring($logoContents);

        if ($logo === false) {
            return null;
        }

        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        imagedestroy($logo);

        $sizePercent = max(5, min(40, $sizePercent));
        $targetLogoWidth = max(1, (int) round($baseWidth * ($sizePercent / 100)));
        $scale = $targetLogoWidth / max(1, $logoWidth);

        return [
            'width' => $targetLogoWidth,
            'height' => max(1, (int) round($logoHeight * $scale)),
        ];
    }

    private function drawBadgeLogoAt(
        \GdImage $base,
        string $logoAbsolute,
        int $destX,
        int $destY,
        int $targetWidth,
        int $targetHeight,
        int $opacity,
    ): void {
        $logoContents = file_get_contents($logoAbsolute);

        if ($logoContents === false || $logoContents === '') {
            return;
        }

        $logo = @imagecreatefromstring($logoContents);

        if ($logo === false) {
            return;
        }

        $scaledLogo = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($scaledLogo, false);
        imagesavealpha($scaledLogo, true);
        $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
        imagefilledrectangle($scaledLogo, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled(
            $scaledLogo,
            $logo,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($logo),
            imagesy($logo),
        );
        imagedestroy($logo);
        $this->imageCopyMergeAlpha($base, $scaledLogo, $destX, $destY, $opacity);
        imagedestroy($scaledLogo);
    }

    /**
     * @param  array{width: int, height: int, fontSize: int, padX: int, padY: int, textHeight: int, fontPath: ?string}  $metrics
     */
    private function drawTextBannerAt(\GdImage $base, string $text, int $destX, int $destY, array $metrics, int $opacity): void
    {
        $banner = imagecreatetruecolor($metrics['width'], $metrics['height']);
        imagealphablending($banner, false);
        imagesavealpha($banner, true);
        $alpha = (int) round(127 * (1 - ($opacity / 100)));
        $bg = imagecolorallocatealpha($banner, 15, 23, 42, max(20, min(100, $alpha)));
        imagefilledrectangle($banner, 0, 0, $metrics['width'], $metrics['height'], $bg);
        imagealphablending($banner, true);
        $white = imagecolorallocate($banner, 255, 255, 255);

        if ($metrics['fontPath'] !== null && function_exists('imagettftext')) {
            imagettftext(
                $banner,
                $metrics['fontSize'],
                0,
                $metrics['padX'],
                $metrics['padY'] + $metrics['textHeight'],
                $white,
                $metrics['fontPath'],
                $text,
            );
        } else {
            imagestring($banner, (int) $metrics['fontSize'], $metrics['padX'], $metrics['padY'], $text, $white);
        }

        imagecopy($base, $banner, $destX, $destY, 0, 0, $metrics['width'], $metrics['height']);
        imagedestroy($banner);
    }

    private function overlayLogo(\GdImage $base, string $logoAbsolute, string $position, int $sizePercent, int $opacity): void
    {
        $logoContents = file_get_contents($logoAbsolute);

        if ($logoContents === false || $logoContents === '') {
            return;
        }

        $logo = @imagecreatefromstring($logoContents);

        if ($logo === false) {
            return;
        }

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
        [$destX, $destY] = $this->destination($position, $baseWidth, $baseHeight, $targetLogoWidth, $targetLogoHeight, $margin);
        $this->imageCopyMergeAlpha($base, $scaledLogo, $destX, $destY, $opacity);
        imagedestroy($scaledLogo);
    }

    private function overlayText(\GdImage $base, string $text, string $position, int $opacity): void
    {
        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $opacity = max(10, min(100, $opacity));
        $margin = max(8, (int) round(min($baseWidth, $baseHeight) * 0.03));
        $fontPath = $this->resolveFontPath();
        $fontSize = max(10, (int) round(min($baseWidth, $baseHeight) * 0.045));

        if ($fontPath !== null && function_exists('imagettftext') && function_exists('imagettfbbox')) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs((int) ($box[2] - $box[0]));
            $textHeight = abs((int) ($box[1] - $box[7]));
            $padX = (int) round($fontSize * 0.55);
            $padY = (int) round($fontSize * 0.45);
            $bannerWidth = min($baseWidth - ($margin * 2), $textWidth + ($padX * 2));
            $bannerHeight = $textHeight + ($padY * 2);
            [$destX, $destY] = $this->destination($position, $baseWidth, $baseHeight, $bannerWidth, $bannerHeight, $margin);

            $banner = imagecreatetruecolor($bannerWidth, $bannerHeight);
            imagealphablending($banner, false);
            imagesavealpha($banner, true);
            $alpha = (int) round(127 * (1 - ($opacity / 100)));
            $bg = imagecolorallocatealpha($banner, 15, 23, 42, max(20, min(100, $alpha)));
            imagefilledrectangle($banner, 0, 0, $bannerWidth, $bannerHeight, $bg);
            imagealphablending($banner, true);
            $white = imagecolorallocate($banner, 255, 255, 255);
            imagettftext($banner, $fontSize, 0, $padX, $padY + $textHeight, $white, $fontPath, $text);
            imagecopy($base, $banner, $destX, $destY, 0, 0, $bannerWidth, $bannerHeight);
            imagedestroy($banner);

            return;
        }

        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $textWidth = $charWidth * strlen($text);
        $padX = 8;
        $padY = 6;
        $bannerWidth = min($baseWidth - ($margin * 2), $textWidth + ($padX * 2));
        $bannerHeight = $charHeight + ($padY * 2);
        [$destX, $destY] = $this->destination($position, $baseWidth, $baseHeight, $bannerWidth, $bannerHeight, $margin);

        $banner = imagecreatetruecolor($bannerWidth, $bannerHeight);
        imagealphablending($banner, false);
        imagesavealpha($banner, true);
        $alpha = (int) round(127 * (1 - ($opacity / 100)));
        $bg = imagecolorallocatealpha($banner, 15, 23, 42, max(20, min(100, $alpha)));
        imagefilledrectangle($banner, 0, 0, $bannerWidth, $bannerHeight, $bg);
        imagealphablending($banner, true);
        $white = imagecolorallocate($banner, 255, 255, 255);
        imagestring($banner, $font, $padX, $padY, $text, $white);
        imagecopy($base, $banner, $destX, $destY, 0, 0, $bannerWidth, $bannerHeight);
        imagedestroy($banner);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function destination(
        string $position,
        int $baseWidth,
        int $baseHeight,
        int $overlayWidth,
        int $overlayHeight,
        int $margin,
    ): array {
        return match ($position) {
            'bottom-left' => [$margin, $baseHeight - $overlayHeight - $margin],
            'top-right' => [$baseWidth - $overlayWidth - $margin, $margin],
            'top-left' => [$margin, $margin],
            'center' => [
                (int) round(($baseWidth - $overlayWidth) / 2),
                (int) round(($baseHeight - $overlayHeight) / 2),
            ],
            default => [
                $baseWidth - $overlayWidth - $margin,
                $baseHeight - $overlayHeight - $margin,
            ],
        };
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
                imagesetpixel($dst, $dx, $dy, imagecolorallocatealpha($dst, $outR, $outG, $outB, 0));
            }
        }
    }

    private function resolveFontPath(): ?string
    {
        $candidates = [
            base_path('resources/fonts/DejaVuSans.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_string($path) && is_readable($path)) {
                return $path;
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
                throw new RuntimeException('Unable to read brand original.');
            }

            return $contents;
        }

        $options = $this->optimizer->presetOptions('catalog');

        return $this->encodePathToWebp(
            $absolutePath,
            (int) ($options['max_width'] ?? 1600),
            (int) ($options['max_height'] ?? 1600),
            (int) ($options['quality'] ?? 85),
        );
    }

    private function encodePathToWebp(string $absolutePath, int $maxWidth, int $maxHeight, int $quality): string
    {
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
        return (string) config('media.brand_watermark.private_disk', 'local');
    }

    private function publicDisk(): string
    {
        return (string) config('media.brand_watermark.public_disk', 'public');
    }
}
