<?php

namespace App\Livewire\Store;

use App\Models\AgricartConversation;
use App\Models\User;
use App\Services\Store\AgricartInboxService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListAgricartInbox extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'open';

    public string $topicFilter = 'all';

    #[Locked]
    public ?int $viewConversationId = null;

    public string $replyText = '';

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);

        $conversationId = request()->integer('conversation');
        if ($conversationId > 0) {
            $this->openView($conversationId, app(AgricartInboxService::class));
        }
    }

    #[On('store-messages-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('store-messages-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, [...AgricartConversation::STATUSES, 'all'], true)) {
            $this->statusFilter = $value;
            $this->resetPage();
        }

        if ($field === 'topic' && in_array($value, [...AgricartConversation::TOPICS, 'all'], true)) {
            $this->topicFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-messages-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->topicFilter = 'all';
        $this->resetPage();
    }

    public function openView(int $conversationId, AgricartInboxService $inbox): void
    {
        abort_unless($this->userCan('view'), 403);

        $conversation = AgricartConversation::query()->findOrFail($conversationId);
        $admin = auth()->user();
        $inbox->markReadByAdmin($conversation, $admin instanceof User ? $admin : null);
        $this->viewConversationId = $conversation->id;
        $this->replyText = '';
        $this->dispatch('topbar-notifications-refresh');
    }

    public function closeView(): void
    {
        $this->viewConversationId = null;
        $this->replyText = '';
    }

    public function sendReply(AgricartInboxService $inbox): void
    {
        abort_unless($this->userCan('edit'), 403);

        if ($this->viewConversationId === null) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $conversation = AgricartConversation::query()->findOrFail($this->viewConversationId);
        $inbox->replyAsAdmin($conversation, $user, $this->replyText);
        $this->replyText = '';
        $this->dispatch('topbar-notifications-refresh');
    }

    public function closeConversation(int $conversationId, AgricartInboxService $inbox): void
    {
        abort_unless($this->userCan('edit'), 403);
        $inbox->close(AgricartConversation::query()->findOrFail($conversationId));
        $this->dispatch('topbar-notifications-refresh');
    }

    public function reopenConversation(int $conversationId, AgricartInboxService $inbox): void
    {
        abort_unless($this->userCan('edit'), 403);
        $inbox->reopen(AgricartConversation::query()->findOrFail($conversationId));
        $this->dispatch('topbar-notifications-refresh');
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        $items = AgricartConversation::query()
            ->with(['customer', 'order:id,order_number', 'review.product:id,name_en'])
            ->withCount('messages')
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->topicFilter !== 'all', fn ($query) => $query->where('topic', $this->topicFilter))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(function ($inner) use ($needle): void {
                    $inner->where('subject', 'like', $needle)
                        ->orWhere('guest_name', 'like', $needle)
                        ->orWhere('guest_phone', 'like', $needle)
                        ->orWhere('guest_email', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('first_name', 'like', $needle)
                                ->orWhere('last_name', 'like', $needle)
                                ->orWhere('display_name', 'like', $needle)
                                ->orWhere('email', 'like', $needle)
                                ->orWhere('phone', 'like', $needle);
                        })
                        ->orWhereHas('messages', fn ($messages) => $messages->where('body', 'like', $needle));
                });
            })
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(20);

        $selected = null;

        if ($this->viewConversationId !== null) {
            $selected = AgricartConversation::query()
                ->with(['customer', 'messages.customer', 'messages.user', 'order', 'review.product'])
                ->find($this->viewConversationId);

            if ($selected === null) {
                $this->viewConversationId = null;
            }
        }

        return view('livewire.store.list-agricart-inbox', [
            'conversations' => $items,
            'selectedConversation' => $selected,
            'canEdit' => $this->userCan('edit'),
            'openCount' => AgricartConversation::query()->where('status', AgricartConversation::STATUS_OPEN)->count(),
            'unreadCount' => app(AgricartInboxService::class)->unreadCountForAdmin(),
            'bridgedConversationId' => $selected !== null
                ? app(\App\Services\Communication\CustomerSupportBridge::class)->findConversation($selected)?->id
                : null,
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('store.'.$action);
    }
}
