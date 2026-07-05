<?php

namespace App\Filament\Layout;

use Filament\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BreadcrumbResolver
{
    /**
     * @return array<string, string>
     */
    public static function resolve(): array
    {
        return self::resolveFromPath('/' . ltrim(request()->path(), '/'));
    }

    /**
     * @return array<string, string>
     */
    public static function resolveFromPath(string $path): array
    {
        if (! filament()->hasBreadcrumbs()) {
            return [];
        }

        $page = self::resolvePageFromPath($path);

        if (! $page instanceof Page) {
            return [];
        }

        return $page->getBreadcrumbs();
    }

    protected static function resolvePageFromPath(string $path): ?Page
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable) {
            return null;
        }

        $uses = $route->getAction('uses');

        if (is_string($uses) && str_contains($uses, '@')) {
            $uses = explode('@', $uses, 2)[0];
        }

        if (! is_string($uses) || ! is_subclass_of($uses, Page::class)) {
            return null;
        }

        return app($uses);
    }
}
