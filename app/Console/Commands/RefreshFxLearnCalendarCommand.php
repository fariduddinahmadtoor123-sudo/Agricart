<?php

namespace App\Console\Commands;

use App\Services\FxLearn\EconomicCalendarService;
use Illuminate\Console\Command;

class RefreshFxLearnCalendarCommand extends Command
{
    protected $signature = 'fx-learn:refresh-calendar';

    protected $description = 'Refresh FX Learn economic calendar feeds and archive released figures';

    public function handle(EconomicCalendarService $calendar): int
    {
        $snap = $calendar->refresh();
        $count = count($snap['events'] ?? []);
        $source = (string) ($snap['source'] ?? 'empty');

        $this->info("FX Learn calendar refreshed ({$source}, {$count} events).");

        return self::SUCCESS;
    }
}
