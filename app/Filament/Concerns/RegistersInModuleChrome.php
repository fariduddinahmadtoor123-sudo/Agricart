<?php

namespace App\Filament\Concerns;

/**
 * Controls visibility in Agricart module chrome (horizontal sub-nav).
 * Independent of Filament's shouldRegisterNavigation() so Filament flags cannot hide our tabs.
 */
trait RegistersInModuleChrome
{
    public static function registersInModuleChrome(): bool
    {
        return true;
    }
}
