<?php

namespace App\Services\Activity;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpensePayment;
use App\Models\FundTransfer;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ActivitySummaryService
{
    /**
     * Subject types shown in the staff work summary (create counts from activity_logs),
     * mapped to the page View key used to decide whether the metric is relevant for that user.
     *
     * @return array<class-string, string>
     */
    public function summarySubjectPermissionKeys(): array
    {
        return [
            Product::class => 'catalog.products.view',
            Category::class => 'catalog.categories.view',
            Brand::class => 'catalog.brands.view',
            Unit::class => 'catalog.units.view',
            PackingUnit::class => 'catalog.packing-units.view',
            CatalogAttribute::class => 'catalog.attributes.view',
            CatalogControl::class => 'catalog.controls.view',
            Supplier::class => 'contacts.suppliers.view',
            Customer::class => 'contacts.customers.view',
        ];
    }

    /**
     * Subject types shown in the staff work summary (create counts from activity_logs).
     *
     * @return list<class-string>
     */
    public function summarySubjectTypes(): array
    {
        return array_keys($this->summarySubjectPermissionKeys());
    }

    /**
     * @return array{
     *     today: array{label: string, from: string, to: string, lines: list<array{key: string, label: string, count: int}>},
     *     yesterday: array{label: string, from: string, to: string, lines: list<array{key: string, label: string, count: int}>},
     *     last_7_days: array{label: string, from: string, to: string, lines: list<array{key: string, label: string, count: int}>}
     * }
     */
    public function forUser(User $user): array
    {
        $now = now();

        return [
            'today' => $this->periodSummary(
                $user,
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                __('agricart.activity.summary.today'),
            ),
            'yesterday' => $this->periodSummary(
                $user,
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                __('agricart.activity.summary.yesterday'),
            ),
            'last_7_days' => $this->periodSummary(
                $user,
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                __('agricart.activity.summary.last_7_days'),
            ),
        ];
    }

    /**
     * @return array{label: string, from: string, to: string, lines: list<array{key: string, label: string, count: int}>}
     */
    private function periodSummary(User $user, CarbonInterface $from, CarbonInterface $to, string $label): array
    {
        $lines = [];
        $permissionKeys = $this->summarySubjectPermissionKeys();

        $createdCounts = ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('event', ActivityEvent::Created->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('subject_type', $this->summarySubjectTypes())
            ->selectRaw('subject_type, COUNT(*) as aggregate')
            ->groupBy('subject_type')
            ->pluck('aggregate', 'subject_type');

        foreach ($permissionKeys as $type => $permissionKey) {
            $count = (int) ($createdCounts[$type] ?? 0);

            if ($count < 1 || ! $this->userMaySeeMetric($user, $permissionKey)) {
                continue;
            }

            $lines[] = [
                'key' => class_basename($type),
                'label' => $this->subjectLabel($type),
                'count' => $count,
            ];
        }

        $expensePayments = $this->countByCreatedBy(ExpensePayment::class, $user->id, $from, $to);

        if ($expensePayments > 0 && $this->userMaySeeMetric($user, 'finance.expense-payments.view')) {
            $lines[] = [
                'key' => 'ExpensePayment',
                'label' => __('agricart.activity.summary.expense_payments'),
                'count' => $expensePayments,
            ];
        }

        $fundTransfers = $this->countByCreatedBy(FundTransfer::class, $user->id, $from, $to);

        if ($fundTransfers > 0 && $this->userMaySeeMetric($user, 'finance.cash-bank-transfers.view')) {
            $lines[] = [
                'key' => 'FundTransfer',
                'label' => __('agricart.activity.summary.fund_transfers'),
                'count' => $fundTransfers,
            ];
        }

        $logins = ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('event', ActivityEvent::Login->value)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        if ($logins > 0) {
            $lines[] = [
                'key' => 'login',
                'label' => __('agricart.activity.summary.logins'),
                'count' => $logins,
            ];
        }

        return [
            'label' => $label,
            'from' => $from->toDateString(),
            'to' => $to instanceof Carbon ? $to->toDateString() : Carbon::parse($to)->toDateString(),
            'lines' => $lines,
        ];
    }

    private function userMaySeeMetric(User $user, string $permissionKey): bool
    {
        return ModulePageAccess::userHasKey($user, $permissionKey);
    }

    /**
     * @param  class-string  $modelClass
     */
    private function countByCreatedBy(string $modelClass, int $userId, CarbonInterface $from, CarbonInterface $to): int
    {
        $model = new $modelClass;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_by')) {
            return 0;
        }

        return $modelClass::query()
            ->where('created_by', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * @param  class-string  $type
     */
    private function subjectLabel(string $type): string
    {
        $key = 'agricart.activity.subjects.'.class_basename($type);
        $label = __($key);

        return $label === $key ? class_basename($type) : $label;
    }
}
