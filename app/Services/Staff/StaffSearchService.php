<?php

namespace App\Services\Staff;

use App\Filament\Clusters\Staff\StaffPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StaffSearchService
{
    public function __construct(
        private readonly StaffMemberService $members,
    ) {}

    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limit = 8): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $needle = '%' . $term . '%';

        $query = $this->members->rosterQuery()
            ->where(function (Builder $builder) use ($needle): void {
                $builder->where('name', 'like', $needle)
                    ->orWhere('user_number', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            })
            ->orderBy('name')
            ->limit($limit);

        if (! StaffPermissions::canViewRoster($user)) {
            $query->where('id', $user->id);
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return [];
        }

        $url = StaffPage::getUrl();

        return [
            'members' => $results->map(fn (User $member): array => [
                'label' => $member->name ?: (string) __('agricart.staff.overview.search_untitled'),
                'meta' => filled($member->user_number) ? (string) $member->user_number : null,
                'url' => $url,
            ])->all(),
        ];
    }
}
