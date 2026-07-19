<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MediaLibraryPage extends BaseModulePage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'media-library';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings.media-library';

    protected static function requiredPermission(): ?string
    {
        return 'settings.media.view';
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
        return SettingsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
