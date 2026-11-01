<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalVaultFile extends Model
{
    public const DOC_TYPES = [
        'cnic',
        'id_card',
        'driving_license',
        'passport',
        'photo',
        'bill',
        'certificate',
        'other',
    ];

    /** Types that typically need renew / expiry tracking */
    public const RENEWABLE_DOC_TYPES = [
        'cnic',
        'id_card',
        'driving_license',
        'passport',
        'certificate',
    ];

    public const FILE_FILTERS = [
        'all',
        'renew_soon',
        'expired',
        'renewable',
    ];

    protected $fillable = [
        'user_id',
        'label',
        'doc_type',
        'valid_from',
        'valid_until',
        'original_name',
        'path',
        'mime',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function reminderDays(): int
    {
        return max(1, (int) config('agricart.personal_vault.reminder_days', 30));
    }

    public function docTypeLabel(): string
    {
        $type = in_array((string) $this->doc_type, self::DOC_TYPES, true)
            ? (string) $this->doc_type
            : 'other';

        return __('agricart.pages.vault.doc_type_'.$type);
    }

    /**
     * @return array<string, string> type => label
     */
    public static function docTypeOptions(): array
    {
        $out = [];
        foreach (self::DOC_TYPES as $type) {
            $out[$type] = __('agricart.pages.vault.doc_type_'.$type);
        }

        return $out;
    }

    public function isRenewableType(): bool
    {
        return in_array((string) $this->doc_type, self::RENEWABLE_DOC_TYPES, true);
    }

    public function validityLabel(): string
    {
        if ($this->valid_from === null && $this->valid_until === null) {
            return '—';
        }

        $from = $this->valid_from?->format('d-m-Y');
        $until = $this->valid_until?->format('d-m-Y');

        if ($from && $until) {
            return $from.' → '.$until;
        }

        if ($until) {
            return __('agricart.pages.vault.valid_until_short', ['date' => $until]);
        }

        return __('agricart.pages.vault.valid_from_short', ['date' => $from]);
    }

    /** expired | soon | ok | none */
    public function validityStatus(): string
    {
        if ($this->valid_until === null) {
            return 'none';
        }

        $today = now()->startOfDay();
        $until = $this->valid_until->copy()->startOfDay();

        if ($until->lt($today)) {
            return 'expired';
        }

        if ($until->lte($today->copy()->addDays(self::reminderDays()))) {
            return 'soon';
        }

        return 'ok';
    }

    public function scopeRenewableTypes(Builder $query): Builder
    {
        return $query->whereIn('doc_type', self::RENEWABLE_DOC_TYPES);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString());
    }

    public function scopeRenewSoon(Builder $query): Builder
    {
        $today = now()->toDateString();
        $until = now()->addDays(self::reminderDays())->toDateString();

        return $query
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $until);
    }

    public function scopeApplyFileFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'expired' => $query->expired()->orderBy('valid_until'),
            'renew_soon' => $query->renewSoon()->orderBy('valid_until'),
            'renewable' => $query->renewableTypes()
                ->orderByRaw('CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END')
                ->orderBy('valid_until'),
            default => $query->latest('id'),
        };
    }
}
