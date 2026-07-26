<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreAboutSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'story_en',
        'story_ur',
        'hero_years_label',
        'ceo_name',
        'ceo_title_en',
        'ceo_title_ur',
        'ceo_bio_en',
        'ceo_bio_ur',
        'ceo_photo_path',
        'office_images',
        'team_members',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'office_images' => 'array',
            'team_members' => 'array',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'hero_years_label' => '27 years of trust',
            'ceo_title_en' => 'CEO & Founder',
            'ceo_title_ur' => 'سی ای او اور بانی',
            'office_images' => [],
            'team_members' => [],
        ]);
    }

    public function localized(string $field): ?string
    {
        $locale = app()->getLocale();
        $localizedKey = $field.'_'.$locale;

        if ($locale !== 'en' && filled($this->{$localizedKey} ?? null)) {
            return $this->{$localizedKey};
        }

        return $this->{$field.'_en'} ?? null;
    }

    public function ceoPhotoUrl(): ?string
    {
        return filled($this->ceo_photo_path)
            ? Storage::disk('public')->url($this->ceo_photo_path)
            : null;
    }

    /**
     * @return list<array{path: string, url: string, caption_en: ?string, caption_ur: ?string, caption: ?string, sort: int}>
     */
    public function officeGallery(): array
    {
        $items = collect($this->office_images ?? [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['path'] ?? null))
            ->map(function (array $item, int $index): array {
                $path = (string) $item['path'];

                return [
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'caption_en' => filled($item['caption_en'] ?? null) ? (string) $item['caption_en'] : null,
                    'caption_ur' => filled($item['caption_ur'] ?? null) ? (string) $item['caption_ur'] : null,
                    'caption' => $this->localizedCaption($item),
                    'sort' => (int) ($item['sort'] ?? $index),
                ];
            })
            ->sortBy('sort')
            ->values()
            ->all();

        return $items;
    }

    /**
     * @return list<array{name: string, role_en: ?string, role_ur: ?string, role: ?string, photo_path: ?string, photo_url: ?string, sort: int}>
     */
    public function teamRoster(): array
    {
        return collect($this->team_members ?? [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['name'] ?? null))
            ->map(function (array $item, int $index): array {
                $path = filled($item['photo_path'] ?? null) ? (string) $item['photo_path'] : null;

                return [
                    'name' => (string) $item['name'],
                    'role_en' => filled($item['role_en'] ?? null) ? (string) $item['role_en'] : null,
                    'role_ur' => filled($item['role_ur'] ?? null) ? (string) $item['role_ur'] : null,
                    'role' => $this->localizedRole($item),
                    'photo_path' => $path,
                    'photo_url' => $path ? Storage::disk('public')->url($path) : null,
                    'sort' => (int) ($item['sort'] ?? $index),
                ];
            })
            ->sortBy('sort')
            ->values()
            ->all();
    }

    public function hasCeoSection(): bool
    {
        return filled($this->ceo_name) || filled($this->ceo_photo_path) || filled($this->localized('ceo_bio'));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function localizedCaption(array $item): ?string
    {
        $locale = app()->getLocale();
        if ($locale !== 'en' && filled($item['caption_ur'] ?? null)) {
            return (string) $item['caption_ur'];
        }

        return filled($item['caption_en'] ?? null) ? (string) $item['caption_en'] : null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function localizedRole(array $item): ?string
    {
        $locale = app()->getLocale();
        if ($locale !== 'en' && filled($item['role_ur'] ?? null)) {
            return (string) $item['role_ur'];
        }

        return filled($item['role_en'] ?? null) ? (string) $item['role_en'] : null;
    }
}
