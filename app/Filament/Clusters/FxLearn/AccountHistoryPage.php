<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\FxLearnDemoAccount;
use App\Models\FxLearnDemoTrade;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\FxLearn\DemoTerminalService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AccountHistoryPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Account History';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'account-history';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'filament.pages.fx-learn.account-history';

    public ?int $selectedAccountId = null;

    public string $tradePeriod = 'week';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function canManageAccounts(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->isSuperAdmin()
            || ModulePageAccess::can($user, 'fx-learn', 'account-history', 'edit')
            || ModulePageAccess::can($user, 'fx-learn', 'trade-terminal', 'edit')
        );
    }

    public function canSeeAllUsers(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->isSuperAdmin()
            || ModulePageAccess::can($user, 'fx-learn', 'account-history', 'edit')
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyRows(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        $query = FxLearnDemoAccount::query()
            ->with(['user:id,name,email'])
            ->withCount([
                'trades as open_trades_count' => fn ($q) => $q->where('status', 'open'),
                'trades as closed_trades_count' => fn ($q) => $q->where('status', 'closed'),
            ])
            ->orderByDesc('created_at');

        if (! $this->canSeeAllUsers()) {
            $query->where('user_id', $user->id);
        }

        return $query->limit(200)->get()->map(static function (FxLearnDemoAccount $account): array {
            $owner = $account->user;

            return [
                'id' => (int) $account->id,
                'account_no' => (string) $account->account_no,
                'user_name' => (string) ($owner?->name ?? '—'),
                'user_email' => (string) ($owner?->email ?? ''),
                'balance' => (float) $account->balance,
                'starting' => (float) $account->starting_balance,
                'is_active' => (bool) $account->is_active,
                'open_trades' => (int) ($account->open_trades_count ?? 0),
                'closed_trades' => (int) ($account->closed_trades_count ?? 0),
                'created_at' => agricart_datetime($account->created_at, 'Y-m-d H:i', false),
            ];
        })->all();
    }

    public function createAccount(DemoTerminalService $terminal): void
    {
        if (! $this->canManageAccounts()) {
            Notification::make()
                ->title(__('agricart.pages.fx-learn.ahist_err_no_edit'))
                ->danger()
                ->send();

            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $account = $terminal->createAccount($user);
        Notification::make()
            ->title(__('agricart.pages.fx-learn.term_account_created', ['no' => $account->account_no]))
            ->success()
            ->send();
    }

    public function openAccountTrades(int $accountId): void
    {
        if ($this->findVisibleAccount($accountId) === null) {
            Notification::make()
                ->title(__('agricart.pages.fx-learn.ahist_trade_forbidden'))
                ->danger()
                ->send();

            return;
        }

        $this->selectedAccountId = $accountId;
        $this->tradePeriod = in_array($this->tradePeriod, ['day', 'week', 'month', 'all'], true)
            ? $this->tradePeriod
            : 'week';
    }

    public function closeAccountTrades(): void
    {
        $this->selectedAccountId = null;
    }

    public function setTradePeriod(string $period): void
    {
        if (! in_array($period, ['day', 'week', 'month', 'all'], true)) {
            return;
        }

        $this->tradePeriod = $period;
    }

    /**
     * @return array{account_no: string, user_name: string, period: string}|null
     */
    public function selectedAccountMeta(): ?array
    {
        if ($this->selectedAccountId === null) {
            return null;
        }

        $account = $this->findVisibleAccount($this->selectedAccountId);
        if ($account === null) {
            return null;
        }

        return [
            'account_no' => (string) $account->account_no,
            'user_name' => (string) ($account->user?->name ?? '—'),
            'period' => $this->tradePeriod,
        ];
    }

    /**
     * Closed (+ still-open) trades for the selected account in the chosen period.
     *
     * @return list<array<string, mixed>>
     */
    public function selectedTradeRows(): array
    {
        if ($this->selectedAccountId === null) {
            return [];
        }

        $account = $this->findVisibleAccount($this->selectedAccountId);
        if ($account === null) {
            return [];
        }

        $query = FxLearnDemoTrade::query()
            ->where('demo_account_id', $account->id)
            ->orderByDesc('opened_at')
            ->limit(300);

        $from = $this->periodStart($this->tradePeriod);
        if ($from !== null) {
            $query->where(function ($q) use ($from): void {
                $q->where('opened_at', '>=', $from)
                    ->orWhere(function ($inner) use ($from): void {
                        $inner->whereNotNull('closed_at')->where('closed_at', '>=', $from);
                    });
            });
        }

        return $query->get()->map(static function (FxLearnDemoTrade $trade): array {
            return [
                'id' => (int) $trade->id,
                'symbol' => (string) $trade->label,
                'side' => strtoupper((string) $trade->side),
                'lots' => (float) $trade->lots,
                'entry' => (float) $trade->entry_price,
                'exit' => $trade->exit_price !== null ? (float) $trade->exit_price : null,
                'pnl' => $trade->pnl !== null ? (float) $trade->pnl : null,
                'status' => (string) $trade->status,
                'reason' => (string) ($trade->close_reason ?: '—'),
                'opened_at' => agricart_datetime($trade->opened_at, 'Y-m-d H:i', false),
                'closed_at' => $trade->closed_at
                    ? agricart_datetime($trade->closed_at, 'Y-m-d H:i', false)
                    : '—',
                'digits' => (int) $trade->digits,
            ];
        })->all();
    }

    private function findVisibleAccount(int $accountId): ?FxLearnDemoAccount
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return null;
        }

        $query = FxLearnDemoAccount::query()
            ->with(['user:id,name,email'])
            ->whereKey($accountId);

        if (! $this->canSeeAllUsers()) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    private function periodStart(string $period): ?CarbonImmutable
    {
        $now = CarbonImmutable::now(agricart_display_timezone());

        return match ($period) {
            'day' => $now->startOfDay(),
            'week' => $now->startOfWeek(),
            'month' => $now->startOfMonth(),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return FxLearnCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
