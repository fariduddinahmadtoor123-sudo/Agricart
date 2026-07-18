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
                count: SystemError::query()
                    ->where('status', SystemError::STATUS_OPEN)
                    ->where('severity', SystemError::SEVERITY_CRITICAL)
                    ->count(),
                pageClass: SystemErrorsPage::class,
            ),
        ]));

        return [
            'total' => array_sum(array_column($items, 'count')),
            'items' => $items,
        ];
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
