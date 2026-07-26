<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Store\StoreProductService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GoogleMerchantFeedController extends Controller
{
    public function __invoke(StoreProductService $products): Response
    {
        $xml = Cache::remember('storefront.google-merchant.xml', now()->addMinutes(30), function () use ($products): string {
            $siteName = e(config('app.name'));
            $home = e(route('store.home'));
            $items = '';

            Product::query()
                ->where('status', 'active')
                ->with(['brand'])
                ->orderBy('id')
                ->chunkById(100, function ($chunk) use (&$items, $products): void {
                    foreach ($chunk as $product) {
                        $items .= $this->itemXml($product, $products);
                    }
                });

            return '<?xml version="1.0" encoding="UTF-8"?>'
                ."\n"
                .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'
                ."\n"
                .'<channel>'
                ."\n"
                .'<title>'.$siteName.' Products</title>'
                ."\n"
                .'<link>'.$home.'</link>'
                ."\n"
                .'<description>'.$siteName.' Google Merchant product feed</description>'
                ."\n"
                .$items
                .'</channel>'
                ."\n"
                .'</rss>';
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function itemXml(Product $product, StoreProductService $products): string
    {
        $title = $products->localizedName($product);
        $description = strip_tags((string) (
            $products->localizedField($product, 'seo_description')
            ?: $products->localizedField($product, 'short_description')
            ?: $title
        ));
        $link = route('store.product', $product->slug);
        $image = $products->imageUrl($product);
        $id = $product->sku ?: $product->code ?: (string) $product->id;
        $price = $product->retail_price !== null && $product->retail_price !== ''
            ? number_format((float) $product->retail_price, 2, '.', '').' PKR'
            : null;
        $availability = (float) ($product->stock_qty ?? 0) > 0 ? 'in_stock' : 'out_of_stock';
        $brand = $products->localizedBrandName($product);

        if ($image === null && filled($product->image_path)) {
            $image = Storage::disk('public')->url($product->image_path);
        }

        if ($price === null || $image === null) {
            return '';
        }

        $lines = [
            '<item>',
            '<g:id>'.e($id).'</g:id>',
            '<g:title>'.e(mb_substr($title, 0, 150)).'</g:title>',
            '<g:description>'.e(mb_substr($description, 0, 5000)).'</g:description>',
            '<g:link>'.e($link).'</g:link>',
            '<g:image_link>'.e($image).'</g:image_link>',
            '<g:availability>'.$availability.'</g:availability>',
            '<g:price>'.e($price).'</g:price>',
            '<g:condition>new</g:condition>',
        ];

        if (filled($brand)) {
            $lines[] = '<g:brand>'.e($brand).'</g:brand>';
        }

        $lines[] = '</item>';

        return implode("\n", $lines)."\n";
    }
}
