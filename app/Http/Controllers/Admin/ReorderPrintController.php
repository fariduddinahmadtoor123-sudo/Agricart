<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Purchasing\ReorderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReorderPrintController extends Controller
{
    public function show(Request $request, ReorderService $reorderService): View
    {
        $this->authorizePurchasingAccess();

        $paper = $request->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $scope = $request->query('scope', 'reorder');
        $filter = $request->query('filter', 'available');
        $locale = app()->getLocale();

        if ($scope === 'queue') {
            $items = $reorderService->queueItems($filter === 'stale' ? 'stale' : 'pending');
        } else {
            $items = $reorderService->reorderCandidates($locale, $filter);

            if ($request->filled('products')) {
                $ids = collect(explode(',', (string) $request->query('products')))
                    ->map(fn (string $id): int => (int) trim($id))
                    ->filter()
                    ->all();
                $items = $items->filter(fn (array $row): bool => in_array((int) $row['product_id'], $ids, true))->values();
            }
        }

        $rows = $reorderService->rowsForPrint($items, $locale);
        $orderSummary = $reorderService->summarizeOrderValue($items);
        $half = (int) ceil(count($rows) / 2);
        $leftRows = array_slice($rows, 0, $half);
        $rightRows = array_slice($rows, $half);

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return view('prints.reorder', [
            'rows' => $rows,
            'leftRows' => $leftRows,
            'rightRows' => $rightRows,
            'paper' => $paper,
            'receiptProfile' => $receiptProfile,
            'locale' => $locale,
            'autoprint' => $request->query('autoprint', '1') !== '0',
            'scope' => $scope,
            'orderSummary' => $orderSummary,
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || $user->hasPermission('purchasing.view')),
            403,
        );
    }
}
