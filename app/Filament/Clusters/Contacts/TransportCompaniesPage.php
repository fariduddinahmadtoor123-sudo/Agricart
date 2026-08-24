<?php

namespace App\Filament\Clusters\Contacts;

use App\Filament\Clusters\ContactsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class TransportCompaniesPage extends BaseModulePage
{
    protected static ?string $cluster = ContactsCluster::class;

    protected static ?string $navigationLabel = 'Carriers';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'transport-companies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected string $view = 'filament.pages.contacts.transport-companies';

    public static function getNavigationLabel(): string
    {
        return __('agricart.pages.contacts.transport-companies');
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
        return ContactsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
