<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\PlanController;

use Illuminate\Support\Facades\Artisan;

// ---------- Web Cron ----------
Route::get('/clear-cache-123', function () {
    Artisan::call('optimize:clear');
    return 'Cache cleared successfully! The new storage configuration is now active.';
});

Route::get('/cron/run-secret-scheduler-9821', function () {
    Artisan::call('schedule:run');
    $schedulerOutput = trim(Artisan::output());

    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--timeout' => 60,
        '--tries' => 3,
    ]);
    $queueOutput = trim(Artisan::output());

    return response()->json([
        'status' => 'success',
        'message' => 'Scheduler and queue processed successfully.',
        'scheduler_output' => $schedulerOutput ?: 'No scheduled tasks due.',
        'queue_output' => $queueOutput ?: 'Queue was empty.',
    ]);
});

// ---------- Public Landing Page (Restricted to Main Domain) ----------
$appUrl = config('app.url', 'http://localhost');
if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
    $appUrl = 'http://' . $appUrl;
}
$baseDomain = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';

Route::domain($baseDomain)->group(function () {
    Route::get('/', function () {
        $plans = \App\Models\SubscriptionPlan::with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = \App\Models\GlobalSetting::all()->keyBy('key')->map(fn($s) => $s->value);
        $themes = \App\Models\Theme::where('is_active', true)->get();

        return inertia('Landing', [
            'plans' => $plans,
            'settings' => $settings,
            'themes' => $themes,
        ]);
    })->name('home');

    Route::get('/api/check-slug', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'checkSlug'])->name('api.check-slug');
});

// ---------- Merchant Storefront Routes ----------
$storefrontRoutes = function () {
    Route::get('/', [\App\Http\Controllers\Storefront\StoreController::class, 'index'])->name('store.home');
    Route::get('/product/{product}', [\App\Http\Controllers\Storefront\StoreController::class, 'product'])->name('store.product');
    Route::get('/cart', [\App\Http\Controllers\Storefront\CartController::class, 'index'])->name('store.cart');
    Route::post('/cart', [\App\Http\Controllers\Storefront\CartController::class, 'add'])->name('store.cart.add');
    Route::delete('/cart/{key}', [\App\Http\Controllers\Storefront\CartController::class, 'remove'])->name('store.cart.remove');
    Route::post('/cart/coupon', [\App\Http\Controllers\Storefront\CartController::class, 'applyCoupon'])->name('store.cart.coupon');
    Route::delete('/cart/coupon/remove', [\App\Http\Controllers\Storefront\CartController::class, 'removeCoupon'])->name('store.cart.coupon.remove');
    Route::get('/checkout', [\App\Http\Controllers\Storefront\CheckoutController::class, 'index'])->name('store.checkout');
    Route::post('/checkout', [\App\Http\Controllers\Storefront\CheckoutController::class, 'store'])->name('store.checkout.submit');
    Route::get('/order/{order_number}', [\App\Http\Controllers\Storefront\CheckoutController::class, 'confirmation'])->name('store.order.confirmation');
    Route::get('/track', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'index'])->name('store.track');
    Route::post('/track', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'track'])->name('store.track.search');
};

// 1. Custom Domain Storefront (Fallback logic is in middleware)
Route::domain('{tenant_domain}')
    ->middleware(['tenant.required', 'store.expiry'])
    ->group($storefrontRoutes);

// 2. Path-based Storefront
Route::prefix('store/{store_slug}')
    ->middleware(['tenant.required', 'store.expiry'])
    ->group($storefrontRoutes);

// ---------- Merchant Dashboard ----------
// The web merchant dashboard is disabled. Use the React Merchant App (API).

// ---------- Super Admin Panel ----------
Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        // Tenants
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/subdomains', [TenantController::class, 'subdomainQueue'])->name('tenants.subdomains');
        Route::post('tenants/{tenant}/subdomain/approve', [TenantController::class, 'approveSubdomain'])->name('tenants.subdomain.approve');
        Route::post('tenants/{tenant}/subdomain/reject', [TenantController::class, 'rejectSubdomain'])->name('tenants.subdomain.reject');

        // Community Content
        Route::get('community', [\App\Http\Controllers\CommunityContentController::class, 'adminIndex'])->name('community.index');
        Route::post('community/{type}', [\App\Http\Controllers\CommunityContentController::class, 'store'])->name('community.store');
        Route::post('community/{type}/{id}', [\App\Http\Controllers\CommunityContentController::class, 'update'])->name('community.update');
        Route::delete('community/{type}/{id}', [\App\Http\Controllers\CommunityContentController::class, 'destroy'])->name('community.destroy');
        Route::post('community/{type}/{id}/publish', [\App\Http\Controllers\CommunityContentController::class, 'togglePublish'])->name('community.togglePublish');

        Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');

        // Domains
        Route::get('domains', [\App\Http\Controllers\Admin\DomainRequestController::class, 'index'])->name('domains.index');
        Route::post('domains/{domainRequest}/quote', [\App\Http\Controllers\Admin\DomainRequestController::class, 'setQuote'])->name('domains.quote');
        Route::post('domains/{domainRequest}/approve', [\App\Http\Controllers\Admin\DomainRequestController::class, 'approve'])->name('domains.approve');
        Route::post('domains/{domainRequest}/reject', [\App\Http\Controllers\Admin\DomainRequestController::class, 'reject'])->name('domains.reject');

        // Subscription Plans
        Route::resource('plans', PlanController::class);

        // Themes
        Route::resource('themes', \App\Http\Controllers\Admin\ThemeController::class)->except(['create', 'show', 'edit']);

        // Settings
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

        // Payments
        Route::get('payments', [\App\Http\Controllers\Admin\PaymentApprovalController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/approve', [\App\Http\Controllers\Admin\PaymentApprovalController::class, 'approve'])->name('payments.approve');
        Route::post('payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentApprovalController::class, 'reject'])->name('payments.reject');

        // Subscriptions
        Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{tenant}/extend', [\App\Http\Controllers\Admin\SubscriptionController::class, 'extend'])->name('subscriptions.extend');

        // Impersonation
        Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::post('impersonate/leave', [TenantController::class, 'leaveImpersonation'])->name('impersonate.leave');

        // Admin Coupons
        Route::resource('coupons', \App\Http\Controllers\Admin\AdminCouponController::class);

        // Unlock Payment Reviews
        Route::get('unlock-payments', [\App\Http\Controllers\Admin\UnlockPaymentController::class, 'index'])->name('unlock_payments.index');
        Route::post('unlock-payments/{payment}/approve', [\App\Http\Controllers\Admin\UnlockPaymentController::class, 'approve'])->name('unlock_payments.approve');
        Route::post('unlock-payments/{payment}/reject', [\App\Http\Controllers\Admin\UnlockPaymentController::class, 'reject'])->name('unlock_payments.reject');

        // WhatsApp Broadcast
        Route::get('broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('broadcast.index');
        Route::post('broadcast/send', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])->name('broadcast.send');

        // User (Tenant) Management – change password, block user
        Route::post('tenants/{tenant}/change-password', [TenantController::class, 'changeOwnerPassword'])->name('tenants.change_password');
        Route::post('tenants/{tenant}/block', [TenantController::class, 'blockOwner'])->name('tenants.block_owner');
        Route::post('tenants/{tenant}/unblock', [TenantController::class, 'unblockOwner'])->name('tenants.unblock_owner');

        // Plan Gifting
        Route::post('tenants/{tenant}/gift-plan', [TenantController::class, 'giftPlan'])->name('tenants.gift_plan');
    });


// ---------- Auth Routes (Breeze) ----------
require __DIR__ . '/auth.php';
