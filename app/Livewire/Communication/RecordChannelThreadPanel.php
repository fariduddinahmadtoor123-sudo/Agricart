<?php

namespace App\Livewire\Communication;

use App\Models\Communication\Message;
use App\Models\User;
use App\Services\Communication\RecordChannelThreadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class RecordChannelThreadPanel extends Component
{
    use WithFileUploads;

    public string $profileKey = '';

    public int $linkableId = 0;

    public string $messageBody = '';

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $messageFiles = [];

    public function mount(string $profileKey, int $linkableId): void
    {
        $this->profileKey = $profileKey;
        $this->linkableId = $linkableId;

        $user = $this->authUser();

        if ($user !== null && $this->profileKey !== '' && $this->linkableId > 0) {
            app(RecordChannelThreadService::class)->conversationFor(
                $user,
                $this->profileKey,
                $this->linkableId,
            );
        }
    }

    public function postMessage(RecordChannelThreadService $threads): void
    {
        $user = $this->authUser();

        if ($user === null) {
            abort(403);
        }

        try {
            $threads->postMessage(
                $user,
                $this->profileKey,
                $this->linkableId,
                $this->messageBody,
                $this->messageFiles,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $fieldMessages) {
                foreach ($fieldMessages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->messageBody = '';
        $this->messageFiles = [];
        $this->resetValidation();
    }

    public function render(RecordChannelThreadService $threads): View
    {
        $user = $this->authUser();
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $authorNames = [];
        $profile = null;
        $channelName = '';

        if ($user !== null && $this->profileKey !== '' && $this->linkableId > 0) {
            $profile = $threads->profile($this->profileKey);
            $conversation = $threads->conversationFor($user, $this->profileKey, $this->linkableId);
            $channelName = (string) ($conversation->channel?->name ?? $profile['channel_key']);
            $messages = $threads->messagesFor($user, $this->profileKey, $this->linkableId);

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

        return view('livewire.communication.record-channel-thread-panel', [
            'messages' => $messages,
            'authUser' => $user,
            'authorNames' => $authorNames,
            'profile' => $profile,
            'channelName' => $channelName,
        ]);
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
