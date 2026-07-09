<?php

namespace App\Models;

use App\Enums\CategoryAiStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'customer_number',
    'customer_channel',
    'first_name',
    'last_name',
    'display_name',
    'name_en',
    'name_ur',
    'email',
    'phone',
    'password',
    'address_line1',
    'address_line2',
    'address_en',
    'address_ur',
    'address_quick',
    'city',
    'city_key',
    'city_en',
    'city_ur',
    'state',
    'postal_code',
    'country',
    'opening_balance',
    'credit_limit',
    'contacts',
    'profile_photo_path',
    'accepts_marketing',
    'is_active',
    'ai_status',
])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasFactory, Notifiable;

    public const CHANNEL_ONLINE = 'online';

    public const CHANNEL_OFFLINE = 'offline';

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function displayLabel(): string
    {
        if (filled($this->display_name)) {
            return (string) $this->display_name;
        }

        $name = $this->displayName();

        return $name !== '' ? $name : (string) ($this->email ?: $this->customer_number);
    }

    public function displayName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        if (filled($this->name_en)) {
            return (string) $this->name_en;
        }

        if (filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        if (filled($this->display_name)) {
            return (string) $this->display_name;
        }

        return $this->fullName();
    }

    public function displayCity(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->city_ur)) {
            return (string) $this->city_ur;
        }

        return (string) ($this->city_en ?: $this->city_ur ?: $this->city);
    }

    public function isOnline(): bool
    {
        return (string) $this->customer_channel === self::CHANNEL_ONLINE;
    }

    public function isOffline(): bool
    {
        return (string) $this->customer_channel === self::CHANNEL_OFFLINE;
    }

    public function channelLabel(): string
    {
        return __('customers.channels.' . ($this->customer_channel ?: self::CHANNEL_OFFLINE));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primaryPhoneContact(): ?array
    {
        foreach (is_array($this->contacts) ? $this->contacts : [] as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            if (filled($contact['mobile_number'] ?? null)) {
                return $contact;
            }
        }

        return filled($this->phone)
            ? ['mobile_number' => (string) $this->phone]
            : null;
    }

    public function primaryPhone(): string
    {
        return (string) ($this->primaryPhoneContact()['mobile_number'] ?? $this->phone ?? '');
    }

    public function profilePhotoUrl(): ?string
    {
        return filled($this->profile_photo_path)
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function onlineSaleOrders(): HasMany
    {
        return $this->hasMany(OnlineSaleOrder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accepts_marketing' => 'boolean',
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'contacts' => 'array',
            'ai_status' => CategoryAiStatus::class,
        ];
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ($key === 'credit_limit' && ($value === '' || $value === null)) {
            return null;
        }

        if ($key === 'opening_balance' && ($value === '' || $value === null)) {
            $value = '0';
        }

        return parent::castAttribute($key, $value);
    }
}
