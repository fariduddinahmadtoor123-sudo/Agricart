<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'top_bar_enabled',
        'top_bar_left_en',
        'top_bar_left_ur',
        'top_bar_center_en',
        'top_bar_center_ur',
        'top_bar_right_en',
        'top_bar_right_ur',
        'top_bar_phone',
        'show_social_top_bar',
        'ticker_enabled',
        'ticker_speed',
        'header_enabled',
        'site_name_en',
        'site_name_ur',
        'logo_path',
        'search_placeholder_en',
        'search_placeholder_ur',
        'show_social',
        'social_facebook',
        'social_instagram',
        'social_whatsapp',
        'social_youtube',
        'social_twitter',
        'social_tiktok',
        'show_auth_links',
        'login_url',
        'register_url',
        'show_cart',
        'whatsapp_number',
        'whatsapp_enabled',
        'whatsapp_message_en',
        'whatsapp_message_ur',
        'menu_enabled',
        'menu_show_home',
        'menu_home_label_en',
        'menu_home_label_ur',
        'hero_slider_enabled',
        'hero_slider_interval',
        'category_grid_enabled',
        'category_grid_columns',
        'category_grid_title_en',
        'category_grid_title_ur',
        'footer_enabled',
        'footer_tagline_en',
        'footer_tagline_ur',
        'footer_logo_path',
        'footer_show_logo',
        'footer_quick_title_en',
        'footer_quick_title_ur',
        'footer_policies_title_en',
        'footer_policies_title_ur',
        'footer_legal_title_en',
        'footer_legal_title_ur',
        'footer_registration_title_en',
        'footer_registration_title_ur',
        'footer_map_embed',
        'footer_copyright_en',
        'footer_copyright_ur',
        'footer_show_social',
        'footer_show_login_link',
        'footer_show_register_link',
        'brand_review_notice_en',
        'brand_review_notice_ur',
        'google_site_verification',
        'bing_site_verification',
        'default_og_image_path',
        'ga_measurement_id',
        'gtm_container_id',
        'meta_pixel_id',
        'customer_inbox_message_subject',
        'customer_inbox_message_body',
        'product_watermark_enabled',
        'product_watermark_logo_path',
        'product_watermark_position',
        'product_watermark_size_percent',
        'product_watermark_opacity',
        'product_watermark_version',
        'marketplace_image_preferences',
        'brand_watermark_enabled',
        'brand_watermark_logo_path',
        'brand_watermark_text',
        'brand_watermark_position',
        'brand_watermark_size_percent',
        'brand_watermark_opacity',
        'brand_watermark_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'top_bar_enabled' => 'boolean',
            'show_social_top_bar' => 'boolean',
            'ticker_enabled' => 'boolean',
            'header_enabled' => 'boolean',
            'show_social' => 'boolean',
            'show_auth_links' => 'boolean',
            'show_cart' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'menu_enabled' => 'boolean',
            'menu_show_home' => 'boolean',
            'hero_slider_enabled' => 'boolean',
            'hero_slider_interval' => 'integer',
            'category_grid_enabled' => 'boolean',
            'footer_enabled' => 'boolean',
            'footer_show_logo' => 'boolean',
            'footer_show_social' => 'boolean',
            'footer_show_login_link' => 'boolean',
            'footer_show_register_link' => 'boolean',
            'ticker_speed' => 'integer',
            'category_grid_columns' => 'integer',
            'product_watermark_enabled' => 'boolean',
            'product_watermark_size_percent' => 'integer',
            'product_watermark_opacity' => 'integer',
            'product_watermark_version' => 'integer',
            'marketplace_image_preferences' => 'array',
            'brand_watermark_enabled' => 'boolean',
            'brand_watermark_size_percent' => 'integer',
            'brand_watermark_opacity' => 'integer',
            'brand_watermark_version' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function localized(string $field): ?string
    {
        $locale = app()->getLocale();
        $localizedKey = $field . '_' . $locale;

        if ($locale !== 'en' && filled($this->{$localizedKey})) {
            return $this->{$localizedKey};
        }

        $englishKey = $field . '_en';

        return $this->{$englishKey};
    }

    /**
     * @return array<string, string|null>
     */
    public function socialNetworks(): array
    {
        return [
            'facebook' => $this->social_facebook,
            'instagram' => $this->social_instagram,
            'youtube' => $this->social_youtube,
            'twitter' => $this->social_twitter,
            'tiktok' => $this->social_tiktok,
        ];
    }

    public function hasSocialLinks(): bool
    {
        foreach ($this->socialNetworks() as $url) {
            if (filled($url)) {
                return true;
            }
        }

        return false;
    }

    public function customerInboxMessageSubject(): string
    {
        return filled($this->customer_inbox_message_subject)
            ? (string) $this->customer_inbox_message_subject
            : (string) __('customers.message.default_subject');
    }

    public function customerInboxMessageBody(): string
    {
        return filled($this->customer_inbox_message_body)
            ? (string) $this->customer_inbox_message_body
            : (string) __('customers.message.default_body', ['name' => ':name']);
    }

    /**
     * Customer WhatsApp chat links are disabled — use the on-site messenger instead.
     */
    public function whatsappChatUrl(?string $message = null): ?string
    {
        return null;
    }

    public function footerCopyrightText(): string
    {
        $text = $this->localized('footer_copyright');

        if (! filled($text)) {
            $name = $this->localized('site_name') ?? 'Agricart';
            $text = '© {year} ' . $name . '. All rights reserved.';
        }

        return str_replace('{year}', (string) date('Y'), $text);
    }

    public function brandReviewNoticeText(): string
    {
        $text = $this->localized('brand_review_notice');

        if (filled($text)) {
            return (string) $text;
        }

        return app()->getLocale() === 'ur'
            ? \App\Support\Catalog\BrandAgricartReviewNotice::defaultUrdu()
            : \App\Support\Catalog\BrandAgricartReviewNotice::defaultEnglish();
    }

    public function footerLogoPath(): ?string
    {
        if (filled($this->footer_logo_path)) {
            return $this->footer_logo_path;
        }

        return $this->logo_path;
    }

    public function displayLogoUrl(): ?string
    {
        $path = $this->footerLogoPath();

        return filled($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public function defaultOgImageUrl(): ?string
    {
        return filled($this->default_og_image_path)
            ? Storage::disk('public')->url($this->default_og_image_path)
            : null;
    }

    public function resolvedRegisterUrl(): string
    {
        return $this->resolvedCustomerRegisterUrl();
    }

    public function resolvedCustomerLoginUrl(): string
    {
        $url = $this->login_url;

        if (! filled($url) || $url === '/admin/login') {
            return route('store.customer.login');
        }

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : url($url);
    }

    public function resolvedCustomerRegisterUrl(): string
    {
        return route('store.customer.login', ['tab' => 'register']);
    }
}
