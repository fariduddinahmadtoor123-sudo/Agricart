<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlineSaleOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPaymentProofController extends Controller
{
    public function customer(Request $request, string $order): StreamedResponse
    {
        $customer = $request->user('customer');

        $model = OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer?->id)
            ->firstOrFail();

        return $this->response($model);
    }

    public function admin(OnlineSaleOrder $order): StreamedResponse
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
            && ($user->isSuperAdmin() || $user->hasAnyPermission(['sales.view', 'sales.edit'])),
            403,
        );

        return $this->response($order);
    }

    private function response(OnlineSaleOrder $order): StreamedResponse
    {
        $path = $order->customer_bank_receipt_path;

        abort_unless(
            is_string($path)
            && str_starts_with($path, 'online-orders/customer-receipts/'),
            404,
        );

        $disk = 'local';

        if (! Storage::disk($disk)->exists($path)
            && config('security.legacy_public_payment_proof_fallback', true)) {
            $disk = 'public';
        }

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
