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
    public function stickerData(Product $product, string $locale, string $type, ?array $fields = null): array
    {
        $locale = $locale === 'ur' ? 'ur' : 'en';
        $type = $type === 'qr' ? 'qr' : 'barcode';

        $sticker = $this->productLabels->stickerData($product, $locale, $type);

        if ($fields !== null) {
            $allowed = array_fill_keys($fields, true);
            $sticker['lines'] = array_values(array_filter(
                $sticker['lines'] ?? [],
                fn (array $line): bool => isset($allowed[$line['key'] ?? '']),
            ));

            if (! isset($allowed['product_name'])) {
                $sticker['title'] = '';
            }

            if (! isset($allowed['store_name'])) {
                $sticker['store_name'] = '';
            }
        }

        $code = (string) ($sticker['label_code'] ?? $product->label_code ?? $product->code ?? '');

        return [
            ...$sticker,
            'barcode_svg' => $code !== '' ? $this->barcodeSvg($code) : '',
            'qr_svg' => $code !== '' ? $this->qrSvg($code) : '',
            'show_printed_code' => $fields === null || in_array('printed_code', $fields, true),
        ];
    }
}
