<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\AgricartConversation;
use App\Models\Customer;
use App\Services\Store\AgricartInboxService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerInboxController extends Controller
{
    public function index(Request $request, StoreLayoutService $layout, AgricartInboxService $inbox): View
    {
        $customer = $this->customer($request);

        $conversations = AgricartConversation::query()
            ->where('customer_id', $customer->id)
            ->with('latestMessage')
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(20);

        return view('store.inbox.index', [
            ...$layout->layoutData(),
            'conversations' => $conversations,
            'unreadCount' => $inbox->unreadCountForCustomer($customer),
        ]);
    }

    public function show(
        Request $request,
        int $conversation,
        StoreLayoutService $layout,
        AgricartInboxService $inbox,
    ): View {
        $customer = $this->customer($request);
        $model = $this->ownedConversation($customer, $conversation);
        $inbox->markReadByCustomer($model, $customer);

        return view('store.inbox.show', [
            ...$layout->layoutData(),
            'conversation' => $model->fresh(['messages.customer', 'messages.user', 'order', 'review.product']),
            'unreadCount' => $inbox->unreadCountForCustomer($customer),
        ]);
    }

    public function store(Request $request, AgricartInboxService $inbox): RedirectResponse
    {
        $customer = $this->customer($request);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:200'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            (string) $validated['subject'],
            (string) $validated['body'],
        );

        return redirect()
            ->route('store.customer.inbox.show', $conversation->id)
            ->with('customer_auth_status', __('agricart.inbox.sent'));
    }

    public function reply(
        Request $request,
        int $conversation,
        AgricartInboxService $inbox,
    ): RedirectResponse {
        $customer = $this->customer($request);
        $model = $this->ownedConversation($customer, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $inbox->replyAsCustomer($model, $customer, (string) $validated['body']);

        return redirect()
            ->route('store.customer.inbox.show', $model->id)
            ->with('customer_auth_status', __('agricart.inbox.reply_sent'));
    }

    private function ownedConversation(Customer $customer, int $conversationId): AgricartConversation
    {
        return AgricartConversation::query()
            ->where('customer_id', $customer->id)
            ->whereKey($conversationId)
            ->firstOrFail();
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            abort(403);
        }

        return $customer;
    }
}
