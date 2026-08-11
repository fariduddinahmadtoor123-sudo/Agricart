<?php

namespace App\Services\Approvals;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Models\User;
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;

class ApprovalsSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limit = 8): array
    {
        $term = trim($term);

        if ($term === '' || ! StaffPage::canAccess()) {
            return [];
        }

        $needle = '%' . $term . '%';
        $url = StaffPage::getUrl();

        $results = User::query()
            ->whereIn('status', [User::STATUS_PENDING, User::STATUS_REJECTED])
            ->where(function (Builder $query) use ($needle): void {
                $query->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('user_number', 'like', $needle);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (User $user) use ($url): array {
                return SearchHit::make(
                    $user->name ?: (string) __('agricart.approvals.overview.search_untitled'),
                    filled($user->user_number) ? (string) $user->user_number : $user->email,
                    $url,
                    $user->getFilamentAvatarUrl(),
                );
            })
            ->all();

        if ($results === []) {
            return [];
        }

        return ['staff' => $results];
    }
}
