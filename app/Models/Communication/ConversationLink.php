<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationLink extends Model
{
    public const RELATION_ABOUT = 'about';

    public const RELATION_REF = 'ref';

    public const RELATION_CREATED_FROM = 'created_from';

    protected $table = 'comm_conversation_links';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'linkable_type',
        'linkable_id',
        'relation',
        'created_by_participant_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function createdByParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'created_by_participant_id');
    }
}
