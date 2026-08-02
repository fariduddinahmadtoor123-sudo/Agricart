<?php

namespace App\Livewire\Store;

use App\Services\Store\StoreRegistrationService;
use Illuminate\Contracts\View\View;

class RegistrationSettings extends BaseStoreSettings
{
    public string $staff_title_en = '';

    public string $staff_title_ur = '';

    public string $staff_body_en = '';

    public string $staff_body_ur = '';

    public bool $staff_require_acceptance = true;

    public string $supplier_title_en = '';

    public string $supplier_title_ur = '';

    public string $supplier_body_en = '';

    public string $supplier_body_ur = '';

    public bool $supplier_require_acceptance = false;

    public function mount(StoreRegistrationService $registration): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $this->hydrateFromSettings($registration);
    }

    public function save(StoreRegistrationService $registration): void
    {
        $this->authorizeStoreSettings();
        $registration->save([
            'staff_title_en' => $this->staff_title_en,
            'staff_title_ur' => $this->staff_title_ur,
            'staff_body_en' => $this->staff_body_en,
            'staff_body_ur' => $this->staff_body_ur,
            'staff_require_acceptance' => $this->staff_require_acceptance,
            'supplier_title_en' => $this->supplier_title_en,
            'supplier_title_ur' => $this->supplier_title_ur,
            'supplier_body_en' => $this->supplier_body_en,
            'supplier_body_ur' => $this->supplier_body_ur,
            'supplier_require_acceptance' => $this->supplier_require_acceptance,
        ]);
        $this->hydrateFromSettings($registration);
        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.registration-settings');
    }

    private function hydrateFromSettings(StoreRegistrationService $registration): void
    {
        $settings = $registration->settings();

        $this->staff_title_en = $settings->staff_title_en ?? '';
        $this->staff_title_ur = $settings->staff_title_ur ?? '';
        $this->staff_body_en = $settings->staff_body_en ?? '';
        $this->staff_body_ur = $settings->staff_body_ur ?? '';
        $this->staff_require_acceptance = (bool) $settings->staff_require_acceptance;
        $this->supplier_title_en = $settings->supplier_title_en ?? '';
        $this->supplier_title_ur = $settings->supplier_title_ur ?? '';
        $this->supplier_body_en = $settings->supplier_body_en ?? '';
        $this->supplier_body_ur = $settings->supplier_body_ur ?? '';
        $this->supplier_require_acceptance = (bool) $settings->supplier_require_acceptance;
    }
}
