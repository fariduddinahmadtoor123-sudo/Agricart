<?php

namespace App\Support\Documentation;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class DocumentationPdfText
{
    /**
     * Render documentation HTML with mPDF so Urdu joins correctly in Arial.
     *
     * DomPDF cannot apply OpenType Arabic shaping; mPDF can when useOTL is on.
     *
     * @return object{output: callable(): string}
     */
    public static function render(string $html, string $locale): object
    {
        File::ensureDirectoryExists(storage_path('fonts'));
        File::ensureDirectoryExists(storage_path('app/mpdf'));

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $windowsFonts = (getenv('WINDIR') ?: 'C:\\Windows').'\\Fonts';
        if (is_dir($windowsFonts)) {
            $fontDirs[] = $windowsFonts;
        }

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontData['arial'] = [
            'R' => 'arial.ttf',
            'B' => 'arialbd.ttf',
            'I' => 'ariali.ttf',
            'BI' => 'arialbi.ttf',
            // OpenType layout is required for Urdu/Arabic letter joining.
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 14,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'arial',
            'tempDir' => storage_path('app/mpdf'),
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
        ]);

        $mpdf->SetDirectionality($locale === 'ur' ? 'rtl' : 'ltr');
        $mpdf->WriteHTML($html);

        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return new class($binary)
        {
            public function __construct(private string $binary) {}

            public function output(): string
            {
                return $this->binary;
            }
        };
    }
}
