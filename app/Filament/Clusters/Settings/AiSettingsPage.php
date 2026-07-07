<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AiSettingsPage extends BaseModulePage
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'AI';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'ai-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings.ai-settings';

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