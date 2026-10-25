<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseFxLearnPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected string $view = 'filament.pages.fx-learn.shell';

    abstract protected static function introKey(): string;

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
        return FxLearnCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }

    public function pageTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function pageIntro(): string
    {
        return __('agricart.pages.fx-learn.'.static::introKey());
    }

    public function placeholderLabel(): string
    {
        return __('agricart.pages.fx-learn.placeholder_card');
    }

    public function comingSoonBadge(): string
    {
        return __('agricart.pages.fx-learn.coming_soon_badge');
    }
}
