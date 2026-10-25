<?php

namespace App\Filament\Clusters\FxLearn;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SmartCalculatorsPage extends BaseFxLearnPage
{
    protected static ?string $navigationLabel = 'Smart Calculators';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'smart-calculators';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static function introKey(): string
    {
        return 'intro_smart_calculators';
    }
}
