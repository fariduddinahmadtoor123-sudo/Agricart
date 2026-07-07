<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Clusters\SettingsCluster;
use BackedEnum;
use App\Filament\Pages\BaseModulePage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AiLogsPage extends BaseModulePage
{

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'ai-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings.ai-logs';

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