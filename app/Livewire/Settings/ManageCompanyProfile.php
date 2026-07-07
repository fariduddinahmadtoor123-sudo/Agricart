<?php

namespace App\Livewire\Settings;

use App\Services\Settings\CompanyProfileService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageCompanyProfile extends Component
{
    public int $profileVersion = 0;

    #[On('company-profile-saved')]
    public function refreshProfile(): void
    {
        $this->profileVersion++;
    }

    public function render(): View
    {
        $service = app(CompanyProfileService::class);
        $profile = $service->current();

        return view('livewire.settings.manage-company-profile', [
            'profile' => $profile,
            'isConfigured' => $profile->isConfigured(),
            'phones' => $profile->savedPhones(),
            'addresses' => $profile->savedAddresses(),
        ]);
    }
}
