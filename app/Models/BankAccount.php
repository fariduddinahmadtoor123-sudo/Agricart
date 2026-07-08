<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const IDENTIFIER_IBAN = 'iban';

    public const IDENTIFIER_ACCOUNT_NUMBER = 'account_number';

    public const IDENTIFIER_TILL_ID = 'till_id';

    /** @var list<string> */
    public const IDENTIFIER_TYPES = [
        self::IDENTIFIER_IBAN,
        self::IDENTIFIER_ACCOUNT_NUMBER,
        self::IDENTIFIER_TILL_ID,
    ];

    /** @var list<string> */
    protected $fillable = [
        'account_code',
        'name_en',
        'name_ur',
        'bank_key',
        'bank_name_en',
        'bank_name_ur',
        'account_title',
        'account_number',
        'iban',
        'account_identifier_type',
        'opening_balance',
        'currency',
        'status',
        'notes',
        'created_by',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'account_identifier_type' => self::IDENTIFIER_ACCOUNT_NUMBER,
        'currency' => 'PKR',
        'opening_balance' => 0,
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<BankAccountTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankAccountTransaction::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        return (string) ($this->name_en ?: $this->account_title);
    }

    public function displayBankName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ur' && filled($this->bank_name_ur)) {
            return (string) $this->bank_name_ur;
        }

        return (string) ($this->bank_name_en ?: $this->bank_name_ur ?: '');
    }

    public function displayAccountIdentifier(): string
    {
        if (filled($this->iban)) {
            return (string) $this->iban;
        }

        return (string) ($this->account_number ?? '');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
