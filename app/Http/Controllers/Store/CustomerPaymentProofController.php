<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderPaymentAccess;
use App\Services\Security\PrivateDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPaymentProofController extends Controller
{
    public function customer(Request $request, string $order, PrivateDocumentStorage $documents): StreamedResponse
    {
        $customer = $request->user('customer');

        $model = OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer?->id)
            ->firstOrFail();

        return $this->response($model, $documents);
    }

    public function admin(OnlineSaleOrder $order, PrivateDocumentStorage $documents): StreamedResponse
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User && OnlineSaleOrderPaymentAccess::canViewProof($user),
            403,
        );

        return $this->response($order, $documents);
    }

    private function response(OnlineSaleOrder $order, PrivateDocumentStorage $documents): StreamedResponse
    {
        $path = $order->customer_bank_receipt_path;

        abort_unless(
            is_string($path)
            && str_starts_with($path, 'online-orders/customer-receipts/'),
            404,
        );

        // Owner or authorized staff only. Move any legacy public original onto the
        // private disk and delete the public copy so /storage/... cannot leak it.
        abort_unless($documents->ensurePrivate($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
