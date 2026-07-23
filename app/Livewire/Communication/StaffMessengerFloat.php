<?php

namespace App\Livewire\Communication;

use App\Models\Communication\Message;
use App\Models\User;
use App\Services\Communication\DirectStaffMessengerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class StaffMessengerFloat extends Component
{
    public bool $open = false;

    public ?int $peerId = null;

    public string $messageText = '';

    public string $staffSearch = '';

    public ?string $errorMessage = null;

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->errorMessage = null;

        if (! $this->open) {
            $this->peerId = null;
            $this->messageText = '';
            $this->staffSearch = '';
        }
    }

    public function openPeer(int $peerId): void
    {
        $this->errorMessage = null;
        $this->messageText = '';
        $this->resetValidation();

        $user = $this->authUser();
        $peer = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->find($peerId);

        if ($user === null || $peer === null || $peer->id === $user->id || ! $peer->canUseAdmin()) {
            $this->errorMessage = __('communication.messenger.errors.peer');

            return;
        }

        $this->peerId = $peer->id;

        try {
            app(DirectStaffMessengerService::class)->conversationWith($user, $peer);
            $this->dispatch('staff-messenger-scroll');
        } catch (ValidationException $exception) {
            $this->peerId = null;
            $this->errorMessage = collect($exception->errors())->flatten()->first()
                ?: __('communication.messenger.errors.forbidden');
        } catch (AuthorizationException) {
            $this->peerId = null;
            $this->errorMessage = __('communication.messenger.errors.forbidden');
        } catch (Throwable) {
            $this->peerId = null;
            $this->errorMessage = __('communication.messenger.errors.forbidden');
        }
    }

    public function backToList(): void
    {
        $this->peerId = null;
        $this->messageText = '';
        $this->errorMessage = null;
        $this->resetValidation();
    }

    public function send(DirectStaffMessengerService $messenger): void
    {
        $user = $this->authUser();
        $peer = $this->peerId !== null ? User::query()->find($this->peerId) : null;
        $this->errorMessage = null;

        if ($user === null || $peer === null) {
            return;
        }

        try {
            $messenger->postMessage($user, $peer, $this->messageText);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'participant' ? 'body' : $field, $message);
                }
            }

            return;
        } catch (AuthorizationException) {
            $this->errorMessage = __('communication.messenger.errors.forbidden');

            return;
        } catch (Throwable) {
            $this->errorMessage = __('communication.messenger.errors.forbidden');

            return;
        }

        $this->messageText = '';
        $this->resetValidation();
        $this->dispatch('staff-messenger-scroll');
    }

    public function render(DirectStaffMessengerService $messenger): View
    {
        $user = $this->authUser();
        /** @var Collection<int, array{user: User, online: bool, unread: int, last_body: ?string, last_at: ?string}> $directory */
        $directory = collect();
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $peer = null;
        $unreadTotal = 0;

        if ($user !== null) {
            $unreadTotal = $messenger->totalUnread($user);
            $directory = $messenger->directory($user);

            if ($this->staffSearch !== '') {
                $needle = mb_strtolower($this->staffSearch);
                $directory = $directory->filter(function (array $row) use ($needle): bool {
                    return str_contains(mb_strtolower((string) $row['user']->name), $needle)
                        || str_contains(mb_strtolower((string) $row['user']->email), $needle);
                })->values();
            }

            if ($this->peerId !== null) {
                $peer = User::query()->find($this->peerId);

                if ($peer !== null) {
                    try {
                        $messages = $messenger->messagesWith($user, $peer);
                    } catch (Throwable) {
                        $this->errorMessage ??= __('communication.messenger.errors.forbidden');
                        $messages = collect();
                    }
                }
            }
        }

        return view('livewire.communication.staff-messenger-float', [
            'authUser' => $user,
            'directory' => $directory,
            'messages' => $messages,
            'peer' => $peer,
            'unreadTotal' => $unreadTotal,
        ]);
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
