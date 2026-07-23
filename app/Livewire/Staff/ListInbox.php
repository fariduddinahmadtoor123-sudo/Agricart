<?php

namespace App\Livewire\Staff;

use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\StaffMessage;
use App\Models\StaffMessageThread;
use App\Models\User;
use App\Services\Communication\AttachmentService;
use App\Services\Communication\StaffOpsBridge;
use App\Services\Communication\Support\ActorRef;
use App\Services\Staff\StaffMessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ListInbox extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    /** @var 'all'|'unread' */
    public string $filter = 'all';

    public ?int $openThreadId = null;

    public string $replyText = '';

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $replyFiles = [];

    #[On('staff-inbox-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('staff-inbox-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, ['all', 'unread'], true)) {
            $this->filter = $value;
            $this->resetPage();
        }
    }

    #[On('staff-inbox-clear-filters')]
    public function clearFilters(): void
    {
        $this->filter = 'all';
        $this->resetPage();
    }

    public function openThread(int $threadId): void
    {
        $user = $this->authUser();

        if ($user === null) {
            return;
        }

        $thread = $this->threadForUser($threadId, $user);

        if ($thread === null) {
            return;
        }

        $this->openThreadId = $thread->id;
        $this->replyText = '';
        $this->replyFiles = [];
        app(StaffMessagingService::class)->markThreadRead($thread, $user);
        $this->dispatch('topbar-notifications-refresh');
    }

    public function closeThread(): void
    {
        $this->openThreadId = null;
        $this->replyText = '';
        $this->replyFiles = [];
    }

    public function sendReply(): void
    {
        $user = $this->authUser();

        if ($user === null || $this->openThreadId === null) {
            return;
        }

        $thread = $this->threadForUser($this->openThreadId, $user);

        if ($thread === null) {
            return;
        }

        $body = trim($this->replyText);
        $files = $this->replyFiles;

        if ($body === '' && $files === []) {
            $this->addError('replyText', __('staff_inbox.validation.body_required'));

            return;
        }

        $bridge = app(StaffOpsBridge::class);
        $bridged = $bridge->findConversation($thread);

        if ($files !== [] && $bridged !== null) {
            $names = collect($files)
                ->map(fn ($file) => $file->getClientOriginalName())
                ->filter()
                ->implode(', ');

            $legacyBody = $body !== ''
                ? $body
                : (string) trans_choice('communication.attachments.shared', count($files), ['count' => count($files)]);

            if ($names !== '') {
                $legacyBody .= ($body !== '' ? "\n" : '').$names;
            }

            app(StaffMessagingService::class)->postMessage($thread, $user, $legacyBody);
            app(AttachmentService::class)->postWithFiles(
                $bridged->fresh(['channel']) ?? $bridged,
                ActorRef::user($user->id),
                $files,
                $body !== '' ? $body : null,
            );
        } else {
            app(StaffMessagingService::class)->postMessage($thread, $user, $body);
        }

        $this->replyText = '';
        $this->replyFiles = [];
        $this->resetValidation();
        $this->dispatch('topbar-notifications-refresh');
    }

    public function render(): View
    {
        $user = $this->authUser();
        $openThread = null;
        $invoiceOrder = null;
        $invoiceSale = null;
        $threadMessages = collect();
        $bridgedConversationId = null;
        $bridgedAttachments = collect();

        $threads = StaffMessageThread::query()
            ->with(['latestMessage.sender', 'participants.user', 'creator'])
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user?->id ?? 0))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('subject', 'like', $needle)
                        ->orWhereHas('latestMessage', fn ($message) => $message->where('body', 'like', $needle));
                });
            })
            ->when($this->filter === 'unread' && $user !== null, function ($query) use ($user): void {
                $query->whereNotNull('last_message_at')
                    ->whereHas('participants', function ($participant) use ($user): void {
                        $participant->where('user_id', $user->id)
                            ->where(function ($inner): void {
                                $inner->whereNull('last_read_at')
                                    ->orWhereRaw('staff_message_thread_participants.last_read_at < staff_message_threads.last_message_at');
                            });
                    });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20);

        if ($user !== null && $this->openThreadId !== null) {
            $openThread = $this->threadForUser($this->openThreadId, $user);

            if ($openThread !== null) {
                [$invoiceOrder, $invoiceSale] = $this->resolveInvoiceContext($openThread);
                $threadMessages = $this->messagesForThread($openThread);
                $bridged = app(StaffOpsBridge::class)->findConversation($openThread);
                $bridgedConversationId = $bridged?->id;

                if ($bridged !== null) {
                    $bridgedAttachments = \App\Models\Communication\Attachment::query()
                        ->whereHas('message', fn ($q) => $q->where('conversation_id', $bridged->id))
                        ->with('message')
                        ->latest('id')
                        ->limit(50)
                        ->get();
                }
            }
        }

        return view('livewire.staff.list-inbox', [
            'threads' => $threads,
            'openThread' => $openThread,
            'authUser' => $user,
            'invoiceOrder' => $invoiceOrder,
            'invoiceSale' => $invoiceSale,
            'threadMessages' => $threadMessages,
            'bridgedConversationId' => $bridgedConversationId,
            'bridgedAttachments' => $bridgedAttachments,
        ]);
    }

    /**
     * @return Collection<int, StaffMessage>
     */
    private function messagesForThread(StaffMessageThread $thread): Collection
    {
        return StaffMessage::query()
            ->with('sender')
            ->where('thread_id', $thread->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{0: ?OnlineSaleOrder, 1: ?Sale}
     */
    private function resolveInvoiceContext(StaffMessageThread $thread): array
    {
        if ($thread->context_type !== StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER || $thread->context_id === null) {
            return [null, null];
        }

        $order = OnlineSaleOrder::query()
            ->with([
                'items' => fn ($query) => $query->orderBy('sort_order'),
                'customer',
                'sale' => fn ($query) => $query->with([
                    'items' => fn ($items) => $items->orderBy('sort_order'),
                    'customer',
                    'cashier',
                    'onlineSaleOrder',
                ]),
            ])
            ->find($thread->context_id);

        if ($order === null) {
            return [null, null];
        }

        $sale = $order->sale;

        if ($sale instanceof Sale && $sale->items->isNotEmpty()) {
            return [$order, $sale];
        }

        return [$order, null];
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function threadForUser(int $threadId, User $user): ?StaffMessageThread
    {
        return StaffMessageThread::query()
            ->with(['onlineSaleOrder'])
            ->whereKey($threadId)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->first();
    }
}
