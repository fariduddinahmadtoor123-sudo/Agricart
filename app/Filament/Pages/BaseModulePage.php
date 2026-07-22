<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChecksAgricartPermissions;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use App\Filament\Concerns\RegistersInModuleChrome;
use Filament\Pages\Page;

abstract class BaseModulePage extends Page
{
    use ChecksAgricartPermissions;
    use HasTranslatedNavigationLabel;
    use RegistersInModuleChrome;
}
