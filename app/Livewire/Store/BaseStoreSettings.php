<?php

namespace App\Livewire\Store;

use App\Models\StoreSetting;
use App\Services\Store\StoreLayoutService;
use Livewire\Component;

abstract class BaseStoreSettings extends Component
{
    public ?string $statusMessage = null;

    public string $statusType = 'success';

    protected function flashSaved(): void
    {
        app(StoreLayoutService::class)->clearCache();
        $this->statusMessage = __('agricart.store.admin.saved');
        $this->statusType = 'success';
    }

    protected function settings(): StoreSetting
    {
        return StoreSetting::current();
    }
}
