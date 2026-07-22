<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StaffMessageThread extends Model
{
    public const CONTEXT_ONLINE_SALE_ORDER = 'online_sale_order';

    protected $fillable = [
        'subject',
        'context_type',
        'context_id',
        'created_by_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(StaffMessageThreadParticipant::class, 'thread_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StaffMessage::class, 'thread_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(StaffMessage::class, 'thread_id')->latestOfMany();
    }

    public function onlineSaleOrder(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class, 'context_id');
    }

    public function isUnreadFor(User $user): bool
    {
        $participant = $this->participants->firstWhere('user_id', $user->id);

        if ($participant === null) {
            return false;
        }

        if ($this->last_message_at === null) {
            return false;
        }

        return $participant->last_read_at === null
            || $participant->last_read_at->lt($this->last_message_at);
    }
}
