<?php

namespace Database\Seeders;

use App\Services\Communication\ChannelRegistry;
use Illuminate\Database\Seeder;

class CommunicationChannelSeeder extends Seeder
{
    public function run(): void
    {
        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }
}
