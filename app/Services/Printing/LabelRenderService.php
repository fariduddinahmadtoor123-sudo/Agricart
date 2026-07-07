<?php

namespace App\Services\Printing;

use App\Models\Product;
use App\Services\Catalog\ProductLabelService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGeneratorSVG;

class LabelRenderService
{
    public function __construct(
        private readonly ProductLabelService $productLabels,
    ) {}

    public function barcodeSvg(string $value, int $widthFactor = 2, int $height = 36): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode($value, $generator::TYPE_CODE_128, $widthFactor, $height);
    }

    public function qrSvg(string $payload, int $scale = 3): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'scale' => $scale,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        return (new QRCode($options))->render($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function stickerData(Product $product, string $locale, string $type): array
    {
        $locale = $locale === 'ur' ? 'ur' : 'en';
        $type = $type === 'qr' ? 'qr' : 'barcode';

        $stored = $type === 'qr'
            ? ($product->label_qr[$locale] ?? null)
            : ($product->label_barcode[$locale] ?? null);

        if (! is_array($stored)) {
            $this->productLabels->generateForProduct($product->fresh());
            $product->refresh();

            $stored = $type === 'qr'
                ? ($product->label_qr[$locale] ?? [])
                : ($product->label_barcode[$locale] ?? []);
        }

        $code = (string) ($stored['label_code'] ?? $product->label_code ?? $product->code ?? '');

        return [
            ...$stored,
            'barcode_svg' => $code !== '' ? $this->barcodeSvg($code) : '',
            'qr_svg' => $code !== '' ? $this->qrSvg($code) : '',
        ];
    }
}
