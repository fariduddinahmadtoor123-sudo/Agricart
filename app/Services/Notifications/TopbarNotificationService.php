<?php

namespace App\Services\Notifications;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Filament\Clusters\Approvals\SuppliersPage;
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
use App\Services\Staff\StaffMessagingService;
use App\Services\Store\AgricartInboxService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TopbarNotificationService
{
    /**
     * @return array{total: int, items: list<array{key: string, label: string, meta: string, count: int, url: string}>, inbox_unread: int}
     */
    public function summary(): array
    {
        $items = array_values(array_filter([
            $this->item(
                key: 'staff_inbox',
                count: $this->unreadStaffInbox(),
                pageClass: InboxPage::class,
            ),
            $this->item(
                key: 'staff_approvals',
                count: User::query()->where('status', User::STATUS_PENDING)->count(),
                pageClass: StaffPage::class,
            ),
            $this->item(
                key: 'supplier_approvals',
                count: Supplier::query()->where('status', Supplier::STATUS_PENDING)->count(),
                pageClass: SuppliersPage::class,
            ),
            $this->item(
                key: 'customer_messages',
                count: $this->unreadAgricartInbox(),
                pageClass: MessagesPage::class,
            ),
            $this->item(
                key: 'product_reviews',
                count: $this->pendingProductReviews(),
                pageClass: ReviewsPage::class,
            ),
            $this->onlineOrdersAwaitingItem(),
            $this->item(
                key: 'system_errors',
                count: $this->unreadCriticalSystemErrors(),
                pageClass: SystemErrorsPage::class,
            ),
        ]));

        return [
            'total' => array_sum(array_column($items, 'count')),
            'items' => $items,
            'inbox_unread' => $this->unreadStaffInbox(),
        ];
    }

    /**
     * @return array{key: string, label: string, meta: string, count: int, url: string}|null
     */
    private function onlineOrdersAwaitingItem(): ?array
    {
        $count = OnlineSaleOrder::query()
            ->where('order_status', '!=', OnlineSaleOrder::STATUS_CONVERTED)
            ->where(function ($query): void {
                $query
                    ->where('order_status', OnlineSaleOrder::STATUS_PENDING)
                    ->orWhere('customer_payment_proof_status', OnlineSaleOrder::PROOF_SUBMITTED);
            })
            ->count();

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

    public static function invalidateSystemErrors(): void
    {
        if (! Cache::increment('topbar-notifications:system-errors:version')) {
            Cache::forever('topbar-notifications:system-errors:version', 2);
        }
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

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, meta: string, count: int, url: string}|null
     */
    private function item(string $key, int $count, string $pageClass): ?array
    {
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
}
