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
            $this->resetChatState();
        }
    }

    public function updatedStaffSearch(): void
    {
        $this->errorMessage = null;
    }

    public function selectPeer(int $peerId): void
    {
        $this->errorMessage = null;
        $this->messageText = '';
        $this->resetValidation();

        $user = $this->authUser();
        $peer = $this->resolveActivePeer($peerId);

        if ($user === null || $peer === null || $peer->id === $user->id) {
            $this->errorMessage = __('communication.messenger.errors.peer');

            return;
        }

        try {
            app(DirectStaffMessengerService::class)->conversationWith($user, $peer);
            $this->peerId = (int) $peer->id;
            $this->staffSearch = '';
            $this->dispatch('staff-messenger-scroll');
        } catch (ValidationException $exception) {
            $this->peerId = null;
            $this->errorMessage = collect($exception->errors())->flatten()->first()
                ?: __('communication.messenger.errors.forbidden');
        } catch (AuthorizationException) {
            $this->peerId = null;
            $this->errorMessage = __('communication.messenger.errors.forbidden');
        } catch (Throwable $exception) {
            report($exception);
            $this->peerId = null;
            $this->errorMessage = __('communication.messenger.errors.peer');
        }
    }

    /** @deprecated Use selectPeer(); kept for older Livewire calls. */
    public function openPeer(int $peerId): void
    {
        $this->selectPeer($peerId);
    }

    public function clearPeer(): void
    {
        $this->peerId = null;
        $this->messageText = '';
        $this->errorMessage = null;
        $this->resetValidation();
    }

    public function backToList(): void
    {
        $this->clearPeer();
    }

    public function send(DirectStaffMessengerService $messenger): void
    {
        $user = $this->authUser();
        $peer = $this->peerId !== null ? $this->resolveActivePeer($this->peerId) : null;
        $this->errorMessage = null;

        if ($user === null || $peer === null) {
            $this->errorMessage = __('communication.messenger.errors.peer');

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
        } catch (Throwable $exception) {
            report($exception);
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
        /** @var Collection<int, array{user: User, online: bool, unread: int, last_body: ?string, last_at: ?string}> $suggestions */
        $suggestions = collect();
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $peer = null;
        $peerOnline = false;
        $unreadTotal = 0;

        if ($user !== null) {
            $unreadTotal = $messenger->totalUnread($user);

            $needle = trim($this->staffSearch);
            if ($this->peerId === null && mb_strlen($needle) >= 1) {
                $suggestions = $messenger->directory($user)
                    ->filter(function (array $row) use ($needle): bool {
                        $hayName = mb_strtolower((string) $row['user']->name);
                        $hayEmail = mb_strtolower((string) $row['user']->email);
                        $q = mb_strtolower($needle);

                        return str_contains($hayName, $q) || str_contains($hayEmail, $q);
                    })
                    ->take(8)
                    ->values();
            }

            if ($this->peerId !== null) {
                $peer = $this->resolveActivePeer($this->peerId);

                if ($peer === null) {
                    $this->peerId = null;
                    $this->errorMessage ??= __('communication.messenger.errors.peer');
                } else {
                    $peerOnline = $messenger->onlineUserIds()->contains($peer->id);

                    try {
                        $messages = $messenger->messagesWith($user, $peer);
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->errorMessage ??= __('communication.messenger.errors.forbidden');
                        $messages = collect();
                    }
                }
            }
        }

        return view('livewire.communication.staff-messenger-float', [
            'authUser' => $user,
            'suggestions' => $suggestions,
            'messages' => $messages,
            'peer' => $peer,
            'peerOnline' => $peerOnline,
            'unreadTotal' => $unreadTotal,
        ]);
    }

    private function resetChatState(): void
    {
        $this->peerId = null;
        $this->messageText = '';
        $this->staffSearch = '';
        $this->resetValidation();
    }

    private function resolveActivePeer(int $peerId): ?User
    {
        $peer = User::query()
            ->whereKey($peerId)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->with('role')
            ->first();

        if ($peer === null || ! $peer->canUseAdmin()) {
            return null;
        }

        return $peer;
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
