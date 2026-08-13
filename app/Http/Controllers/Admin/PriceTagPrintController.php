<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Purchasing\PriceTagService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PriceTagPrintController extends Controller
{
    public function show(
        Request $request,
        PriceTagService $priceTags,
        PrintProfileService $profiles,
    ): View {
        $this->authorizePurchasingAccess();

        $payload = Cache::pull('price_tag_print:' . (string) $request->query('token', ''));

        abort_if(! is_array($payload), 404);

        $profileKey = (string) ($payload['profile_key'] ?? '');
        $profile = filled($profileKey)
            ? $profiles->findByKey($profileKey)
            : $profiles->defaultPriceLabel();

        abort_if($profile === null, 404);

        $options = [
            'locale' => (string) ($payload['locale'] ?? 'en'),
            'code_type' => (string) ($payload['code_type'] ?? 'barcode'),
            'show_store_name' => (bool) ($payload['show_store_name'] ?? true),
            'show_product_name' => (bool) ($payload['show_product_name'] ?? true),
            'show_cost_price' => (bool) ($payload['show_cost_price'] ?? false),
            'show_sale_price' => (bool) ($payload['show_sale_price'] ?? true),
        ];

        $jobs = $priceTags->expandPrintJobs($payload['lines'] ?? [], $options);

        return view('prints.price-tag', [
            'profile' => $profile,
            'jobs' => $jobs,
            'options' => $options,
            'locale' => $options['locale'] === 'ur' ? 'ur' : 'en',
            'autoprint' => $request->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'price-tags', 'print'),
            403,
        );
    }
}
