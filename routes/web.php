<?php

use App\Http\Controllers\Admin\ApiIntegrationController as AdminApiIntegrationController;
use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlockingLogController as AdminBlockingLogController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CarrybeeWebhookController as AdminCarrybeeWebhookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChildcategoryController as AdminChildcategoryController;
use App\Http\Controllers\Admin\ColorController as AdminColorController;
use App\Http\Controllers\Admin\CourierApiController as AdminCourierApiController;
use App\Http\Controllers\Admin\CourierDashboardController as AdminCourierDashboardController;
use App\Http\Controllers\Admin\CronJobController as AdminCronJobController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailSettingController as AdminEmailSettingController;
use App\Http\Controllers\Admin\ErrorLogController as AdminErrorLogController;
use App\Http\Controllers\Admin\FraudOrderRuleSettingController as AdminFraudOrderRuleSettingController;
use App\Http\Controllers\Admin\FraudSettingController as AdminFraudSettingController;
use App\Http\Controllers\Admin\IncompleteOrderController as AdminIncompleteOrderController;
use App\Http\Controllers\Admin\IpBlockController as AdminIpBlockController;
use App\Http\Controllers\Admin\LandingPageController as AdminLandingPageController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrderImportExportController as AdminOrderImportExportController;
use App\Http\Controllers\Admin\OrderRestrictionSettingController as AdminOrderRestrictionSettingController;
use App\Http\Controllers\Admin\OrderStatusController as AdminOrderStatusController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PaymentGatewayController as AdminPaymentGatewayController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ProductAiController as AdminProductAiController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImportExportController as AdminProductImportExportController;
use App\Http\Controllers\Admin\ProductPageCustomizationController as AdminProductPageCustomizationController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\RedXWebhookController as AdminRedXWebhookController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SeoSettingController as AdminSeoSettingController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ShippingChargeController as AdminShippingChargeController;
use App\Http\Controllers\Admin\SitemapController as AdminSitemapController;
use App\Http\Controllers\Admin\SizeController as AdminSizeController;
use App\Http\Controllers\Admin\SteadfastWebhookController as AdminSteadfastWebhookController;
use App\Http\Controllers\Admin\StoryController as AdminStoryController;
use App\Http\Controllers\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\SystemUpdateController as AdminSystemUpdateController;
use App\Http\Controllers\Admin\TrackingController as AdminTrackingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WebsiteCustomizationController as AdminWebsiteCustomizationController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\AiAssistantController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CustomerAuthController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LandingPageController as StoreLandingPageController;
use App\Http\Controllers\Frontend\OrderOtpController;
use App\Http\Controllers\Frontend\PageController as StorePageController;
use App\Http\Controllers\Frontend\ShopController;
use App\Http\Controllers\Frontend\SslCommerzPaymentController;
use App\Http\Controllers\InstallController;
use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Installer routes
|--------------------------------------------------------------------------
*/
Route::middleware('installed')->prefix('install')->name('install.')->group(function (): void {
    Route::get('/', [InstallController::class, 'step1'])->name('step1');
    Route::get('/database', [InstallController::class, 'step2'])->name('step2');
    Route::post('/database', [InstallController::class, 'postStep2'])->name('post-step2');
    Route::get('/admin', [InstallController::class, 'step3'])->name('step3');
    Route::post('/admin', [InstallController::class, 'postStep3'])->name('post-step3');
    Route::get('/complete', [InstallController::class, 'step4'])->name('step4');
});

/*
|--------------------------------------------------------------------------
| Courier webhooks (CSRF-exempt)
|--------------------------------------------------------------------------
*/
Route::post('/api/steadfast/webhook', AdminSteadfastWebhookController::class)->name('webhooks.steadfast');
Route::post('/api/redx/webhook', AdminRedXWebhookController::class)->name('webhooks.redx');
Route::post('/api/carrybee/webhook', AdminCarrybeeWebhookController::class)->name('webhooks.carrybee');

/*
|--------------------------------------------------------------------------
| Storefront routes (Phase 2)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/search/live', [ShopController::class, 'liveSearch'])->name('shop.search.live');
Route::get('/shop/category/{category:slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/product/{product:slug}', [ShopController::class, 'show'])->name('product.show');

$landingPrefix = 'page';
try {
    $landingPrefix = \App\Support\LandingUrl::prefix();
} catch (\Throwable) {
    $landingPrefix = 'page';
}

Route::get("/{$landingPrefix}/{slug}", [StoreLandingPageController::class, 'show'])->name('landing.show');
Route::post("/{$landingPrefix}/{slug}/checkout", [StoreLandingPageController::class, 'checkout'])->middleware('throttle:checkout')->name('landing.checkout');
Route::post("/{$landingPrefix}/{slug}/progress", [StoreLandingPageController::class, 'progress'])->middleware('throttle:checkout')->name('landing.progress');

if ($landingPrefix !== 'page') {
    Route::get('/page/{slug}', fn (string $slug) => redirect()->route('landing.show', $slug));
    Route::post('/page/{slug}/checkout', [StoreLandingPageController::class, 'checkout'])->middleware('throttle:checkout');
    Route::post('/page/{slug}/progress', [StoreLandingPageController::class, 'progress'])->middleware('throttle:checkout');
}

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::post('/cart/modal', [CartController::class, 'storeModal'])->name('cart.store.modal');
Route::post('/cart/buy-now/cancel', [CartController::class, 'cancelBuyNow'])->name('cart.buy-now.cancel');
Route::post('/cart/modal/remove', [CartController::class, 'destroyModal'])->name('cart.destroy.modal');
Route::post('/cart/modal/update', [CartController::class, 'updateModal'])->name('cart.update.modal');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::get('/checkout/modal-data', [CheckoutController::class, 'modalData'])->name('checkout.modal.data');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::post('/checkout/modal', [CheckoutController::class, 'storeModal'])->middleware('throttle:checkout')->name('checkout.modal');
Route::post('/checkout/incomplete', [CheckoutController::class, 'progress'])->middleware('throttle:checkout')->name('checkout.incomplete');
Route::get('/checkout/otp/status', [OrderOtpController::class, 'status'])->name('checkout.otp.status');
Route::post('/checkout/otp/send', [OrderOtpController::class, 'send'])->middleware('throttle:5,1')->name('checkout.otp.send');
Route::post('/checkout/otp/verify', [OrderOtpController::class, 'verify'])->middleware('throttle:10,1')->name('checkout.otp.verify');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/product-modal/{product}', [ShopController::class, 'productModalData'])->name('product.modal.data');
Route::post('/ai-assistant/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:30,1')->name('ai-assistant.chat');

Route::match(['get', 'post'], '/payments/sslcommerz/success', [SslCommerzPaymentController::class, 'success'])
    ->name('payments.sslcommerz.success');
Route::match(['get', 'post'], '/payments/sslcommerz/fail', [SslCommerzPaymentController::class, 'fail'])
    ->name('payments.sslcommerz.fail');
Route::match(['get', 'post'], '/payments/sslcommerz/cancel', [SslCommerzPaymentController::class, 'cancel'])
    ->name('payments.sslcommerz.cancel');
Route::post('/payments/sslcommerz/ipn', [SslCommerzPaymentController::class, 'ipn'])
    ->name('payments.sslcommerz.ipn');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:register')->name('register.store');

    Route::get('/customer/auth/{provider}/redirect', [CustomerAuthController::class, 'redirectToProvider'])
        ->whereIn('provider', ['facebook', 'google'])
        ->name('customer.auth.provider.redirect');
});

Route::get('/customer/auth/{provider}/callback', [CustomerAuthController::class, 'handleProviderCallback'])
    ->whereIn('provider', ['facebook', 'google'])
    ->name('customer.auth.provider.callback');

Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->middleware('throttle:password-reset')->name('password.update');
    Route::get('/orders/{order}', [AccountController::class, 'showOrder'])->name('orders.show');
});

// Storefront Content, Policy & Service Pages
Route::get('/privacy-policy', [StorePageController::class, 'showPrivacy'])->name('page.privacy');
Route::get('/refund-return-policy', [StorePageController::class, 'showRefund'])->name('page.refund-return');
Route::get('/terms-and-conditions', [StorePageController::class, 'showTerms'])->name('page.terms');
Route::get('/shipping-policy', [StorePageController::class, 'showShipping'])->name('page.shipping');
Route::get('/payment-policy', [StorePageController::class, 'showPayment'])->name('page.payment');
Route::get('/how-to-order', [StorePageController::class, 'showHowToOrder'])->name('page.how-to-order');
Route::match(['get', 'post'], '/order-track', [StorePageController::class, 'track'])
    ->middleware('throttle:10,1')
    ->name('order-track.index');
Route::get('/faq', [StorePageController::class, 'faq'])->name('faq.index');
Route::get('/contact', [StorePageController::class, 'contact'])->name('contact.index');
Route::post('/contact', [StorePageController::class, 'submitContact'])
    ->middleware('throttle:5,1')
    ->name('contact.store');
Route::get('/policy/{slug}', [StorePageController::class, 'show'])->name('page.show');

/*
|--------------------------------------------------------------------------
| Admin routes (Phase 3) — append below; do not remove storefront routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
        Route::get('auth/{provider}/redirect', [AdminAuthController::class, 'redirectToProvider'])
            ->whereIn('provider', ['facebook', 'google'])
            ->middleware('throttle:login')
            ->name('auth.provider.redirect');
    });

    Route::middleware(['auth:admin', 'admin', 'admin.ajax'])->group(function (): void {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Admin My Account / Profile
        Route::prefix('profile')->name('profile.')->group(function (): void {
            Route::get('/', [AdminProfileController::class, 'edit'])->name('edit');
            Route::put('/', [AdminProfileController::class, 'update'])->name('update');
            Route::put('/password', [AdminProfileController::class, 'updatePassword'])->middleware('throttle:password-reset')->name('password.update');
        });

        // Pages & Policies Customization
        Route::middleware('permission:page-list|page-edit|page-create|customization.manage,admin')->prefix('pages')->name('pages.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'index'])->name('index');
            Route::get('/{page}/edit', [AdminPageController::class, 'edit'])->name('edit');
            Route::put('/{page}', [AdminPageController::class, 'update'])->name('update');
            Route::post('/{page}/toggle', [AdminPageController::class, 'toggle'])->name('toggle');
            Route::delete('/messages/{message}', [AdminPageController::class, 'destroyMessage'])->name('messages.destroy');
        });

        // Landing Pages & Funnels
        Route::middleware('permission:page-list|page-edit|page-create|customization.manage,admin')->prefix('landing-pages')->name('landing-pages.')->group(function (): void {
            Route::put('url-prefix', [AdminLandingPageController::class, 'updateUrlPrefix'])->name('url-prefix');
            Route::post('upload-media', [AdminLandingPageController::class, 'uploadMedia'])->name('upload-media');
            Route::get('product-images/{product}', [AdminLandingPageController::class, 'getProductImages'])->name('product-images');
            Route::post('{landingPage}/toggle', [AdminLandingPageController::class, 'toggle'])->name('toggle');
            Route::post('{landingPage}/duplicate', [AdminLandingPageController::class, 'duplicate'])->name('duplicate');
        });
        Route::middleware('permission:page-list|page-edit|page-create|customization.manage,admin')->group(function (): void {
            Route::resource('landing-pages', AdminLandingPageController::class)->except(['show']);
        });

        Route::post('clear-cache', [AdminDashboardController::class, 'clearCache'])
            ->middleware('permission:cache-clear|settings.manage,admin')
            ->name('clear-cache');

        // Reports & Analytics
        Route::prefix('reports')->name('reports.')->group(function (): void {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('/export', [AdminReportController::class, 'export'])->name('export');
            Route::post('/ai-insight', [AdminReportController::class, 'generateAiInsight'])->name('ai-insight');
        });

        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::post('categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
            ->name('categories.toggle');

        Route::resource('subcategories', AdminSubcategoryController::class)->except(['show']);
        Route::resource('childcategories', AdminChildcategoryController::class)->except(['show']);
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::resource('colors', AdminColorController::class)->except(['show']);
        Route::resource('sizes', AdminSizeController::class)->except(['show']);
        Route::resource('attributes', AdminAttributeController::class)->except(['show']);
        Route::post('attributes/{attribute}/values', [AdminAttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::delete('attributes/{attribute}/values/{value}', [AdminAttributeController::class, 'destroyValue'])->name('attributes.values.destroy');

        Route::middleware('permission:products.manage|product-manage|product-create|product-edit|product-delete,admin')->group(function (): void {
        Route::post('products/bulk', [AdminProductController::class, 'bulk'])->name('products.bulk');
        Route::match(['get', 'post'], 'products/export', [AdminProductImportExportController::class, 'export'])->name('products.export');
        Route::get('products/sample-template', [AdminProductImportExportController::class, 'sampleTemplate'])->name('products.sample-template');
        Route::post('products/import', [AdminProductImportExportController::class, 'import'])->name('products.import');
        Route::post('products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');
        Route::post('products/{product}/duplicate', [AdminProductController::class, 'duplicate'])->name('products.duplicate');
        Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])
            ->name('products.images.destroy');
        Route::delete('products/{product}/meta-image', [AdminProductController::class, 'destroyMetaImage'])
            ->name('products.meta-image.destroy');
        Route::delete('products/{product}/videos/{video}', [AdminProductController::class, 'destroyVideo'])
            ->name('products.videos.destroy');
        Route::post('products/generate-ai', [AdminProductAiController::class, 'generate'])
            ->name('products.generate-ai');
        Route::resource('products', AdminProductController::class)->except(['index', 'show']);
        });
        Route::middleware('permission:products.manage|product-manage|product-list,admin')->group(function (): void {
            Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        });

        Route::middleware('permission:orders.view|orders.edit|orders.delete|order-list|order-create|order-edit|order-delete|order-manage,admin')->group(function (): void {
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::match(['get', 'post'], 'orders/export', [AdminOrderImportExportController::class, 'export'])->name('orders.export');
        Route::get('orders/sample-template', [AdminOrderImportExportController::class, 'sampleTemplate'])->name('orders.sample-template');
        Route::post('orders/import', [AdminOrderImportExportController::class, 'import'])->name('orders.import');
        Route::post('orders/bulk', [AdminOrderController::class, 'bulkUpdate'])->name('orders.bulk');
        Route::get('orders/bulk-invoice', [AdminOrderController::class, 'bulkInvoice'])->name('orders.bulk-invoice');
        Route::get('orders/bulk-sticker', [AdminOrderController::class, 'bulkSticker'])->name('orders.bulk-sticker');
        Route::get('orders/products/search', [AdminOrderController::class, 'searchProducts'])->name('orders.products.search');
        Route::middleware('permission:incomplete-orders.manage|incomplete-order-list|incomplete-order-accept|incomplete-order-delete,admin')->group(function (): void {
        Route::get('incomplete-orders', [AdminIncompleteOrderController::class, 'index'])->name('incomplete-orders.index');
        Route::post('incomplete-orders/bulk', [AdminIncompleteOrderController::class, 'bulk'])->name('incomplete-orders.bulk');
        Route::post('incomplete-orders/{incompleteOrder}/accept', [AdminIncompleteOrderController::class, 'accept'])->name('incomplete-orders.accept');
        Route::post('incomplete-orders/{incompleteOrder}/fraud-check', [AdminIncompleteOrderController::class, 'fraudCheck'])->name('incomplete-orders.fraud-check');
        Route::delete('incomplete-orders/{incompleteOrder}', [AdminIncompleteOrderController::class, 'destroy'])->name('incomplete-orders.destroy');
        });
        Route::get('orders/check-ratio', [AdminOrderController::class, 'checkPhoneRatio'])->name('orders.check-ratio');
        Route::post('orders/ai-parse', [AdminOrderController::class, 'aiParseOrder'])->name('orders.ai-parse');
        Route::get('orders/create', [AdminOrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [AdminOrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/edit', [AdminOrderController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('orders/{order}/clear-cart', [AdminOrderController::class, 'clearCart'])->name('orders.clear-cart');
        Route::delete('orders/{order}', [AdminOrderController::class, 'destroy'])->middleware('permission:orders.delete|order-delete,admin')->name('orders.destroy');
        Route::post('orders/{order}/confirm', [AdminOrderController::class, 'markConfirmed'])->name('orders.confirm');
        Route::post('orders/{order}/processing', [AdminOrderController::class, 'markProcessing'])->name('orders.processing');
        Route::post('orders/{order}/hold', [AdminOrderController::class, 'markHold'])->name('orders.hold');
        Route::post('orders/{order}/no-response', [AdminOrderController::class, 'markNoResponse'])->name('orders.no-response');
        Route::post('orders/{order}/fraud-check', [AdminOrderController::class, 'fraudCheck'])->name('orders.fraud-check');
        Route::post('orders/{order}/send-to-courier', [AdminOrderController::class, 'sendToCourier'])->name('orders.send-to-courier');
        Route::get('orders/{order}/print', [AdminOrderController::class, 'invoice'])->name('orders.print');
        Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('orders/{order}/sticker', [AdminOrderController::class, 'sticker'])->name('orders.sticker');
        Route::post('orders/{order}/block-ip', [AdminOrderController::class, 'blockIp'])->name('orders.block-ip');
        });

        Route::middleware('permission:order-statuses.manage|order-status-list|order-status-edit|order-status-create,admin')->group(function (): void {
            Route::resource('order-statuses', AdminOrderStatusController::class)->except(['show', 'destroy']);
            Route::post('order-statuses/{order_status}/toggle', [AdminOrderStatusController::class, 'toggle'])
                ->name('order-statuses.toggle');
        });

        Route::middleware('permission:ip-blocks.manage|ip-block-list|ip-block-create|ip-block-delete,admin')->group(function (): void {
        Route::get('ip-blocks', [AdminIpBlockController::class, 'index'])->name('ip-blocks.index');
        Route::post('ip-blocks', [AdminIpBlockController::class, 'store'])->name('ip-blocks.store');
        Route::delete('ip-blocks/{ip_block}', [AdminIpBlockController::class, 'destroy'])->name('ip-blocks.destroy');
        });

        Route::middleware('permission:customers.view|customers.manage|customer-list,admin')->group(function (): void {
            Route::resource('customers', AdminCustomerController::class)->only(['index', 'show']);
        });
        Route::delete('customers/{customer}', [AdminCustomerController::class, 'destroy'])
            ->middleware('permission:customers.manage|customer-edit,admin')
            ->name('customers.destroy');
        Route::post('customers/{customer}/fraud-check', [AdminCustomerController::class, 'fraudCheck'])
            ->middleware('permission:customers.view|customers.manage|customer-list|fraud-check,admin')
            ->name('customers.fraud-check');

        Route::middleware('permission:users.manage,admin')->group(function (): void {
            Route::resource('users', AdminUserController::class)->except(['show']);
        });

        Route::middleware('permission:roles.manage,admin')->group(function (): void {
            Route::resource('roles', AdminRoleController::class)->except(['show']);
        });

        Route::middleware('permission:permissions.manage,admin')->group(function (): void {
            Route::resource('permissions', AdminPermissionController::class)->except(['show']);
        });

        Route::get('refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
        Route::get('refunds/create', [AdminRefundController::class, 'create'])->name('refunds.create');
        Route::post('refunds', [AdminRefundController::class, 'store'])->name('refunds.store');
        Route::get('refunds/{refund}', [AdminRefundController::class, 'show'])->name('refunds.show');
        Route::post('refunds/{refund}/approve', [AdminRefundController::class, 'approve'])->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');
        Route::post('refunds/{refund}/process', [AdminRefundController::class, 'process'])->name('refunds.process');

        Route::get('suppliers', [AdminSupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [AdminSupplierController::class, 'store'])->name('suppliers.store');
        Route::get('suppliers/{supplier}/edit', [AdminSupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [AdminSupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [AdminSupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('purchases/export', [AdminPurchaseController::class, 'export'])->name('purchases.export');
        Route::get('purchases/reports', [AdminPurchaseController::class, 'reports'])->name('purchases.reports');
        Route::post('purchases/{purchase}/pay-due', [AdminPurchaseController::class, 'payDue'])->name('purchases.pay-due');
        Route::resource('purchases', AdminPurchaseController::class)->except(['create']);

        Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::get('customization', [AdminWebsiteCustomizationController::class, 'edit'])->name('customization.edit');
        Route::put('customization', [AdminWebsiteCustomizationController::class, 'update'])->name('customization.update');
        Route::post('customization/reset', [AdminWebsiteCustomizationController::class, 'resetDefaults'])->name('customization.reset');
        Route::post('customization/upload-hero-slide', [AdminWebsiteCustomizationController::class, 'uploadHeroSlide'])->name('customization.upload-hero-slide');

        // Single Product Page Customization
        Route::get('product-page', [AdminProductPageCustomizationController::class, 'edit'])->name('product-page.edit');
        Route::put('product-page', [AdminProductPageCustomizationController::class, 'update'])->name('product-page.update');
        Route::post('product-page/reset', [AdminProductPageCustomizationController::class, 'resetDefaults'])->name('product-page.reset');

        Route::middleware('permission:media.manage|media-list|media-create|media-delete,admin')->group(function (): void {
        Route::get('media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::post('media', [AdminMediaController::class, 'store'])->name('media.store');
        Route::post('media/update', [AdminMediaController::class, 'update'])->name('media.update');
        Route::delete('media', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::post('media/bulk-delete', [AdminMediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
        Route::get('media/download', [AdminMediaController::class, 'download'])->name('media.download');
        });

        Route::post('stories/{story}/toggle', [AdminStoryController::class, 'toggle'])->name('stories.toggle');
        Route::resource('stories', AdminStoryController::class)->except(['show']);

        Route::get('cron-jobs', [AdminCronJobController::class, 'index'])->name('cron-jobs.index');
        Route::put('cron-jobs', [AdminCronJobController::class, 'update'])->name('cron-jobs.update');
        Route::post('cron-jobs/toggle', [AdminCronJobController::class, 'toggle'])->name('cron-jobs.toggle');
        Route::post('cron-jobs/run', [AdminCronJobController::class, 'runNow'])->name('cron-jobs.run');

        Route::middleware('permission:email-setting-list|settings.manage,admin')->group(function (): void {
            Route::get('email-settings', [AdminEmailSettingController::class, 'edit'])->name('email-settings.edit');
            Route::put('email-settings', [AdminEmailSettingController::class, 'update'])->name('email-settings.update');
            Route::post('email-settings/test', [AdminEmailSettingController::class, 'sendTest'])->name('email-settings.test');
            Route::delete('email-settings/reset', [AdminEmailSettingController::class, 'resetCredentials'])->name('email-settings.reset');
        });

        Route::middleware('permission:seo-manage|settings.manage,admin')->group(function (): void {
            Route::get('seo-settings', [AdminSeoSettingController::class, 'edit'])->name('seo-settings.edit');
            Route::put('seo-settings', [AdminSeoSettingController::class, 'update'])->name('seo-settings.update');
        });

        Route::middleware('permission:sitemap-manage|seo-manage|settings.manage,admin')->group(function (): void {
            Route::get('sitemap', [AdminSitemapController::class, 'edit'])->name('sitemap.edit');
            Route::post('sitemap/generate', [AdminSitemapController::class, 'generate'])->name('sitemap.generate');
        });

        Route::middleware('permission:error-log-view|settings.manage,admin')->prefix('error-log')->name('error-log.')->group(function (): void {
            Route::get('/', [AdminErrorLogController::class, 'index'])->name('index');
            Route::post('write-test', [AdminErrorLogController::class, 'writeTest'])->name('write-test');
            Route::post('create', [AdminErrorLogController::class, 'createFile'])->name('create');
            Route::post('clear', [AdminErrorLogController::class, 'clear'])->name('clear');
        });

        Route::middleware('permission:settings.manage,admin')->group(function (): void {
            Route::get('order-restrictions', [AdminOrderRestrictionSettingController::class, 'edit'])->name('order-restrictions.edit');
            Route::put('order-restrictions', [AdminOrderRestrictionSettingController::class, 'update'])->name('order-restrictions.update');

            Route::prefix('blocking-logs')->name('blocking-logs.')->group(function (): void {
                Route::get('/', [AdminBlockingLogController::class, 'index'])->name('index');
                Route::get('export', [AdminBlockingLogController::class, 'exportCsv'])->name('export');
                Route::post('clear', [AdminBlockingLogController::class, 'clear'])->name('clear');
                Route::delete('{log}', [AdminBlockingLogController::class, 'destroy'])->name('destroy');
                Route::post('block', [AdminBlockingLogController::class, 'blockEntity'])->name('block');
                Route::post('unblock', [AdminBlockingLogController::class, 'unblockEntity'])->name('unblock');
            });

            Route::prefix('system-update')->name('system-update.')->group(function (): void {
                Route::get('/', [AdminSystemUpdateController::class, 'index'])->name('index');
                Route::post('apply-zip', [AdminSystemUpdateController::class, 'applyZip'])->name('apply-zip');
                Route::post('apply-online', [AdminSystemUpdateController::class, 'applyOnline'])->name('apply-online');
                Route::post('publish-theme-css', [AdminSystemUpdateController::class, 'publishThemeCss'])->name('publish-theme-css');
            });
        });

        Route::middleware('permission:pixel-manage|settings.manage,admin')->prefix('tracking')->name('tracking.')->group(function (): void {
            Route::get('facebook', [AdminTrackingController::class, 'editFacebook'])->name('facebook.edit');
            Route::put('facebook', [AdminTrackingController::class, 'updateFacebook'])->name('facebook.update');
            Route::post('facebook/revoke', [AdminTrackingController::class, 'revokeFacebook'])->name('facebook.revoke');
            Route::get('facebook-capi', [AdminTrackingController::class, 'editFacebookCapi'])->name('facebook-capi.edit');
            Route::put('facebook-capi', [AdminTrackingController::class, 'updateFacebookCapi'])->name('facebook-capi.update');
            Route::post('facebook-capi/revoke', [AdminTrackingController::class, 'revokeFacebookCapi'])->name('facebook-capi.revoke');
            Route::get('google-tag-manager', [AdminTrackingController::class, 'editGoogle'])->name('google.edit');
            Route::put('google-tag-manager', [AdminTrackingController::class, 'updateGoogle'])->name('google.update');
            Route::post('google-tag-manager/revoke', [AdminTrackingController::class, 'revokeGoogle'])->name('google.revoke');
            Route::get('tiktok', [AdminTrackingController::class, 'editTiktok'])->name('tiktok.edit');
            Route::put('tiktok', [AdminTrackingController::class, 'updateTiktok'])->name('tiktok.update');
            Route::post('tiktok/revoke', [AdminTrackingController::class, 'revokeTiktok'])->name('tiktok.revoke');
        });

        Route::get('fraud-settings', [AdminFraudSettingController::class, 'edit'])->name('fraud-settings.edit');
        Route::put('fraud-settings', [AdminFraudSettingController::class, 'update'])->name('fraud-settings.update');

        Route::get('fraud-order-rules', [AdminFraudOrderRuleSettingController::class, 'edit'])->name('fraud-order-rules.edit');
        Route::put('fraud-order-rules', [AdminFraudOrderRuleSettingController::class, 'update'])->name('fraud-order-rules.update');

        Route::put('shipping-charges/threshold', [AdminShippingChargeController::class, 'updateThreshold'])->name('shipping-charges.threshold');
        Route::resource('shipping-charges', AdminShippingChargeController::class)->except(['show']);

        Route::middleware('permission:courier.manage|api-manage|settings.manage,admin')->group(function (): void {
            Route::get('courier-panel', [AdminCourierDashboardController::class, 'index'])->name('courier-panel.index');

            Route::prefix('courier-apis')->name('courier-apis.')->group(function (): void {
                Route::get('/', [AdminCourierApiController::class, 'index'])->name('index');
                Route::post('/', [AdminCourierApiController::class, 'store'])->name('store');
                Route::put('{courierApi}', [AdminCourierApiController::class, 'update'])->name('update');
                Route::delete('{courierApi}', [AdminCourierApiController::class, 'destroy'])->name('destroy');
                Route::post('{courierApi}/pathao/generate-token', [AdminCourierApiController::class, 'generatePathaoToken'])->name('pathao.generate-token');
            });
        });

        Route::middleware('permission:api-manage|settings.manage,admin')->prefix('api-integrations')->name('api-integrations.')->group(function (): void {
            Route::get('payment-gateway', [AdminPaymentGatewayController::class, 'edit'])->name('payment-gateway.edit');
            Route::put('payment-gateway/{provider}', [AdminPaymentGatewayController::class, 'update'])->name('payment-gateway.update');

            Route::get('sms-gateway', [AdminApiIntegrationController::class, 'editSmsGateway'])->name('sms-gateway.edit');
            Route::put('sms-gateway', [AdminApiIntegrationController::class, 'updateSmsGateway'])->name('sms-gateway.update');
            Route::post('sms-gateway/test', [AdminApiIntegrationController::class, 'testSmsGateway'])->name('sms-gateway.test');
            Route::post('sms-gateway/balance', [AdminApiIntegrationController::class, 'checkSmsGatewayBalance'])->name('sms-gateway.balance');

            Route::get('gemini-ai', [AdminApiIntegrationController::class, 'editGeminiAi'])->name('gemini-ai.edit');
            Route::put('gemini-ai', [AdminApiIntegrationController::class, 'updateGeminiAi'])->name('gemini-ai.update');

            Route::get('social-login', [AdminApiIntegrationController::class, 'editSocialLogin'])->name('social-login.edit');
            Route::put('social-login', [AdminApiIntegrationController::class, 'updateSocialLogin'])->name('social-login.update');

            Route::get('gemini-assistant', [AdminApiIntegrationController::class, 'editGeminiAssistant'])->name('gemini-assistant.edit');
            Route::put('gemini-assistant', [AdminApiIntegrationController::class, 'updateGeminiAssistant'])->name('gemini-assistant.update');
        });

        Route::middleware('permission:pixel-manage|api-manage|settings.manage,admin')->group(function (): void {
            Route::get('api-integrations/facebook-capi', [AdminApiIntegrationController::class, 'editFacebookCapi'])->name('api-integrations.facebook-capi.edit');
            Route::put('api-integrations/facebook-capi', [AdminApiIntegrationController::class, 'updateFacebookCapi'])->name('api-integrations.facebook-capi.update');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Hardened Dynamic Admin Route Group (Security Architecture)
|--------------------------------------------------------------------------
| Protected by:
| - Authentication ('auth')
| - Bank-Grade Admin Security & Role Authorization ('admin:super_admin,admin')
| - Rate Limiting / Brute-Force Throttling ('throttle:60,1')
| - Dynamic URL Prefix from config('admin.prefix')
*/
Route::prefix(config('admin.prefix', 'SecretAdminPanel'))
    ->middleware(['auth', 'admin:super_admin,admin', 'throttle:60,1'])
    ->name('secure.admin.')
    ->group(function (): void {
        Route::get('/dashboard', function () {
            return response()->json([
                'status' => 'secure',
                'message' => 'Welcome to the hardened bank-grade admin portal.',
                'user' => [
                    'id' => auth()->id(),
                    'role' => auth()->user()?->role,
                ],
            ]);
        })->name('dashboard');

        Route::get('/activity-logs', function () {
            return response()->json(
                AdminActivityLog::with('user:id,name,email')
                    ->latest()
                    ->paginate(25)
            );
        })->name('activity-logs.index');
    });
