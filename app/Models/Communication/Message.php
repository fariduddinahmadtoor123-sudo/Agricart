<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_ATTACHMENT = 'attachment';

    public const TYPE_FORWARD = 'forward';

    protected $table = 'comm_messages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'conversation_id',
        'participant_id',
        'message_type',
        'body',
        'body_format',
        'parent_id',
        'edited_at',
        'deleted_at',
        'deleted_by_participant_id',
        'event_key',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'message_id');
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
