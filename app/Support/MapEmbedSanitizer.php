<?php

namespace App\Support;

/**
 * Allows only a single HTTPS map &lt;iframe&gt; for storefront footer embeds.
 */
class MapEmbedSanitizer
{
    /** @var list<string> */
    private const ALLOWED_HOSTS = [
        'www.google.com',
        'maps.google.com',
        'maps.google.com.pk',
        'www.openstreetmap.org',
        'openstreetmap.org',
    ];

    public static function sanitize(?string $html): ?string
    {
        if (! is_string($html)) {
            return null;
        }

        $html = trim($html);

        if ($html === '' || preg_match('/<script\b/i', $html) === 1) {
            return null;
        }

        if (preg_match('/<iframe\b[^>]*>\s*<\/iframe>/is', $html, $match) !== 1
            && preg_match('/<iframe\b[^>]*\/>/is', $html, $match) !== 1) {
            return null;
        }

        // Reject additional HTML tags around / beside the iframe.
        $withoutIframe = trim(preg_replace('/<iframe\b[^>]*>\s*<\/iframe>|<iframe\b[^>]*\/>/is', '', $html) ?? '');

        if ($withoutIframe !== '') {
            return null;
        }

        $tag = $match[0];

        if (preg_match('/\son\w+\s*=/i', $tag) === 1) {
            return null;
        }

        if (preg_match('/\bsrc\s*=\s*([\'"])(.*?)\1/is', $tag, $srcMatch) !== 1) {
            return null;
        }

        $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! self::isAllowedMapSrc($src)) {
            return null;
        }

        $width = self::attribute($tag, 'width') ?? '600';
        $height = self::attribute($tag, 'height') ?? '450';
        $style = self::attribute($tag, 'style');
        $title = self::attribute($tag, 'title') ?? 'Map';

        $parts = [
            'src="'.e($src).'"',
            'width="'.e($width).'"',
            'height="'.e($height).'"',
            'title="'.e($title).'"',
            'loading="lazy"',
            'referrerpolicy="no-referrer-when-downgrade"',
            'allowfullscreen',
        ];

        if (is_string($style) && self::isSafeStyle($style)) {
            $parts[] = 'style="'.e($style).'"';
        }

        return '<iframe '.implode(' ', $parts).'></iframe>';
    }

    private static function attribute(string $tag, string $name): ?string
    {
        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*([\'"])(.*?)\1/is', $tag, $match) !== 1) {
            return null;
        }

        $value = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $value !== '' ? $value : null;
    }

    private static function isAllowedMapSrc(string $src): bool
    {
        if ($src === '' || preg_match('/^\s*javascript:/i', $src) === 1) {
            return false;
        }

        $parts = parse_url($src);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($scheme !== 'https' || $host === '' || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            return false;
        }

        if (str_contains($host, 'google.')) {
            return str_contains($path, '/maps/embed');
        }

        return str_contains($path, '/export/');
    }

    private static function isSafeStyle(string $style): bool
    {
        if (preg_match('/(javascript:|expression\s*\(|url\s*\(|@import)/i', $style) === 1) {
            return false;
        }

        return preg_match('/^[a-z0-9\s:#%.,;%-]+$/i', $style) === 1;
    }
}
