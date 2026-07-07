<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_name_en',
        'business_name_ur',
        'legal_name_en',
        'legal_name_ur',
        'tagline_en',
        'tagline_ur',
        'logo_path',
        'email',
        'website',
        'ntn',
        'strn',
        'currency_code',
        'price_use_decimals',
        'price_decimal_places',
        'phones',
        'addresses',
        'invoice_footer_en',
        'invoice_footer_ur',
        'ai_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_use_decimals' => 'boolean',
            'price_decimal_places' => 'integer',
            'phones' => 'array',
            'addresses' => 'array',
            'ai_status' => CategoryAiStatus::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            [],
            [
                'currency_code' => 'PKR',
                'price_use_decimals' => false,
                'price_decimal_places' => 2,
                'phones' => [],
                'addresses' => [],
                'ai_status' => CategoryAiStatus::None,
            ],
        );
    }

    public function localized(string $field): ?string
    {
        $locale = app()->getLocale();
        $localizedKey = $field . '_' . $locale;

        if ($locale !== 'en' && filled($this->{$localizedKey})) {
            return $this->{$localizedKey};
        }

        return $this->{$field . '_en'};
    }

    public function logoUrl(): ?string
    {
        return filled($this->logo_path)
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    public function displayLogoUrl(): ?string
    {
        return StoreSetting::current()->displayLogoUrl();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primaryPhone(): ?array
    {
        $phones = is_array($this->phones) ? $this->phones : [];

        foreach ($phones as $phone) {
            if (! is_array($phone)) {
                continue;
            }

            if (! empty($phone['is_primary']) && filled($phone['phone_number'] ?? null)) {
                return $phone;
            }
        }

        foreach ($phones as $phone) {
            if (is_array($phone) && filled($phone['phone_number'] ?? null)) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primaryAddress(): ?array
    {
        $addresses = is_array($this->addresses) ? $this->addresses : [];

        foreach ($addresses as $address) {
            if (! is_array($address)) {
                continue;
            }

            if (! empty($address['is_primary'])) {
                return $address;
            }
        }

        return $addresses[0] ?? null;
    }

    public function formattedPrimaryAddress(): ?string
    {
        $address = $this->primaryAddress();

        if ($address === null) {
            return null;
        }

        $locale = app()->getLocale();
        $line = $locale === 'ur'
            ? ($address['address_ur'] ?? $address['address_en'] ?? null)
            : ($address['address_en'] ?? $address['address_ur'] ?? null);

        $parts = array_filter([$line, $address['postal_code'] ?? null]);

        return $parts === [] ? null : implode(' — ', $parts);
    }

    public function isConfigured(): bool
    {
        if (filled($this->business_name_en) || filled($this->business_name_ur) || filled($this->logo_path)) {
            return true;
        }

        foreach (is_array($this->phones) ? $this->phones : [] as $phone) {
            if (is_array($phone) && filled($phone['phone_number'] ?? null)) {
                return true;
            }
        }

        foreach (is_array($this->addresses) ? $this->addresses : [] as $address) {
            if (! is_array($address)) {
                continue;
            }

            if (filled($address['address_en'] ?? null) || filled($address['address_ur'] ?? null)) {
                return true;
            }
        }

        return filled($this->email) || filled($this->website) || filled($this->ntn) || filled($this->strn);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function savedPhones(): array
    {
        return array_values(array_filter(
            is_array($this->phones) ? $this->phones : [],
            static fn ($phone): bool => is_array($phone) && filled($phone['phone_number'] ?? null),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function savedAddresses(): array
    {
        return array_values(array_filter(
            is_array($this->addresses) ? $this->addresses : [],
            static function ($address): bool {
                if (! is_array($address)) {
                    return false;
                }

                return filled($address['address_en'] ?? null) || filled($address['address_ur'] ?? null);
            },
        ));
    }
}
