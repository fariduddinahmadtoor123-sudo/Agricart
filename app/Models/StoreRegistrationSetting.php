<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreRegistrationSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_title_en',
        'staff_title_ur',
        'staff_body_en',
        'staff_body_ur',
        'staff_require_acceptance',
        'supplier_title_en',
        'supplier_title_ur',
        'supplier_body_en',
        'supplier_body_ur',
        'supplier_require_acceptance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'staff_require_acceptance' => 'boolean',
            'supplier_require_acceptance' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'staff_require_acceptance' => true,
            'supplier_require_acceptance' => false,
        ]);
    }

    public function localized(string $field): ?string
    {
        $locale = app()->getLocale();
        $localizedKey = $field.'_'.$locale;

        if ($locale !== 'en' && filled($this->{$localizedKey} ?? null)) {
            return $this->{$localizedKey};
        }

        $english = $this->{$field.'_en'} ?? null;

        return filled($english) ? $english : null;
    }

    public function hasAudienceNotice(string $audience): bool
    {
        return filled($this->localized("{$audience}_body"))
            || filled($this->localized("{$audience}_title"));
    }

    public function requiresAcceptance(string $audience): bool
    {
        $flag = $audience === 'supplier'
            ? $this->supplier_require_acceptance
            : $this->staff_require_acceptance;

        return (bool) $flag && filled($this->localized("{$audience}_body"));
    }
}
