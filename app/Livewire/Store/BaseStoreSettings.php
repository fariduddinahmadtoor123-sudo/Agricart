<?php

namespace App\Livewire\Store;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\StoreSetting;
use App\Services\Store\StoreLayoutService;
use Livewire\Component;

abstract class BaseStoreSettings extends Component
{
    use AuthorizesSensitiveActions;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    protected function flashSaved(): void
    {
        $this->authorizeStoreSettings();
        app(StoreLayoutService::class)->clearCache();
        $this->statusMessage = __('agricart.store.admin.saved');
        $this->statusType = 'success';
    }

    protected function settings(): StoreSetting
    {
        $this->authorizeStoreSettings();

        return StoreSetting::current();
    }

    protected function canManageStoreSettings(): bool
    {
        return $this->canAccessAny('store.edit');
    }

    protected function authorizeStoreSettings(): void
    {
        $this->authorizeAny('store.edit');
    }
}
