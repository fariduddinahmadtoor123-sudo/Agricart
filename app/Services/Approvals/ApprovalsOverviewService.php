<?php

namespace App\Services\Approvals;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Models\User;

class ApprovalsOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $cards = [
            $this->staffCard(),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function staffCard(): array
    {
        $pending = User::query()->where('status', User::STATUS_PENDING)->count();
        $rejected = User::query()->where('status', User::STATUS_REJECTED)->count();
        $count = $pending + $rejected;

        return $this->card('staff', $count, $pending, StaffPage::class);
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function card(string $key, int $count, ?int $active, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.approvals.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }
}
