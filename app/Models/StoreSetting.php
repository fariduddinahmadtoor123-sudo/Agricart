<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'footer_registration_title_en',
        'footer_registration_title_ur',
        'footer_map_embed',
        'footer_copyright_en',
        'footer_copyright_ur',
        'footer_show_social',
        'footer_show_login_link',
        'footer_show_register_link',
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
            'category_grid_enabled' => 'boolean',
            'footer_enabled' => 'boolean',
            'footer_show_logo' => 'boolean',
            'footer_show_social' => 'boolean',
            'footer_show_login_link' => 'boolean',
            'footer_show_register_link' => 'boolean',
            'ticker_speed' => 'integer',
            'category_grid_columns' => 'integer',
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
            'whatsapp' => $this->social_whatsapp,
            'youtube' => $this->social_youtube,
            'twitter' => $this->social_twitter,
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

    public function whatsappChatUrl(): ?string
    {
        if (! filled($this->whatsapp_number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->whatsapp_number);

        if ($digits === '') {
            return null;
        }

        $message = $this->localized('whatsapp_message');

        if (! filled($message)) {
            return 'https://wa.me/' . $digits;
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
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

    public function footerLogoPath(): ?string
    {
        if (filled($this->footer_logo_path)) {
            return $this->footer_logo_path;
        }

        return $this->logo_path;
    }

    public function resolvedRegisterUrl(): string
    {
        $url = $this->register_url;

        if (! filled($url)) {
            return route('auth.register');
        }

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : url($url);
    }
}
