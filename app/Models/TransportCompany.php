<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportCompany extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'transport_code',
        'transport_type',
        'status',
        'business_name_en',
        'business_name_ur',
        'country_code',
        'country_ur',
        'city_key',
        'city_en',
        'city_ur',
        'address_en',
        'address_ur',
        'destination_business_name_en',
        'destination_business_name_ur',
        'destination_country_code',
        'destination_country_ur',
        'destination_city_key',
        'destination_city_en',
        'destination_city_ur',
        'destination_address_en',
        'destination_address_ur',
        'routes',
        'branches',
        'destination_branches',
        'contacts',
        'bank_accounts',
        'credit_limit',
        'opening_balance',
        'profile_photo_path',
        'card_front_path',
        'card_back_path',
    ];

    protected function casts(): array
    {
        return [
            'routes' => 'array',
            'branches' => 'array',
            'destination_branches' => 'array',
            'contacts' => 'array',
            'bank_accounts' => 'array',
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function displayCity(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur') {
            return (string) ($this->city_ur ?: $this->city_en);
        }

        return (string) ($this->city_en ?: $this->city_ur);
    }

    public function branchesCount(): int
    {
        return count(is_array($this->branches) ? $this->branches : []);
    }

    public function displayBusinessName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur') {
            return (string) ($this->business_name_ur ?: $this->business_name_en);
        }

        return (string) ($this->business_name_en ?: $this->business_name_ur);
    }

    public function transportTypeLabel(): string
    {
        if (blank($this->transport_type)) {
            return '';
        }

        $labels = config('transport_types.' . $this->transport_type);

        if (! is_array($labels)) {
            return (string) $this->transport_type;
        }

        $suffix = app()->getLocale() === 'ur' ? 'ur' : 'en';

        return (string) ($labels[$suffix] ?? $labels['en'] ?? $this->transport_type);
    }

    /**
     * @return array<string, string>|null
     */
    public function primaryContact(): ?array
    {
        $contacts = is_array($this->contacts) ? $this->contacts : [];

        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            if (filled($contact['mobile_number'] ?? null) || filled($contact['contact_name_en'] ?? null) || filled($contact['contact_name_ur'] ?? null)) {
                return $contact;
            }
        }

        return null;
    }

    public function primaryContactName(): string
    {
        $contact = $this->primaryContact();

        if ($contact === null) {
            return '';
        }

        $suffix = app()->getLocale() === 'ur' ? 'contact_name_ur' : 'contact_name_en';

        return (string) ($contact[$suffix] ?? $contact['contact_name_en'] ?? $contact['contact_name_ur'] ?? '');
    }

    public function primaryMobile(): string
    {
        return (string) ($this->primaryContact()['mobile_number'] ?? '');
    }

    /**
     * Human-readable "From → To" summary of all routes.
     */
    public function routesSummary(): string
    {
        $routes = is_array($this->routes) ? $this->routes : [];
        $suffix = app()->getLocale() === 'ur' ? 'ur' : 'en';
        $parts = [];

        foreach ($routes as $route) {
            if (! is_array($route)) {
                continue;
            }

            $from = trim((string) ($route['from_' . $suffix] ?? $route['from_en'] ?? ''));
            $to = trim((string) ($route['to_' . $suffix] ?? $route['to_en'] ?? ''));

            if ($from === '' && $to === '') {
                continue;
            }

            $parts[] = trim($from . ' → ' . $to, ' →');
        }

        return implode(', ', $parts);
    }

    public function profilePhotoUrl(): ?string
    {
        return filled($this->profile_photo_path)
            ? \Illuminate\Support\Facades\Storage::disk(config('media.disk', 'public'))->url($this->profile_photo_path)
            : null;
    }
}
