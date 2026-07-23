<?php

use App\Filament\Layout\TopbarActions;
use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\BackupUploadController;
use App\Http\Controllers\Admin\BankAccountPrintController;
use App\Http\Controllers\Admin\BrandAgricartReviewPrintController;
use App\Http\Controllers\Admin\LabelBatchPrintController;
use App\Http\Controllers\Admin\LabelPrintController;
use App\Http\Controllers\Admin\OnlineSaleOrderPackingSlipPrintController;
use App\Http\Controllers\Admin\OnlineSaleOrderPrintController;
use App\Http\Controllers\Admin\PriceTagPrintController;
use App\Http\Controllers\Admin\PurchasePaymentSummaryPrintController;
use App\Http\Controllers\Admin\PurchasePlanningPrintController;
use App\Http\Controllers\Admin\PurchasePrintController;
use App\Http\Controllers\Admin\PurchaseQuotationPrintController;
use App\Http\Controllers\Admin\ReorderPrintController;
use App\Http\Controllers\Admin\SalePrintController;
use App\Http\Controllers\Admin\SaleQuotationPrintController;
use App\Http\Controllers\Admin\SaleReturnPrintController;
use App\Http\Controllers\Admin\TopbarNotificationController;
use App\Http\Controllers\Admin\UserDocumentController;
use App\Http\Controllers\Admin\VisitorAnalyticsExportController;
use App\Http\Controllers\SchedulerRunController;
use App\Http\Controllers\Store\AnalyticsPresenceController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\CustomerAuthController;
use App\Http\Controllers\Store\CustomerLogoutController;
use App\Http\Controllers\Store\CustomerOrderController;
use App\Http\Controllers\Store\CustomerOrderPaymentController;
use App\Http\Controllers\Store\CustomerPaymentProofController;
use App\Http\Controllers\Store\CustomerPasswordResetController;
use App\Http\Controllers\Store\CustomerProfileController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\RegisterController;
use App\Http\Controllers\Store\RobotsController;
use App\Http\Controllers\Store\SearchController;
use App\Http\Controllers\Store\SitemapController;
use App\Http\Controllers\Store\StaffRegisterController;
use App\Http\Controllers\Store\StoreInquiryController;
use App\Http\Middleware\BlockOnlineStoreMaintenance;
use App\Http\Middleware\SetAgricartLocale;
use App\Http\Middleware\TrackStorefrontAnalytics;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('store.robots');
Route::get('/internal/schedule-run', SchedulerRunController::class)->name('internal.schedule-run');

Route::middleware([
    SetAgricartLocale::class,
    BlockOnlineStoreMaintenance::class,
    TrackStorefrontAnalytics::class,
])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('store.home');
    Route::get('/sitemap.xml', SitemapController::class)->name('store.sitemap');
    Route::get('/login', [CustomerAuthController::class, 'show'])
        ->middleware('guest:customer')
        ->name('store.customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->middleware(['guest:customer', 'throttle:customer-login'])
        ->name('store.customer.login.submit');
    Route::post('/customer-register', [CustomerAuthController::class, 'register'])
        ->middleware(['guest:customer', 'throttle:customer-register'])
        ->name('store.customer.register.submit');
    Route::middleware('guest:customer')->group(function (): void {
        Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])
            ->name('store.customer.password.request');
        Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])
            ->middleware('throttle:customer-password-reset')
            ->name('store.customer.password.email');
        Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])
            ->name('store.customer.password.reset');
        Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])
            ->middleware('throttle:customer-password-reset')
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
        Route::get('/account/orders/{order}/payment-proof', [CustomerPaymentProofController::class, 'customer'])
            ->name('store.customer.orders.payment-proof.show');
        Route::post('/account/orders/{order}/payment-proof', [CustomerOrderPaymentController::class, 'store'])
            ->middleware('throttle:customer-payment-proof')
            ->name('store.customer.orders.payment-proof');
    });
    Route::get('/register', [RegisterController::class, 'show'])->name('auth.register');
    Route::get('/staff-register', [StaffRegisterController::class, 'show'])
        ->name('auth.staff.register');
    Route::get('/search/fragment', [SearchController::class, 'fragment'])->name('store.search.fragment');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('store.search.suggest');
    Route::get('/search', [SearchController::class, 'index'])->name('store.search');
    Route::post('/analytics/presence', [AnalyticsPresenceController::class, 'update'])
        ->middleware('throttle:90,1')
        ->name('store.analytics.presence');
    Route::post('/contact-message', [StoreInquiryController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('store.contact-message');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('store.page');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('store.category');
    Route::get('/product/{slug}', [ProductController::class, 'show'])->name('store.product');
    Route::get('/cart', [CartController::class, 'index'])->name('store.cart');
    Route::get('/cart/print', [CartController::class, 'print'])->name('store.cart.print');
    Route::post('/cart/add', [CartController::class, 'add'])->name('store.cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('store.cart.update');
    Route::post('/cart/remove/{productId}', [CartController::class, 'remove'])->name('store.cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('store.checkout');
    Route::post('/checkout', [CheckoutController::class, 'place'])
        ->middleware('throttle:store-checkout')
        ->name('store.checkout.place');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('store.checkout.success');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ur'], true)) {
        abort(404);
    }

    $redirect = request()->query('redirect', '/');

    if (
        ! is_string($redirect)
        || ! str_starts_with($redirect, '/')
        || str_starts_with($redirect, '//')
        || str_contains($redirect, '\\')
        || preg_match('/[\x00-\x1F\x7F]/', $redirect)
        || parse_url($redirect, PHP_URL_SCHEME) !== null
        || parse_url($redirect, PHP_URL_HOST) !== null
    ) {
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
    Route::get('/notifications/summary', TopbarNotificationController::class)
        ->name('admin.notifications.summary');
    Route::get('/users/{user}/documents/{side}', UserDocumentController::class)
        ->whereIn('side', ['front', 'back'])
        ->name('admin.users.documents.show');
    Route::get('/store/visitor-analytics/export', VisitorAnalyticsExportController::class)
        ->name('admin.store.visitor-analytics.export');
    Route::get('/backups/{backupId}/download', [BackupDownloadController::class, 'download'])
        ->name('admin.backups.download');
    Route::post('/backups/upload', [BackupUploadController::class, 'store'])
        ->name('admin.backups.upload');
    Route::get('/backups/export/{type}', [BackupDownloadController::class, 'exportCsv'])
        ->whereIn('type', ['products', 'categories'])
        ->name('admin.backups.export');
    Route::get('/labels/{product}/print', [LabelPrintController::class, 'show'])
        ->name('admin.labels.print');
    Route::post('/labels/print-batch', [LabelBatchPrintController::class, 'store'])
        ->name('admin.labels.batch-print');
    Route::get('/purchase-planning/{sheet}/print', [PurchasePlanningPrintController::class, 'show'])
        ->name('admin.purchase-planning.print');
    Route::get('/purchase-payment-summaries/{summary}/print', [PurchasePaymentSummaryPrintController::class, 'show'])
        ->name('admin.purchase-payment-summaries.print');
    Route::get('/purchase-quotations/{quotation}/print', [PurchaseQuotationPrintController::class, 'show'])
        ->name('admin.purchase-quotations.print');
    Route::get('/re-order/print', [ReorderPrintController::class, 'show'])
        ->name('admin.reorder.print');
    Route::get('/price-tags/print', [PriceTagPrintController::class, 'show'])
        ->name('admin.price-tags.print');
    Route::get('/purchases/{purchase}/print', [PurchasePrintController::class, 'show'])
        ->name('admin.purchases.print');
    Route::get('/sales/{sale}/print', [SalePrintController::class, 'show'])
        ->name('admin.sales.print');
    Route::get('/brands/{brand}/print', [BrandAgricartReviewPrintController::class, 'show'])
        ->name('admin.brands.print');
    Route::get('/sale-quotations/{quotation}/print', [SaleQuotationPrintController::class, 'show'])
        ->name('admin.sale-quotations.print');
    Route::get('/sale-returns/{saleReturn}/print', [SaleReturnPrintController::class, 'show'])
        ->name('admin.sale-returns.print');
    Route::get('/online-sale-orders/{order}/print', [OnlineSaleOrderPrintController::class, 'show'])
        ->name('admin.online-sale-orders.print');
    Route::get('/bank-accounts/{account}/print', [BankAccountPrintController::class, 'details'])
        ->name('admin.bank-accounts.print');
    Route::get('/bank-accounts/{account}/print-summary', [BankAccountPrintController::class, 'summary'])
        ->name('admin.bank-accounts.print-summary');
    Route::get('/bank-accounts/{account}/history-print', [BankAccountPrintController::class, 'history'])
        ->name('admin.bank-accounts.history-print');
    Route::get('/online-sale-orders/{order}/packing-slip', [OnlineSaleOrderPackingSlipPrintController::class, 'show'])
        ->name('admin.online-sale-orders.packing-slip');
    Route::get('/online-sale-orders/{order}/payment-proof', [CustomerPaymentProofController::class, 'admin'])
        ->name('admin.online-sale-orders.payment-proof.show');
});
