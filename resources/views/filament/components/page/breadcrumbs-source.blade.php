<div id="agricart-breadcrumbs-source" hidden>
    @include('filament.components.breadcrumbs-nav', [
        'breadcrumbs' => \App\Filament\Layout\BreadcrumbResolver::resolve(),
    ])
</div>

@php
    $activeModule = \App\Filament\Layout\ModuleChrome::active();
@endphp

@if ($activeModule)
    <div id="agricart-module-chrome-source" hidden>
        @include('filament.components.module.chrome', [
            'subNavigationClass' => $activeModule['subNavigation'],
            'toolbarClass' => $activeModule['toolbar'],
            'navLabel' => $activeModule['navLabel'],
            'moduleKey' => $activeModule['key'],
        ])
    </div>
@endif
