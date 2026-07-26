<?php

namespace App\Filament\Clusters\Store;

use App\Filament\Clusters\StoreCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class TestimonialsPage extends BaseModulePage
{
    protected static ?string $cluster = StoreCluster::class;

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'testimonials';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected string $view = 'filament.pages.store.testimonials';

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
        return StoreCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
