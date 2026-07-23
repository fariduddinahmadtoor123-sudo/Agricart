<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_MEMBER = 'member';

    public const ROLE_OBSERVER = 'observer';

    public const ROLE_BOT = 'bot';

    protected $table = 'comm_participants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'actor_type',
        'actor_id',
        'role',
        'joined_at',
        'left_at',
        'last_read_at',
        'last_read_message_id',
        'notifications',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'last_read_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'participant_id');
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    public function canPost(): bool
    {
        return $this->isActive()
            && in_array($this->role, [self::ROLE_OWNER, self::ROLE_MEMBER, self::ROLE_BOT], true);
    }

    public function canManage(): bool
    {
        return $this->isActive() && $this->role === self::ROLE_OWNER;
    }
}
