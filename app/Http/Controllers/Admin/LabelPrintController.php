<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\User;
use App\Services\Printing\LabelRenderService;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LabelPrintController extends Controller
{
    public function show(
        Request $request,
        Product $product,
        PrintProfileService $profiles,
        LabelRenderService $labels,
    ): View {
        $this->authorizeCatalogAccess();

        abort_unless(filled($product->label_code), 404);

        $type = $request->query('type', 'barcode') === 'qr' ? 'qr' : 'barcode';
        $locale = $request->query('locale', 'en') === 'ur' ? 'ur' : 'en';
        $profileKey = (string) $request->query('profile', '');

        $profile = filled($profileKey)
            ? $profiles->findByKey($profileKey)
            : $profiles->defaultProductLabel();

        abort_unless(
            $profile instanceof PrintProfile
                && $profile->category === PrintProfile::CATEGORY_LABEL,
            404,
        );

        $sticker = $labels->stickerData($product, $locale, $type);

        return view('prints.label', [
            'product' => $product,
            'profile' => $profile,
            'sticker' => $sticker,
            'type' => $type,
            'locale' => $locale,
            'autoprint' => $request->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizeCatalogAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'labels', 'print')),
            403,
        );
    }
}
