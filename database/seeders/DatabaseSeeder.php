<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UnitSeeder::class);
        $this->call(PackingUnitSeeder::class);
        $this->call(DeliveryMethodSeeder::class);
        $this->call(AttributeSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(QuantityControlSeeder::class);
    }
}
