<?php

namespace App\Livewire\Tools;

use App\Filament\Clusters\Tools\BarcodePrintingPage;
use App\Filament\Clusters\Tools\HomeExpensesPage;
use App\Filament\Clusters\Tools\PendingPaymentsPage;
use App\Filament\Clusters\Tools\PrintingPage;
use App\Filament\Clusters\Tools\PurchaseSummaryPage;
use App\Filament\Clusters\Tools\ReOrderPage;
use App\Models\ToolsBarcodePrintFolder;
use App\Models\ToolsBillPrintFolder;
use App\Models\ToolsHomeExpenseItem;
use App\Models\ToolsHomeExpenseSheet;
use App\Models\ToolsPendingPaymentSheet;
use App\Models\ToolsPurchaseSummary;
use App\Models\ToolsReorderOrder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    /**
     * @var list<array{key: string, label: string, count: int|string, meta: string|null, url: string}>
     */
    public array $stats = [];

    public function mount(): void
    {
        $this->stats = $this->buildStats();
    }

    /**
     * @return list<array{key: string, label: string, count: int|string, meta: string|null, url: string}>
     */
    protected function buildStats(): array
    {
        $userId = (int) (auth()->id() ?? 0);

        $orderRunning = 0;
        $orderClosed = 0;
        $summaryOpen = 0;
        $summaryClosed = 0;
        $pendingOpen = 0;
        $pendingClosed = 0;
        $billFolders = 0;
        $billImages = 0;
        $barcodeFolders = 0;
        $barcodePrints = 0;
        $homeOpen = 0;
        $homePaid = 0;
        $homeExpected = 0.0;

        if ($userId > 0) {
            $orderRunning = ToolsReorderOrder::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->count();
            $orderClosed = ToolsReorderOrder::query()
                ->where('user_id', $userId)
                ->where('status', 'closed')
                ->count();

            $summaryOpen = ToolsPurchaseSummary::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->count();
            $summaryClosed = ToolsPurchaseSummary::query()
                ->where('user_id', $userId)
                ->where('status', 'closed')
                ->count();

            $pendingOpen = ToolsPendingPaymentSheet::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->count();
            $pendingClosed = ToolsPendingPaymentSheet::query()
                ->where('user_id', $userId)
                ->where('status', 'closed')
                ->count();

            $billFolders = ToolsBillPrintFolder::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->count();
            $billImages = (int) ToolsBillPrintFolder::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->sum('image_count');

            $barcodeFolders = ToolsBarcodePrintFolder::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->count();
            $barcodePrints = (int) ToolsBarcodePrintFolder::query()
                ->where('user_id', $userId)
                ->where('status', 'saved')
                ->sum('print_count');

            $openSheetIds = ToolsHomeExpenseSheet::query()
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->pluck('id');

            if ($openSheetIds->isNotEmpty()) {
                $homeOpen = ToolsHomeExpenseItem::query()
                    ->where('user_id', $userId)
                    ->whereIn('sheet_id', $openSheetIds)
                    ->count();
                $homePaid = ToolsHomeExpenseItem::query()
                    ->where('user_id', $userId)
                    ->whereIn('sheet_id', $openSheetIds)
                    ->where('is_paid', true)
                    ->count();
                $homeExpected = (float) ToolsHomeExpenseItem::query()
                    ->where('user_id', $userId)
                    ->whereIn('sheet_id', $openSheetIds)
                    ->sum('expected_amount');
            }
        }

        return [
            [
                'key' => 're-order',
                'label' => __('agricart.pages.tools.overview_card_orders'),
                'count' => $orderRunning + $orderClosed,
                'meta' => __('agricart.pages.tools.overview_card_orders_meta', [
                    'running' => $orderRunning,
                    'closed' => $orderClosed,
                ]),
                'url' => ReOrderPage::getUrl(),
            ],
            [
                'key' => 'purchase-summary',
                'label' => __('agricart.pages.tools.overview_card_summaries'),
                'count' => $summaryOpen + $summaryClosed,
                'meta' => __('agricart.pages.tools.overview_card_summaries_meta', [
                    'open' => $summaryOpen,
                    'closed' => $summaryClosed,
                ]),
                'url' => PurchaseSummaryPage::getUrl(),
            ],
            [
                'key' => 'pending-payments',
                'label' => __('agricart.pages.tools.overview_card_pending'),
                'count' => $pendingOpen + $pendingClosed,
                'meta' => __('agricart.pages.tools.overview_card_pending_meta', [
                    'open' => $pendingOpen,
                    'closed' => $pendingClosed,
                ]),
                'url' => PendingPaymentsPage::getUrl(),
            ],
            [
                'key' => 'bill-printing',
                'label' => __('agricart.pages.tools.overview_card_bills'),
                'count' => $billFolders,
                'meta' => __('agricart.pages.tools.overview_card_bills_meta', [
                    'images' => $billImages,
                ]),
                'url' => PrintingPage::getUrl(),
            ],
            [
                'key' => 'barcode-printing',
                'label' => __('agricart.pages.tools.overview_card_barcodes'),
                'count' => $barcodeFolders,
                'meta' => __('agricart.pages.tools.overview_card_barcodes_meta', [
                    'folders' => $barcodeFolders,
                    'prints' => $barcodePrints,
                ]),
                'url' => BarcodePrintingPage::getUrl(),
            ],
            [
                'key' => 'home-expenses',
                'label' => __('agricart.pages.tools.overview_card_home_expenses'),
                'count' => $homeOpen,
                'meta' => __('agricart.pages.tools.overview_card_home_expenses_meta', [
                    'open' => $homeOpen,
                    'paid' => $homePaid,
                    'expected' => 'Rs '.number_format($homeExpected, 0, '.', ','),
                ]),
                'url' => HomeExpensesPage::getUrl(),
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.tools.overview');
    }
}
