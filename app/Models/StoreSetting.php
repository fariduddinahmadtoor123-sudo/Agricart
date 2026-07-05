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
        'menu_enabled',
        'menu_show_home',
        'menu_home_label_en',
        'menu_home_label_ur',
        'category_grid_enabled',
        'category_grid_columns',
        'category_grid_title_en',
        'category_grid_title_ur',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'top_bar_enabled' => 'boolean',
            'ticker_enabled' => 'boolean',
            'header_enabled' => 'boolean',
            'show_social' => 'boolean',
            'show_auth_links' => 'boolean',
            'show_cart' => 'boolean',
            'menu_enabled' => 'boolean',
            'menu_show_home' => 'boolean',
            'category_grid_enabled' => 'boolean',
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
}
