<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreInquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('company_website'))) {
            return back()->with('store_message_sent', __('agricart.storefront.contact.sent'));
        }

        $validated = $request->validateWithBag('storeMessage', [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s()\-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);

        StoreInquiry::query()->create([
            'name' => trim($validated['name']),
            'phone' => trim($validated['phone']),
            'email' => filled($validated['email'] ?? null) ? strtolower(trim($validated['email'])) : null,
            'message' => trim($validated['message']),
            'status' => StoreInquiry::STATUS_NEW,
            'source_url' => $validated['source_url'] ?? null,
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        return back()->with('store_message_sent', __('agricart.storefront.contact.sent'));
    }
}
