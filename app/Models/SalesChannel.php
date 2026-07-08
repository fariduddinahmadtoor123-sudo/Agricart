<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesChannel extends Model
{
    public const SLUG_ONLINE_STORE = 'online_store';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name_en',
        'name_ur',
        'is_enabled',
        'maintenance_mode',
        'offline_message_en',
        'offline_message_ur',
        'banner_message_en',
        'banner_message_ur',
        'maintenance_message_en',
        'maintenance_message_ur',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'maintenance_mode' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function onlineStore(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => self::SLUG_ONLINE_STORE],
            [
                'name_en' => 'Online Store',
                'name_ur' => 'آن لائن اسٹور',
                'is_enabled' => true,
                'maintenance_mode' => false,
                'offline_message_en' => 'Wait for some time',
                'offline_message_ur' => 'تھوڑی دیر انتظار کریں',
                'banner_message_en' => 'Online ordering is temporarily unavailable. You can still browse our catalog.',
                'banner_message_ur' => 'آن لائن آرڈرنگ عارضی بند ہے۔ آپ مصنوعات دیکھ سکتے ہیں۔',
                'sort_order' => 1,
            ],
        );
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ur' && filled($this->name_ur)) {
            return $this->name_ur;
        }

        return $this->name_en;
    }

    public function localizedOfflineMessage(): string
    {
        $locale = app()->getLocale();
        $key = 'offline_message_' . $locale;

        if ($locale !== 'en' && filled($this->{$key})) {
            return (string) $this->{$key};
        }

        return filled($this->offline_message_en)
            ? (string) $this->offline_message_en
            : __('sales_channels.defaults.offline_message');
    }

    public function localizedBannerMessage(): ?string
    {
        $locale = app()->getLocale();
        $key = 'banner_message_' . $locale;

        if ($locale !== 'en' && filled($this->{$key})) {
            return (string) $this->{$key};
        }

        return filled($this->banner_message_en) ? (string) $this->banner_message_en : null;
    }
}
