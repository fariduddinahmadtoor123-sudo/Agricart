<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\OnlineSaleOrder;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Sales\OnlineSaleOrderDocumentAccess;
use App\Services\Sales\OnlineSaleOrderService;
use App\Support\Documentation\DocumentationPdfText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnlineSaleOrderPackingSlipPrintController extends Controller
{
    public function show(OnlineSaleOrder $order): View|Response
    {
        $this->authorizePackingAccess($order);

        $context = $this->buildContext($order);

        if (request()->query('download') === 'pdf') {
            return $this->pdfResponse($order, $context);
        }

        return view('prints.online-sale-order-packing-slip', $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function pdfResponse(OnlineSaleOrder $order, array $context): Response
    {
        $html = view('prints.online-sale-order-packing-slip-pdf', $context)->render();
        $binary = DocumentationPdfText::render($html, (string) $context['locale'])->output();
        $filename = 'packing-list-'.$order->order_number.'.pdf';

        return response($binary, SymfonyResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(OnlineSaleOrder $order): array
    {
        $order->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $itemIds = $this->parseItemIds(request()->query('item_ids'));

        if ($itemIds !== []) {
            $order->setRelation(
                'items',
                $order->items->whereIn('id', $itemIds)->values(),
            );
        }

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, OnlineSaleOrderService::LOCALES, true)
            ? $localeQuery
            : (in_array($order->locale, OnlineSaleOrderService::LOCALES, true) ? $order->locale : 'en');
        app()->setLocale($locale);

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $company = CompanyProfile::current();
        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return [
            'order' => $order,
            'locale' => $locale,
            'paper' => $paper,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storeLogoUrl' => $company->displayLogoUrl(),
            'receiptProfile' => $receiptProfile,
            'autoprint' => request()->query('autoprint', '1') !== '0' && request()->query('download') !== 'pdf',
            'downloadPdfUrl' => route('admin.online-sale-orders.packing-slip', array_filter([
                'order' => $order,
                'download' => 'pdf',
                'locale' => $locale,
                'item_ids' => request()->query('item_ids'),
                'autoprint' => 0,
            ], static fn ($value): bool => $value !== null && $value !== '')),
        ];
    }

    /**
     * @return list<int>
     */
    private function parseItemIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map('intval', $raw))));
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            'intval',
            preg_split('/[,\s]+/', $raw) ?: [],
        ))));
    }

    private function authorizePackingAccess(OnlineSaleOrder $order): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User && OnlineSaleOrderDocumentAccess::canPrintPacking($user, $order),
            403,
        );
    }
}
