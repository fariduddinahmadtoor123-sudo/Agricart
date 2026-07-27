<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StorePage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('storefront.sitemap.xml', now()->addMinutes(30), function (): string {
            $urls = [];
            $urls[] = $this->url(route('store.home'), now(), 'daily', '1.0');
            $urls[] = $this->url(route('store.about'), now(), 'monthly', '0.7');
            $urls[] = $this->url(route('store.brands'), now(), 'weekly', '0.7');
            $urls[] = $this->url(route('store.testimonials'), now(), 'weekly', '0.7');

            Category::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (Category $category) use (&$urls): void {
                    $urls[] = $this->url(
                        route('store.category', $category->slug),
                        $category->updated_at,
                        'weekly',
                        '0.8',
                    );
                });

            Product::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (Product $product) use (&$urls): void {
                    $urls[] = $this->url(
                        route('store.product', $product->slug),
                        $product->updated_at,
                        'weekly',
                        '0.7',
                    );
                });

            Brand::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (Brand $brand) use (&$urls): void {
                    $urls[] = $this->url(
                        route('store.brand', $brand->slug),
                        $brand->updated_at,
                        'weekly',
                        '0.6',
                    );
                });

            StorePage::query()
                ->where('is_active', true)
                ->where('slug', '!=', 'about-us')
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function (StorePage $page) use (&$urls): void {
                    $urls[] = $this->url(
                        $page->publicUrl(),
                        $page->updated_at,
                        'monthly',
                        '0.5',
                    );
                });

            return '<?xml version="1.0" encoding="UTF-8"?>'
                ."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                ."\n"
                .implode("\n", $urls)
                ."\n"
                .'</urlset>';
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    private function url(string $loc, mixed $lastmod, string $changefreq, string $priority): string
    {
        $lastmodXml = '';

        if ($lastmod !== null) {
            try {
                $lastmodXml = '<lastmod>'.e(\Illuminate\Support\Carbon::parse($lastmod)->toAtomString()).'</lastmod>';
            } catch (\Throwable) {
                $lastmodXml = '';
            }
        }

        return '<url>'
            .'<loc>'.e($loc).'</loc>'
            .$lastmodXml
            .'<changefreq>'.e($changefreq).'</changefreq>'
            .'<priority>'.e($priority).'</priority>'
            .'</url>';
    }
}
