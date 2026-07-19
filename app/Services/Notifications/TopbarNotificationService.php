<?php

namespace App\Services\Notifications;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Filament\Clusters\Sales\OnlineOrdersPage;
use App\Filament\Clusters\Settings\SystemErrorsPage;
use App\Filament\Clusters\Store\MessagesPage;
use App\Models\OnlineSaleOrder;
use App\Models\StoreInquiry;
use App\Models\SystemError;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TopbarNotificationService
{
    /**
     * @return array{total: int, items: list<array{key: string, label: string, meta: string, count: int, url: string}>}
     */
    public function summary(): array
    {
        $items = array_values(array_filter([
            $this->item(
                key: 'staff_approvals',
                count: User::query()->where('status', User::STATUS_PENDING)->count(),
                pageClass: StaffPage::class,
            ),
            $this->item(
                key: 'customer_messages',
                count: StoreInquiry::query()->where('status', StoreInquiry::STATUS_NEW)->count(),
                pageClass: MessagesPage::class,
            ),
            $this->item(
                key: 'online_orders',
                count: OnlineSaleOrder::query()
                    ->where(function ($query): void {
                        $query
                            ->where('order_status', OnlineSaleOrder::STATUS_PENDING)
                            ->orWhere('customer_payment_proof_status', OnlineSaleOrder::PROOF_SUBMITTED);
                    })
                    ->count(),
                pageClass: OnlineOrdersPage::class,
            ),
            $this->item(
                key: 'system_errors',
                count: $this->unreadCriticalSystemErrors(),
                pageClass: SystemErrorsPage::class,
            ),
        ]));

        return [
            'total' => array_sum(array_column($items, 'count')),
            'items' => $items,
        ];
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
