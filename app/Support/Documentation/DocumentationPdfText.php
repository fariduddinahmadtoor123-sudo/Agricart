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
     * Render documentation HTML with mPDF so Urdu joins correctly.
     *
     * Prefers Arial when present (local Windows). On Linux shared hosting falls back
     * to mPDF's bundled DejaVu Sans so PDF generation does not 500.
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
        $fontDirs[] = storage_path('fonts');

        foreach (self::extraFontDirectories() as $directory) {
            if (is_dir($directory)) {
                $fontDirs[] = $directory;
            }
        }

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $defaultFont = 'dejavusans';

        if (self::fontFileExists($fontDirs, 'arial.ttf')) {
            $fontData['arial'] = [
                'R' => 'arial.ttf',
                'B' => self::firstExistingFontFile($fontDirs, ['arialbd.ttf', 'arial.ttf']),
                'I' => self::firstExistingFontFile($fontDirs, ['ariali.ttf', 'arial.ttf']),
                'BI' => self::firstExistingFontFile($fontDirs, ['arialbi.ttf', 'arialbd.ttf', 'arial.ttf']),
                // OpenType layout is required for Urdu/Arabic letter joining.
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];
            $defaultFont = 'arial';
        } else {
            // Bundled with mPDF; available on Hostinger without system TrueType installs.
            $fontData['dejavusans'] = array_merge($fontData['dejavusans'] ?? ['R' => 'DejaVuSans.ttf'], [
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ]);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 14,
            'fontDir' => array_values(array_unique($fontDirs)),
            'fontdata' => $fontData,
            'default_font' => $defaultFont,
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

    /**
     * @return list<string>
     */
    private static function extraFontDirectories(): array
    {
        $directories = [];

        $windowsFonts = (getenv('WINDIR') ?: 'C:\\Windows').'\\Fonts';
        $directories[] = $windowsFonts;

        $directories[] = '/usr/share/fonts/truetype/dejavu';
        $directories[] = '/usr/share/fonts/truetype/liberation';
        $directories[] = '/usr/share/fonts/TTF';
        $directories[] = '/usr/local/share/fonts';

        return $directories;
    }

    /**
     * @param  list<string>  $fontDirs
     */
    private static function fontFileExists(array $fontDirs, string $filename): bool
    {
        foreach ($fontDirs as $directory) {
            if (is_file(rtrim((string) $directory, '/\\').DIRECTORY_SEPARATOR.$filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $fontDirs
     * @param  list<string>  $filenames
     */
    private static function firstExistingFontFile(array $fontDirs, array $filenames): string
    {
        foreach ($filenames as $filename) {
            if (self::fontFileExists($fontDirs, $filename)) {
                return $filename;
            }
        }

        return $filenames[0];
    }
}
