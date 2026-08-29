<?php

namespace App\Services\Notifications;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Filament\Clusters\Approvals\SuppliersPage;
use App\Filament\Clusters\Finance\ExpensesPage;
use App\Filament\Clusters\Sales\InboxPage;
use App\Filament\Clusters\Sales\OnlineOrdersPage;
use App\Filament\Clusters\Sales\OnlineQuotationsPage;
use App\Filament\Clusters\Settings\SystemErrorsPage;
use App\Filament\Clusters\Store\MessagesPage;
use App\Filament\Clusters\Store\ReviewsPage;
use App\Models\OnlineSaleOrder;
use App\Models\ProductReview;
use App\Models\Supplier;
use App\Models\SystemError;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Finance\ExpenseScheduleService;
use App\Services\Staff\StaffMessagingService;
use App\Services\Store\AgricartInboxService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ModuleNotificationService
{
    /**
     * Notification keys surfaced on module sub-nav and main sidebar (not the order bell).
     *
     * @var array<string, class-string>
     */
    private const KEY_PAGE_MAP = [
        'staff_inbox' => InboxPage::class,
        'staff_approvals' => StaffPage::class,
        'supplier_approvals' => SuppliersPage::class,
        'customer_messages' => MessagesPage::class,
        'product_reviews' => ReviewsPage::class,
        'expense_bills' => ExpensesPage::class,
        'system_errors' => SystemErrorsPage::class,
    ];

    /**
     * Keys that also share the online-order queue count on Sales sub-pages.
     *
     * @var list<class-string>
     */
    private const ONLINE_ORDER_PAGES = [
        OnlineQuotationsPage::class,
        OnlineOrdersPage::class,
    ];

    /**
     * @var array<string, string>
     */
    private const KEY_MODULE_MAP = [
        'staff_inbox' => 'sales',
        'staff_approvals' => 'approvals',
        'supplier_approvals' => 'approvals',
        'customer_messages' => 'store',
        'product_reviews' => 'store',
        'expense_bills' => 'finance',
        'system_errors' => 'settings',
        'online_orders' => 'sales',
    ];

    public function countForPage(string $pageClass): int
    {
        if (in_array($pageClass, self::ONLINE_ORDER_PAGES, true)) {
            return $this->count('online_orders');
        }

        foreach (self::KEY_PAGE_MAP as $key => $mappedPage) {
            if ($mappedPage === $pageClass) {
                return $this->count($key);
            }
        }

        return 0;
    }

    /**
     * @return array<string, int>
     */
    public function countsByModule(): array
    {
        $totals = [];

        foreach (self::KEY_MODULE_MAP as $key => $module) {
            $item = $this->item($key);

            if ($item === null) {
                continue;
            }

            $totals[$module] = ($totals[$module] ?? 0) + $item['count'];
        }

        return $totals;
    }

    /**
     * @return array{total: int, items: list<array{key: string, label: string, meta: string, count: int, url: string}>, inbox_unread: int, nav_modules: array<string, int>}
     */
    public function bellSummary(): array
    {
        $items = array_values(array_filter([
            $this->onlineOrdersAwaitingItem(),
        ]));

        return [
            'total' => array_sum(array_column($items, 'count')),
            'items' => $items,
            'inbox_unread' => $this->count('staff_inbox'),
            'nav_modules' => $this->countsByModule(),
        ];
    }

    public function count(string $key): int
    {
        return match ($key) {
            'staff_inbox' => $this->unreadStaffInbox(),
            'staff_approvals' => User::query()->where('status', User::STATUS_PENDING)->count(),
            'supplier_approvals' => Supplier::query()->where('status', Supplier::STATUS_PENDING)->count(),
            'customer_messages' => $this->unreadAgricartInbox(),
            'product_reviews' => $this->pendingProductReviews(),
            'online_orders' => $this->onlineOrdersAwaitingCount(),
            'expense_bills' => $this->unpaidIssuedExpenseBills(),
            'system_errors' => $this->unreadCriticalSystemErrors(),
            default => 0,
        };
    }

    /**
     * @return array{key: string, label: string, meta: string, count: int, url: string}|null
     */
    public function item(string $key): ?array
    {
        $pageClass = self::KEY_PAGE_MAP[$key] ?? null;

        if ($pageClass === null) {
            if ($key === 'online_orders') {
                return $this->onlineOrdersAwaitingItem();
            }

            return null;
        }

        $count = $this->count($key);

        if ($count < 1 || ! $pageClass::canAccess()) {
            return null;
        }

        return [
            'key' => $key,
            'label' => (string) __("agricart.topbar.notification_items.{$key}"),
            'meta' => (string) trans_choice("agricart.topbar.notification_counts.{$key}", $count, ['count' => number_format($count)]),
            'count' => $count,
            'url' => $pageClass::getUrl(),
        ];
    }

    /**
     * @return array{key: string, label: string, meta: string, count: int, url: string}|null
     */
    private function onlineOrdersAwaitingItem(): ?array
    {
        $count = $this->onlineOrdersAwaitingCount();

        if ($count < 1) {
            return null;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if (! $user->isSuperAdmin()
            && ! ModulePageAccess::can($user, 'sales', 'online-quotations', 'view')
            && ! ModulePageAccess::can($user, 'sales', 'online-orders', 'view')) {
            return null;
        }

        $url = OnlineQuotationsPage::canAccess()
            ? OnlineQuotationsPage::getUrl()
            : OnlineOrdersPage::getUrl();

        return [
            'key' => 'online_orders',
            'label' => (string) __('agricart.topbar.notification_items.online_orders'),
            'meta' => (string) trans_choice('agricart.topbar.notification_counts.online_orders', $count, ['count' => number_format($count)]),
            'count' => $count,
            'url' => $url,
        ];
    }

    private function onlineOrdersAwaitingCount(): int
    {
        return OnlineSaleOrder::query()
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CONVERTED)
            ->where(function ($query): void {
                $query
                    ->where('order_status', OnlineSaleOrder::STATUS_PENDING)
                    ->orWhere('customer_payment_proof_status', OnlineSaleOrder::PROOF_SUBMITTED);
            })
            ->count();
    }

    private function unreadStaffInbox(): int
    {
        $user = auth()->user();

        if (! $user instanceof User || ! Schema::hasTable('staff_message_thread_participants')) {
            return 0;
        }

        return app(StaffMessagingService::class)->unreadCountFor($user);
    }

    private function pendingProductReviews(): int
    {
        if (! Schema::hasTable('product_reviews')) {
            return 0;
        }

        return ProductReview::query()
            ->where('status', ProductReview::STATUS_PENDING)
            ->count();
    }

    private function unreadAgricartInbox(): int
    {
        if (! Schema::hasTable('agricart_conversations')) {
            if (! Schema::hasTable('store_inquiries')) {
                return 0;
            }

            return \App\Models\StoreInquiry::query()
                ->where('status', \App\Models\StoreInquiry::STATUS_NEW)
                ->count();
        }

        $user = auth()->user();

        return app(AgricartInboxService::class)->unreadCountForAdmin(
            $user instanceof User ? $user : null,
        );
    }

    private function unreadCriticalSystemErrors(): int
    {
        $user = auth()->user();
        $version = (int) Cache::get('topbar-notifications:system-errors:version', 1);
        $userKey = $user instanceof User ? (string) $user->id : 'guest';

        return Cache::remember(
            "topbar-notifications:system-errors:{$version}:{$userKey}",
            now()->addSeconds(15),
            fn (): int => $this->queryUnreadCriticalSystemErrors($user),
        );
    }

    private function queryUnreadCriticalSystemErrors(?User $user): int
    {
        $query = SystemError::query()
            ->where('status', SystemError::STATUS_OPEN)
            ->where('severity', SystemError::SEVERITY_CRITICAL);

        if ($user instanceof User && Schema::hasTable('system_error_reads')) {
            $query->whereDoesntHave('reads', function ($reads) use ($user): void {
                $reads
                    ->where('user_id', $user->id)
                    ->whereColumn('occurrences_seen', '>=', 'system_errors.occurrences');
            });
        }

        return $query->count();
    }

    private function unpaidIssuedExpenseBills(): int
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'issue_day')) {
            return 0;
        }

        return app(ExpenseScheduleService::class)->openCycleCount();
    }

    public static function invalidateSystemErrors(): void
    {
        if (! Cache::increment('topbar-notifications:system-errors:version')) {
            Cache::forever('topbar-notifications:system-errors:version', 2);
        }
    }
}
