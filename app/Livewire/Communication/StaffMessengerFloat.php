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

    public string $peerName = '';

    public string $peerEmail = '';

    public string $messageText = '';

    public ?string $errorMessage = null;

    public function openMessenger(): void
    {
        $this->open = true;
        $this->errorMessage = null;
        app(DirectStaffMessengerService::class)->ensureChannel();
    }

    public function close(): void
    {
        $this->open = false;
        $this->errorMessage = null;
        $this->resetChatState();
    }

    public function toggle(): void
    {
        if ($this->open) {
            $this->close();

            return;
        }

        $this->openMessenger();
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
            $this->resetChatState();

            return;
        }

        // Open compose immediately — create the DM thread only when sending.
        $this->peerId = (int) $peer->id;
        $this->peerName = (string) $peer->name;
        $this->peerEmail = (string) $peer->email;
        $this->dispatch('staff-messenger-scroll');
    }

    /** @deprecated Use selectPeer(); kept for older Livewire calls. */
    public function openPeer(int $peerId): void
    {
        $this->selectPeer($peerId);
    }

    public function clearPeer(): void
    {
        $this->resetChatState();
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
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $peerOnline = false;
        $unreadTotal = 0;
        /** @var list<array{id: int, name: string, email: string, online: bool}> $staffDirectory */
        $staffDirectory = [];

        if ($user !== null) {
            $unreadTotal = $messenger->totalUnread($user);
            $staffDirectory = $messenger->searchableStaff($user, null, 100);

            if ($this->peerId !== null) {
                $peer = $this->resolveActivePeer($this->peerId);

                if ($peer === null) {
                    $this->resetChatState();
                    $this->errorMessage ??= __('communication.messenger.errors.peer');
                } else {
                    $this->peerName = (string) $peer->name;
                    $this->peerEmail = (string) $peer->email;
                    $peerOnline = $messenger->onlineUserIds()->contains($peer->id);

                    // Read existing thread only — never create on render.
                    $existing = $messenger->findDm($messenger->pairKey($user->id, $peer->id));

                    if ($existing !== null) {
                        try {
                            $messages = Message::query()
                                ->with('participant')
                                ->where('conversation_id', $existing->id)
                                ->whereNull('deleted_at')
                                ->orderBy('id')
                                ->limit(200)
                                ->get();
                        } catch (Throwable $exception) {
                            report($exception);
                            $messages = collect();
                        }
                    }
                }
            }
        }

        return view('livewire.communication.staff-messenger-float', [
            'authUser' => $user,
            'messages' => $messages,
            'peerOnline' => $peerOnline,
            'unreadTotal' => $unreadTotal,
            'staffDirectory' => $staffDirectory,
            'staffCount' => count($staffDirectory),
        ]);
    }

    private function resetChatState(): void
    {
        $this->peerId = null;
        $this->peerName = '';
        $this->peerEmail = '';
        $this->messageText = '';
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
