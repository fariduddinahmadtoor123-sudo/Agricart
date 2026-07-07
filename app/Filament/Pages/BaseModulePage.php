<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChecksAgricartPermissions;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use Filament\Pages\Page;

abstract class BaseModulePage extends Page
{
    use ChecksAgricartPermissions;
    use HasTranslatedNavigationLabel;
}
