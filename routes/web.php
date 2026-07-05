<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Middleware\SetAgricartLocale;
use App\Filament\Layout\TopbarActions;
use Illuminate\Support\Facades\Route;

Route::middleware([SetAgricartLocale::class])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('store.home');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('store.category');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ur'], true)) {
        abort(404);
    }

    $redirect = request()->query('redirect', '/');

    if (! is_string($redirect) || ! str_starts_with($redirect, '/')) {
        $redirect = '/';
    }

    return redirect($redirect)
        ->withCookie(cookie()->forever('agricart-locale', $locale));
})->name('agricart.locale');

Route::middleware('web')->get('/ai/status', function () {
    if (! auth()->check()) {
        abort(403);
    }

    return response()->json(TopbarActions::aiConnectionState());
})->name('agricart.ai.status');
