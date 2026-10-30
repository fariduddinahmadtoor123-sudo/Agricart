<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\FxLearnAccountType;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountTypesPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Account Types';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'account-types';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.fx-learn.account-types';

    public ?int $editingId = null;

    public string $name = '';

    public string $typeSlug = '';

    public string $description = '';

    public string $startingBalance = '10000';

    public string $phase1Pct = '8';

    public string $phase2Pct = '6';

    public string $phase3Pct = '3';

    public string $maxDayLossPct = '5';

    public string $maxTotalLossPct = '10';

    public string $maxTradeLossPct = '3';

    public string $sortOrder = '100';

    public bool $isActive = true;

    public bool $showForm = false;

    /**
     * @return list<FxLearnAccountType>
     */
    public function types(): array
    {
        return FxLearnAccountType::query()->ordered()->get()->all();
    }

    public function canEditTypes(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->isSuperAdmin()
            || ModulePageAccess::can($user, 'fx-learn', 'account-types', 'edit')
        );
    }

    public function startCreate(): void
    {
        $this->ensureCanEdit();
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $this->ensureCanEdit();
        $type = FxLearnAccountType::query()->findOrFail($id);
        $this->editingId = $type->id;
        $this->name = (string) $type->name;
        $this->typeSlug = (string) $type->slug;
        $this->description = (string) ($type->description ?? '');
        $this->startingBalance = (string) $type->starting_balance;
        $this->phase1Pct = (string) $type->phase1_pct;
        $this->phase2Pct = (string) $type->phase2_pct;
        $this->phase3Pct = (string) $type->phase3_pct;
        $this->maxDayLossPct = (string) $type->max_day_loss_pct;
        $this->maxTotalLossPct = (string) $type->max_total_loss_pct;
        $this->maxTradeLossPct = (string) $type->max_trade_loss_pct;
        $this->sortOrder = (string) $type->sort_order;
        $this->isActive = (bool) $type->is_active;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function saveType(): void
    {
        $this->ensureCanEdit();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'typeSlug' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('fx_learn_account_types', 'slug')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'startingBalance' => ['required', 'numeric', 'min:100', 'max:10000000'],
            'phase1Pct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'phase2Pct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'phase3Pct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'maxDayLossPct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'maxTotalLossPct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'maxTradeLossPct' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'isActive' => ['boolean'],
        ]);

        $slug = filled($validated['typeSlug'] ?? null)
            ? Str::slug((string) $validated['typeSlug'])
            : Str::slug((string) $validated['name']);

        $payload = [
            'name' => trim((string) $validated['name']),
            'slug' => $slug !== '' ? $slug : 'type-'.Str::lower(Str::random(6)),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'starting_balance' => round((float) $validated['startingBalance'], 2),
            'phase1_pct' => round((float) $validated['phase1Pct'], 2),
            'phase2_pct' => round((float) $validated['phase2Pct'], 2),
            'phase3_pct' => round((float) $validated['phase3Pct'], 2),
            'max_day_loss_pct' => round((float) $validated['maxDayLossPct'], 2),
            'max_total_loss_pct' => round((float) $validated['maxTotalLossPct'], 2),
            'max_trade_loss_pct' => round((float) $validated['maxTradeLossPct'], 2),
            'sort_order' => (int) $validated['sortOrder'],
            'is_active' => (bool) ($validated['isActive'] ?? false),
        ];

        if ($this->editingId) {
            FxLearnAccountType::query()->whereKey($this->editingId)->update($payload);
            $message = __('agricart.pages.fx-learn.atype_updated');
        } else {
            FxLearnAccountType::query()->create($payload);
            $message = __('agricart.pages.fx-learn.atype_created');
        }

        $this->cancelForm();

        Notification::make()->title($message)->success()->send();
    }

    public function deleteType(int $id): void
    {
        $this->ensureCanEdit();
        FxLearnAccountType::query()->whereKey($id)->delete();
        if ($this->editingId === $id) {
            $this->cancelForm();
        }
        Notification::make()->title(__('agricart.pages.fx-learn.atype_deleted'))->success()->send();
    }

    public function toggleActive(int $id): void
    {
        $this->ensureCanEdit();
        $type = FxLearnAccountType::query()->findOrFail($id);
        $type->is_active = ! $type->is_active;
        $type->save();
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
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

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->typeSlug = '';
        $this->description = '';
        $this->startingBalance = '10000';
        $this->phase1Pct = '8';
        $this->phase2Pct = '6';
        $this->phase3Pct = '3';
        $this->maxDayLossPct = '5';
        $this->maxTotalLossPct = '10';
        $this->maxTradeLossPct = '3';
        $this->sortOrder = '100';
        $this->isActive = true;
    }

    private function ensureCanEdit(): void
    {
        if ($this->canEditTypes()) {
            return;
        }

        Notification::make()
            ->title(__('agricart.pages.fx-learn.atype_err_no_edit'))
            ->danger()
            ->send();

        abort(403);
    }
}
