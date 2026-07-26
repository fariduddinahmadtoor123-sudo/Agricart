<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Supplier extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_ADMIN = 'admin';

    public const SOURCE_PUBLIC = 'public';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_code',
        'supplier_type',
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
        'credit_limit',
        'opening_balance',
        'contacts',
        'bank_accounts',
        'portal_enabled',
        'email',
        'password',
        'profile_photo_path',
        'card_front_path',
        'card_back_path',
        'ai_status',
        'application_note',
        'custom_categories',
        'terms_accepted_at',
        'source',
        'rejection_reason',
        'rejected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'contacts' => 'array',
            'bank_accounts' => 'array',
            'portal_enabled' => 'boolean',
            'password' => 'hashed',
            'ai_status' => CategoryAiStatus::class,
            'terms_accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_supplier')->withTimestamps();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function displayBusinessName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->business_name_ur)) {
            return (string) $this->business_name_ur;
        }

        return (string) ($this->business_name_en ?: $this->business_name_ur);
    }

    public function displayCity(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->city_ur)) {
            return (string) $this->city_ur;
        }

        return (string) ($this->city_en ?: $this->city_ur);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primaryContact(): ?array
    {
        foreach (is_array($this->contacts) ? $this->contacts : [] as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            if (filled($contact['mobile_number'] ?? null)) {
                return $contact;
            }
        }

        return null;
    }

    public function primaryMobile(): ?string
    {
        $contact = $this->primaryContact();

        if ($contact === null) {
            return null;
        }

        $mobile = trim((string) ($contact['mobile_number'] ?? ''));

        return $mobile !== '' ? $mobile : null;
    }

    public function primaryContactName(): string
    {
        $contact = $this->primaryContact();

        if ($contact === null) {
            return '—';
        }

        $locale = app()->getLocale();
        $ur = trim((string) ($contact['contact_name_ur'] ?? ''));
        $en = trim((string) ($contact['contact_name_en'] ?? ''));

        if ($locale === 'ur' && $ur !== '') {
            return $ur;
        }

        return $en !== '' ? $en : $ur;
    }

    public function supplierTypeLabel(): string
    {
        if (! filled($this->supplier_type)) {
            return '—';
        }

        $locale = app()->getLocale();
        $suffix = $locale === 'ur' ? 'ur' : 'en';
        $types = config('supplier_types.'.$this->supplier_type);

        if (! is_array($types)) {
            return (string) $this->supplier_type;
        }

        return (string) ($types[$suffix] ?? $types['en'] ?? $this->supplier_type);
    }

    public function profilePhotoUrl(): ?string
    {
        return filled($this->profile_photo_path)
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }
}
