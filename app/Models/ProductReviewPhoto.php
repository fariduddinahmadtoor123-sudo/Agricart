<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductReviewPhoto extends Model
{
    public const KIND_GALLERY = 'gallery';

    public const KIND_PROOF = 'proof';

    /** @var list<string> */
    public const KINDS = [
        self::KIND_GALLERY,
        self::KIND_PROOF,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_review_id',
        'path',
        'kind',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function isProof(): bool
    {
        return $this->kind === self::KIND_PROOF;
    }

    public function isPdf(): bool
    {
        return str_ends_with(mb_strtolower((string) $this->path), '.pdf');
    }
}
