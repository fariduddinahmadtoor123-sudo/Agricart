<?php

namespace App\Filament\Clusters\Vault;

use App\Filament\Clusters\VaultCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\PersonalVaultFile;
use App\Models\PersonalVaultSecret;
use App\Models\User;
use App\Services\Vault\PersonalVaultService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AllBoxesPage extends BaseModulePage
{
    use WithFileUploads;

    protected static ?string $cluster = VaultCluster::class;

    protected static ?string $navigationLabel = 'All Boxes';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'all-boxes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected string $view = 'filament.pages.vault.all-boxes';

    public ?int $selectedUserId = null;

    public string $search = '';

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $label = '';

    public string $docType = 'other';

    public string $validFrom = '';

    public string $validUntil = '';

    public string $fileFilter = 'all';

    public int $quotaMb = 100;

    public string $secretTitle = '';

    public string $secretSiteUrl = '';

    public string $secretUsername = '';

    public string $secretPassword = '';

    public string $secretNotes = '';

    /** @var array<int, true> */
    public array $revealedSecretIds = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->quotaMb = (int) config('agricart.personal_vault.default_quota_mb', 100);
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
        return VaultCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }

    public function selectUser(int $userId): void
    {
        abort_unless(static::canAccess(), 403);
        $this->selectedUserId = User::query()->whereKey($userId)->exists() ? $userId : null;
        $this->reset('upload', 'label', 'validFrom', 'validUntil', 'secretTitle', 'secretSiteUrl', 'secretUsername', 'secretPassword', 'secretNotes');
        $this->docType = 'other';
        $this->fileFilter = 'all';
        $this->revealedSecretIds = [];
        if ($this->selectedUserId) {
            $owner = User::query()->find($this->selectedUserId);
            if ($owner) {
                $this->quotaMb = (int) round(app(PersonalVaultService::class)->quotaBytesFor($owner) / (1024 * 1024));
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function staffUsers(): Collection
    {
        $term = trim($this->search);
        $query = User::query()
            ->orderBy('name')
            ->limit(80);

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return $query->get(['id', 'name', 'email']);
    }

    public function selectedOwner(): ?User
    {
        if (! $this->selectedUserId) {
            return null;
        }

        return User::query()->find($this->selectedUserId);
    }

    /**
     * @return array{used: int, quota: int, remaining: int, files: int, max_files: int, used_display: string, quota_display: string, percent: float}|null
     */
    public function usage(): ?array
    {
        $owner = $this->selectedOwner();
        if (! $owner) {
            return null;
        }

        return app(PersonalVaultService::class)->usageSummary($owner);
    }

    /**
     * @return Collection<int, PersonalVaultFile>
     */
    public function files(): Collection
    {
        $owner = $this->selectedOwner();
        if (! $owner) {
            return collect();
        }

        return app(PersonalVaultService::class)->filesForOwner($owner, $this->fileFilter);
    }

    /**
     * @return array{renew_soon: int, expired: int, renewable: int, days: int, total_alerts: int}|null
     */
    public function renewalReminders(): ?array
    {
        $owner = $this->selectedOwner();
        if (! $owner) {
            return null;
        }

        return app(PersonalVaultService::class)->renewalReminderSummary($owner);
    }

    public function setFileFilter(string $filter): void
    {
        $this->fileFilter = app(PersonalVaultService::class)->normalizeFileFilter($filter);
    }

    public function saveUpload(): void
    {
        abort_unless(static::canAccess(), 403);
        $owner = $this->selectedOwner();
        abort_unless($owner instanceof User, 404);

        $this->validate([
            'upload' => ['required', 'file', 'max:'.((int) config('agricart.personal_vault.max_file_mb', 8) * 1024)],
            'label' => ['nullable', 'string', 'max:191'],
            'docType' => ['required', 'string', 'in:'.implode(',', PersonalVaultFile::DOC_TYPES)],
            'validFrom' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:validFrom'],
        ]);

        try {
            app(PersonalVaultService::class)->store(
                $owner,
                $this->upload,
                $this->label !== '' ? $this->label : null,
                $this->docType,
                $this->validFrom !== '' ? $this->validFrom : null,
                $this->validUntil !== '' ? $this->validUntil : null,
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('agricart.pages.vault.upload_failed');
            Notification::make()->title((string) $message)->danger()->send();

            return;
        }

        $this->reset('upload', 'label', 'validFrom', 'validUntil');
        $this->docType = 'other';
        Notification::make()
            ->title(__('agricart.pages.vault.upload_ok'))
            ->success()
            ->send();
    }

    public function deleteFile(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $file = PersonalVaultFile::query()->whereKey($id)->firstOrFail();
        app(PersonalVaultService::class)->assertCanManage($actor, $file);
        app(PersonalVaultService::class)->delete($file);

        Notification::make()
            ->title(__('agricart.pages.vault.delete_ok'))
            ->success()
            ->send();
    }

    public function saveQuota(): void
    {
        abort_unless(static::canAccess(), 403);
        $owner = $this->selectedOwner();
        abort_unless($owner instanceof User, 404);

        $this->validate([
            'quotaMb' => ['required', 'integer', 'min:1', 'max:5120'],
        ]);

        app(PersonalVaultService::class)->setQuota($owner, (int) $this->quotaMb * 1024 * 1024);

        Notification::make()
            ->title(__('agricart.pages.vault.quota_saved'))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, PersonalVaultSecret>
     */
    public function secrets(): Collection
    {
        if (! $this->selectedUserId) {
            return collect();
        }

        return PersonalVaultSecret::query()
            ->where('user_id', $this->selectedUserId)
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function saveSecret(): void
    {
        abort_unless(static::canAccess(), 403);
        $owner = $this->selectedOwner();
        abort_unless($owner instanceof User, 404);

        $this->validate([
            'secretTitle' => ['required', 'string', 'max:191'],
            'secretSiteUrl' => ['nullable', 'string', 'max:500'],
            'secretUsername' => ['nullable', 'string', 'max:255'],
            'secretPassword' => ['required', 'string', 'max:500'],
            'secretNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            app(PersonalVaultService::class)->storeSecret($owner, [
                'title' => $this->secretTitle,
                'site_url' => $this->secretSiteUrl,
                'username' => $this->secretUsername,
                'password' => $this->secretPassword,
                'notes' => $this->secretNotes,
            ]);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('agricart.pages.vault.secret_failed');
            Notification::make()->title((string) $message)->danger()->send();

            return;
        }

        $this->reset('secretTitle', 'secretSiteUrl', 'secretUsername', 'secretPassword', 'secretNotes');
        Notification::make()
            ->title(__('agricart.pages.vault.secret_ok'))
            ->success()
            ->send();
    }

    public function deleteSecret(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $secret = PersonalVaultSecret::query()->whereKey($id)->firstOrFail();
        app(PersonalVaultService::class)->assertCanManage($actor, $secret);
        app(PersonalVaultService::class)->deleteSecret($secret);
        unset($this->revealedSecretIds[$id]);

        Notification::make()
            ->title(__('agricart.pages.vault.secret_deleted'))
            ->success()
            ->send();
    }

    public function toggleRevealSecret(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        $secret = PersonalVaultSecret::query()->whereKey($id)->firstOrFail();
        if (isset($this->revealedSecretIds[$id])) {
            unset($this->revealedSecretIds[$id]);
        } else {
            $this->revealedSecretIds[$id] = true;
        }
    }
}
