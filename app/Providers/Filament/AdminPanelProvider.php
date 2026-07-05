<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Dashboard\OverviewPage as DashboardOverviewPage;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Agricart')
            ->font('Arial, sans-serif', provider: LocalFontProvider::class)
            ->monoFont('Arial, sans-serif', provider: LocalFontProvider::class)
            ->serifFont('Arial, sans-serif', provider: LocalFontProvider::class)
            ->breadcrumbs()
            ->darkMode()
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('11.25rem')
            ->colors([
                'primary' => Color::hex('#83B735'),
                'warning' => Color::hex('#FBBC34'),
                'danger' => Color::hex('#DC2626'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->homeUrl(fn (): string => DashboardOverviewPage::getUrl())
            ->globalSearch(false)
            ->pages([])
            ->widgets([])
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): View => view('filament.components.topbar.breadcrumbs-slot'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): View => view('filament.components.topbar.actions'),
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_BEFORE,
                fn (): View => view('filament.components.module.chrome-slot'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): View => view('filament.components.page.breadcrumbs-source'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): View => view('filament.components.form-panel.index'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
