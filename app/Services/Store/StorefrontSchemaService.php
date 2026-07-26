<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\StoreSetting;

class StorefrontSchemaService
{
    /**
     * @param  list<string>  $imageUrls
     * @param  array{average: float|null, count: int}|null  $reviewSummary
     * @param  array{amount?: mixed, unit_price?: mixed, formatted?: string}|null  $buyPrice
     * @return array<string, mixed>
     */
    public function productDocument(
        Product $product,
        StoreProductService $products,
        StoreCategoryService $categories,
        array $imageUrls,
        ?array $buyPrice,
        ?array $reviewSummary,
        bool $comingSoon = false,
    ): array {
        $productName = $products->localizedName($product);
        $description = strip_tags((string) (
            $products->localizedField($product, 'seo_description')
            ?: $products->localizedField($product, 'short_description')
            ?: $productName
        ));

        $productNode = array_filter([
            '@type' => 'Product',
            'name' => $productName,
            'description' => $description,
            'sku' => $product->sku ?: $product->code,
            'image' => $imageUrls !== [] ? $imageUrls : null,
            'brand' => $product->brand
                ? ['@type' => 'Brand', 'name' => $products->localizedBrandName($product)]
                : null,
            'offers' => $this->productOffer($product, $buyPrice, $comingSoon),
            'aggregateRating' => $this->aggregateRating($reviewSummary),
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $productNode,
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_values(array_filter([
                        ['@type' => 'ListItem', 'position' => 1, 'name' => __('agricart.storefront.category.home'), 'item' => route('store.home')],
                        $product->category
                            ? [
                                '@type' => 'ListItem',
                                'position' => 2,
                                'name' => $categories->localizedName($product->category),
                                'item' => route('store.category', $product->category->slug),
                            ]
                            : null,
                        [
                            '@type' => 'ListItem',
                            'position' => $product->category ? 3 : 2,
                            'name' => $productName,
                            'item' => route('store.product', $product->slug),
                        ],
                    ])),
                ],
            ],
        ];
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>
     */
    public function categoryDocument(
        Category $category,
        StoreCategoryService $categories,
        array $faqs = [],
    ): array {
        $categoryName = $categories->localizedName($category);
        $description = strip_tags((string) (
            $categories->localizedField($category, 'seo_description')
            ?: $categories->localizedField($category, 'short_description')
            ?: $categoryName
        ));
        $image = $categories->imageUrl($category);

        $graph = [
            array_filter([
                '@type' => 'CollectionPage',
                'name' => $categoryName,
                'description' => $description,
                'url' => route('store.category', $category->slug),
                'image' => $image,
            ], static fn ($value): bool => $value !== null && $value !== ''),
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('agricart.storefront.category.home'), 'item' => route('store.home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryName, 'item' => route('store.category', $category->slug)],
                ],
            ],
        ];

        if ($faqs !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    static fn (array $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer'],
                        ],
                    ],
                    $faqs,
                ),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function homeDocument(StoreSetting $settings, ?string $logoUrl): array
    {
        $siteName = $settings->localized('site_name') ?? config('app.name');
        $profile = CompanyProfile::current();
        $phone = $profile->primaryPhone()['phone_number']
            ?? ($settings->top_bar_phone ?: null);
        $address = $this->postalAddress($profile);
        $sameAs = array_values(array_filter([
            $settings->social_facebook ?? null,
            $settings->social_instagram ?? null,
            $settings->social_youtube ?? null,
            $settings->social_twitter ?? null,
        ]));

        $business = array_filter([
            '@type' => 'LocalBusiness',
            'name' => $profile->localized('business_name') ?: $siteName,
            'url' => route('store.home'),
            'logo' => $logoUrl ?: $profile->logoUrl(),
            'image' => $logoUrl ?: $profile->logoUrl(),
            'telephone' => $phone,
            'email' => filled($profile->email) ? $profile->email : null,
            'address' => $address,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $business,
                [
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => route('store.home'),
                    'inLanguage' => app()->getLocale(),
                    'publisher' => ['@type' => 'Organization', 'name' => $siteName],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('store.search').'?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    public function toJson(array $document): string
    {
        return (string) json_encode(
            $document,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }

    /**
     * @param  array{amount?: mixed, unit_price?: mixed}|null  $buyPrice
     * @return array<string, mixed>|null
     */
    private function productOffer(Product $product, ?array $buyPrice, bool $comingSoon): ?array
    {
        if ($comingSoon || $buyPrice === null) {
            return null;
        }

        $price = $buyPrice['amount'] ?? $buyPrice['unit_price'] ?? null;

        if ($price === null || $price === '') {
            return null;
        }

        return [
            '@type' => 'Offer',
            'url' => route('store.product', $product->slug),
            'priceCurrency' => 'PKR',
            'price' => $price,
            'availability' => (float) ($product->stock_qty ?? 0) > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ];
    }

    /**
     * @param  array{average: float|null, count: int}|null  $reviewSummary
     * @return array<string, mixed>|null
     */
    private function aggregateRating(?array $reviewSummary): ?array
    {
        if ($reviewSummary === null) {
            return null;
        }

        $count = (int) ($reviewSummary['count'] ?? 0);
        $average = $reviewSummary['average'] ?? null;

        if ($count < 1 || $average === null) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $average,
            'reviewCount' => $count,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function postalAddress(CompanyProfile $profile): ?array
    {
        $address = $profile->primaryAddress();

        if ($address === null) {
            return null;
        }

        $locale = app()->getLocale();
        $street = $locale === 'ur'
            ? ($address['address_ur'] ?? $address['address_en'] ?? null)
            : ($address['address_en'] ?? $address['address_ur'] ?? null);

        if (! filled($street) && ! filled($address['postal_code'] ?? null)) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => filled($street) ? (string) $street : null,
            'postalCode' => filled($address['postal_code'] ?? null) ? (string) $address['postal_code'] : null,
            'addressCountry' => 'PK',
        ], static fn ($value): bool => $value !== null && $value !== '');
    }
}
