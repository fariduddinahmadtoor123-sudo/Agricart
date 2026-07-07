<?php

use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\CustomerAuthController;
use App\Http\Controllers\Store\CustomerLogoutController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SearchController;
use App\Http\Middleware\SetAgricartLocale;
use App\Filament\Layout\TopbarActions;
use Illuminate\Support\Facades\Route;

Route::middleware([SetAgricartLocale::class])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('store.home');
    Route::get('/login', [CustomerAuthController::class, 'show'])
        ->middleware('guest:customer')
        ->name('store.customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->middleware('guest:customer')
        ->name('store.customer.login.submit');
    Route::post('/customer-register', [CustomerAuthController::class, 'register'])
        ->middleware('guest:customer')
        ->name('store.customer.register.submit');
    Route::post('/logout', CustomerLogoutController::class)
        ->middleware('auth:customer')
        ->name('store.customer.logout');
    Route::get('/register', [\App\Http\Controllers\Store\RegisterController::class, 'show'])->name('auth.register');
    Route::get('/staff-register', [\App\Http\Controllers\Store\StaffRegisterController::class, 'show'])
        ->name('auth.staff.register');
    Route::get('/search', [SearchController::class, 'index'])->name('store.search');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('store.page');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('store.category');
    Route::get('/product/{slug}', [ProductController::class, 'show'])->name('store.product');
    Route::get('/cart', [CartController::class, 'index'])->name('store.cart');
    Route::post('/cart/add', [CartController::class, 'add'])->name('store.cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('store.cart.update');
    Route::post('/cart/remove/{productId}', [CartController::class, 'remove'])->name('store.cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('store.checkout');
    Route::post('/checkout', [CheckoutController::class, 'place'])->name('store.checkout.place');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('store.checkout.success');
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

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    Route::get('/backups/{backupId}/download', [BackupDownloadController::class, 'download'])
        ->name('admin.backups.download');
    Route::get('/backups/export/{type}', [BackupDownloadController::class, 'exportCsv'])
        ->whereIn('type', ['products', 'categories'])
        ->name('admin.backups.export');
    Route::get('/labels/{product}/print', [\App\Http\Controllers\Admin\LabelPrintController::class, 'show'])
        ->name('admin.labels.print');
    Route::get('/purchase-planning/{sheet}/print', [\App\Http\Controllers\Admin\PurchasePlanningPrintController::class, 'show'])
        ->name('admin.purchase-planning.print');
    Route::get('/purchase-payment-summaries/{summary}/print', [\App\Http\Controllers\Admin\PurchasePaymentSummaryPrintController::class, 'show'])
        ->name('admin.purchase-payment-summaries.print');
    Route::get('/purchase-quotations/{quotation}/print', [\App\Http\Controllers\Admin\PurchaseQuotationPrintController::class, 'show'])
        ->name('admin.purchase-quotations.print');
    Route::get('/re-order/print', [\App\Http\Controllers\Admin\ReorderPrintController::class, 'show'])
        ->name('admin.reorder.print');
    Route::get('/price-tags/print', [\App\Http\Controllers\Admin\PriceTagPrintController::class, 'show'])
        ->name('admin.price-tags.print');
    Route::get('/purchases/{purchase}/print', [\App\Http\Controllers\Admin\PurchasePrintController::class, 'show'])
        ->name('admin.purchases.print');
    Route::get('/sales/{sale}/print', [\App\Http\Controllers\Admin\SalePrintController::class, 'show'])
        ->name('admin.sales.print');
    Route::get('/sale-quotations/{quotation}/print', [\App\Http\Controllers\Admin\SaleQuotationPrintController::class, 'show'])
        ->name('admin.sale-quotations.print');
    Route::get('/sale-returns/{saleReturn}/print', [\App\Http\Controllers\Admin\SaleReturnPrintController::class, 'show'])
        ->name('admin.sale-returns.print');
    Route::get('/online-sale-orders/{order}/print', [\App\Http\Controllers\Admin\OnlineSaleOrderPrintController::class, 'show'])
        ->name('admin.online-sale-orders.print');
});
