<?php

use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\CustomerAuthController;
use App\Http\Controllers\Store\CustomerLogoutController;
use App\Http\Controllers\Store\CustomerOrderController;
use App\Http\Controllers\Store\CustomerPasswordResetController;
use App\Http\Controllers\Store\CustomerProfileController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SearchController;
use App\Http\Middleware\BlockOnlineStoreMaintenance;
use App\Http\Middleware\SetAgricartLocale;
use App\Filament\Layout\TopbarActions;
use Illuminate\Support\Facades\Route;

Route::middleware([SetAgricartLocale::class, BlockOnlineStoreMaintenance::class])->group(function (): void {
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
    Route::middleware('guest:customer')->group(function (): void {
        Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])
            ->name('store.customer.password.request');
        Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])
            ->name('store.customer.password.email');
        Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])
            ->name('store.customer.password.reset');
        Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])
            ->name('store.customer.password.update');
    });
    Route::post('/logout', CustomerLogoutController::class)
        ->middleware('auth:customer')
        ->name('store.customer.logout');
    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', [CustomerProfileController::class, 'show'])->name('store.customer.account');
        Route::post('/account/profile', [CustomerProfileController::class, 'updateProfile'])->name('store.customer.account.profile');
        Route::post('/account/address', [CustomerProfileController::class, 'updateAddress'])->name('store.customer.account.address');
        Route::post('/account/password', [CustomerProfileController::class, 'updatePassword'])->name('store.customer.account.password');
        Route::get('/account/orders', [CustomerOrderController::class, 'index'])->name('store.customer.orders');
        Route::get('/account/orders/{order}', [CustomerOrderController::class, 'show'])->name('store.customer.orders.show');
    });
    Route::get('/register', [\App\Http\Controllers\Store\RegisterController::class, 'show'])->name('auth.register');
    Route::get('/staff-register', [\App\Http\Controllers\Store\StaffRegisterController::class, 'show'])
        ->name('auth.staff.register');
    Route::get('/search/fragment', [SearchController::class, 'fragment'])->name('store.search.fragment');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('store.search.suggest');
    Route::get('/search', [SearchController::class, 'index'])->name('store.search');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('store.page');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('store.category');
    Route::get('/product/{slug}', [ProductController::class, 'show'])->name('store.product');
    Route::get('/cart', [CartController::class, 'index'])->name('store.cart');
    Route::get('/cart/print', [CartController::class, 'print'])->name('store.cart.print');
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
    Route::get('/bank-accounts/{account}/print', [\App\Http\Controllers\Admin\BankAccountPrintController::class, 'details'])
        ->name('admin.bank-accounts.print');
    Route::get('/bank-accounts/{account}/print-summary', [\App\Http\Controllers\Admin\BankAccountPrintController::class, 'summary'])
        ->name('admin.bank-accounts.print-summary');
    Route::get('/bank-accounts/{account}/history-print', [\App\Http\Controllers\Admin\BankAccountPrintController::class, 'history'])
        ->name('admin.bank-accounts.history-print');
    Route::get('/online-sale-orders/{order}/packing-slip', [\App\Http\Controllers\Admin\OnlineSaleOrderPackingSlipPrintController::class, 'show'])
        ->name('admin.online-sale-orders.packing-slip');
});
