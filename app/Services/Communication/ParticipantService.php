<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Participant;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ParticipantService
{
    public function add(
        Conversation $conversation,
        ActorRef $actor,
        string $role = Participant::ROLE_MEMBER,
    ): Participant {
        $existing = Participant::query()
            ->where('conversation_id', $conversation->id)
            ->where('actor_type', $actor->type)
            ->where('actor_id', $actor->id)
            ->first();

        if ($existing !== null) {
            if ($existing->left_at !== null) {
                $existing->update([
                    'left_at' => null,
                    'role' => $role,
                    'joined_at' => now(),
                ]);
            }

            return $existing->fresh() ?? $existing;
        }

        return Participant::query()->create([
            'conversation_id' => $conversation->id,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'role' => $role,
            'joined_at' => now(),
            'notifications' => 'all',
        ]);
    }

    public function leave(Conversation $conversation, ActorRef $actor): void
    {
        $participant = Participant::query()
            ->where('conversation_id', $conversation->id)
            ->where('actor_type', $actor->type)
            ->where('actor_id', $actor->id)
            ->whereNull('left_at')
            ->first();

        if ($participant === null) {
            throw ValidationException::withMessages([
                'participant' => ['Participant not found in this conversation.'],
            ]);
        }

        $participant->update(['left_at' => Carbon::now()]);
    }

    public function setRole(Participant $participant, string $role): Participant
    {
        if (! in_array($role, [
            Participant::ROLE_OWNER,
            Participant::ROLE_MEMBER,
            Participant::ROLE_OBSERVER,
            Participant::ROLE_BOT,
        ], true)) {
            throw ValidationException::withMessages([
                'role' => ['Invalid participant role.'],
            ]);
        }

        $participant->update(['role' => $role]);

        return $participant->fresh() ?? $participant;
    }
}
