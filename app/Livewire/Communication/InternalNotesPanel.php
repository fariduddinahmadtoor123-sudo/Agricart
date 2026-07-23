<?php

namespace App\Livewire\Communication;

use App\Models\Communication\Message;
use App\Models\User;
use App\Services\Communication\InternalNotesService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class InternalNotesPanel extends Component
{
    use WithFileUploads;

    public string $linkableType = '';

    public int $linkableId = 0;

    public string $noteBody = '';

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $noteFiles = [];

    public function mount(string $linkableType, int $linkableId): void
    {
        $this->linkableType = $linkableType;
        $this->linkableId = $linkableId;

        $user = $this->authUser();

        if ($user !== null) {
            app(InternalNotesService::class)->conversationForRecord(
                $user,
                $this->linkableType,
                $this->linkableId,
            );
        }
    }

    public function postNote(InternalNotesService $notes): void
    {
        $user = $this->authUser();

        if ($user === null) {
            abort(403);
        }

        try {
            $notes->postNote(
                $user,
                $this->linkableType,
                $this->linkableId,
                $this->noteBody,
                $this->noteFiles,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->noteBody = '';
        $this->noteFiles = [];
        $this->resetValidation();
    }

    public function render(InternalNotesService $notes): View
    {
        $user = $this->authUser();
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $authorNames = [];

        if ($user !== null && $this->linkableType !== '' && $this->linkableId > 0) {
            $messages = $notes->messagesForRecord($user, $this->linkableType, $this->linkableId);
            $userIds = $messages
                ->map(fn (Message $message) => $message->participant?->actor_type === 'user'
                    ? $message->participant->actor_id
                    : null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($userIds !== []) {
                $authorNames = User::query()
                    ->whereIn('id', $userIds)
                    ->pluck('name', 'id')
                    ->all();
            }
        }

        return view('livewire.communication.internal-notes-panel', [
            'messages' => $messages,
            'authUser' => $user,
            'authorNames' => $authorNames,
        ]);
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
