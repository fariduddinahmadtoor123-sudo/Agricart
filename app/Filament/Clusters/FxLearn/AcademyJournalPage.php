<?php

namespace App\Filament\Clusters\FxLearn;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AcademyJournalPage extends BaseFxLearnPage
{
    protected static ?string $navigationLabel = 'Academy & Journal';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'academy-journal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static function introKey(): string
    {
        return 'intro_academy_journal';
    }
}
