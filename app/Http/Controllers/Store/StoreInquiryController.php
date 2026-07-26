<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\StoreInquiry;
use App\Services\Store\AgricartInboxService;
use App\Support\SafeInternalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreInquiryController extends Controller
{
    public function store(Request $request, AgricartInboxService $inbox): RedirectResponse
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            $intended = SafeInternalRedirect::normalize(
                $request->input('source_url') ?: $request->headers->get('referer'),
                '/',
            ) ?? '/';

            SafeInternalRedirect::putIntended($intended);

            return redirect()
                ->route('store.customer.login', ['redirect' => $intended])
                ->with('customer_auth_status', __('agricart.storefront.contact.login_required'));
        }

        if (filled($request->input('company_website'))) {
            return back()->with('store_message_sent', __('agricart.storefront.contact.sent'));
        }

        $validated = $request->validateWithBag('storeMessage', [
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $name = trim($customer->displayName());
        $phone = trim($customer->primaryPhone());
        $email = filled($customer->email) ? strtolower(trim((string) $customer->email)) : null;

        if ($name === '' || $phone === '') {
            throw ValidationException::withMessages([
                'message' => [__('agricart.storefront.contact.profile_incomplete')],
            ])->errorBag('storeMessage');
        }

        // Keep a StoreInquiry row for legacy admin filters / analytics.
        $inquiry = StoreInquiry::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'message' => trim($validated['message']),
            'status' => StoreInquiry::STATUS_NEW,
            'source_url' => $validated['source_url'] ?? null,
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        $conversation = $inbox->startContactConversation($customer, [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'message' => $validated['message'],
            'source_url' => $validated['source_url'] ?? null,
        ]);

        $conversation->update(['store_inquiry_id' => $inquiry->id]);

        return redirect()
            ->route('store.customer.inbox.show', $conversation->id)
            ->with('store_message_sent', __('agricart.storefront.contact.sent_inbox'));
    }
}
