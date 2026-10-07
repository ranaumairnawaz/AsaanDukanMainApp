<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommunityContentController;
use App\Http\Controllers\Api\MerchantAppController;

// =============================================================================
//  LEGACY – Community / Admin API (keep as-is)
// =============================================================================
Route::middleware('auth:sanctum')->prefix('community')->group(function () {
    Route::get('/{type}', [CommunityContentController::class, 'index']);
    Route::get('/{type}/{id}', [CommunityContentController::class, 'show']);
    Route::post('/{type}', [CommunityContentController::class, 'store']);
    Route::put('/{type}/{id}', [CommunityContentController::class, 'update']);
    Route::delete('/{type}/{id}', [CommunityContentController::class, 'destroy']);
    Route::patch('/{type}/{id}/publish', [CommunityContentController::class, 'togglePublish']);
});

// =============================================================================
//  MERCHANT APP API  –  /api/merchant/*
// =============================================================================
Route::prefix('merchant')->middleware([\App\Http\Middleware\CheckMaintenance::class])->group(function () {

    // -------------------------------------------------------------------------
    //  Public – no authentication required
    // -------------------------------------------------------------------------
    Route::post('/login', [MerchantAppController::class, 'login']);
    Route::post('/register', [MerchantAppController::class, 'register']);

    // WhatsApp OTP – Password Reset (no auth)
    Route::post('/password/send-otp', [MerchantAppController::class, 'sendPasswordResetOtp']);
    Route::post('/password/verify-otp', [MerchantAppController::class, 'verifyPasswordResetOtp']);
    Route::post('/password/reset', [MerchantAppController::class, 'resetPasswordWithOtp']);

    // WhatsApp OTP – Registration Verification (no auth)
    Route::post('/register/send-otp', [MerchantAppController::class, 'sendRegistrationOtp']);
    Route::post('/register/verify-otp', [MerchantAppController::class, 'verifyRegistrationOtp']);

    Route::prefix('public')->group(function () {
        Route::get('/announcements', [MerchantAppController::class, 'publicAnnouncements']);
        Route::get('/news', [MerchantAppController::class, 'publicNews']);
        Route::get('/events', [MerchantAppController::class, 'publicEvents']);
        Route::get('/community-posts', [MerchantAppController::class, 'publicCommunityPosts']);
        Route::post('/community-posts', [MerchantAppController::class, 'storeCommunityPost'])->middleware('auth:sanctum');
        Route::post('/community-posts/{id}/like', [MerchantAppController::class, 'likeCommunityPost']);
        Route::get('/discover-products', [MerchantAppController::class, 'discoverProducts']);
        Route::get('/themes', [MerchantAppController::class, 'publicThemes']);
        Route::get('/plans', [MerchantAppController::class, 'publicPlans']);
    });

    // -------------------------------------------------------------------------
    //  Protected – requires Sanctum token (Bearer)
    // -------------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [MerchantAppController::class, 'logout']);
        Route::get('/me', [MerchantAppController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [MerchantAppController::class, 'dashboard']);

        // Products
        Route::get('/products', [MerchantAppController::class, 'getProducts']);
        Route::get('/products/{product}', [MerchantAppController::class, 'showProduct']);
        Route::post('/products', [MerchantAppController::class, 'storeProduct']);
        Route::post('/products/{product}', [MerchantAppController::class, 'updateProduct']);   // _method=PUT (multipart)
        Route::delete('/products/{product}', [MerchantAppController::class, 'deleteProduct']);
        Route::patch('/products/{product}/toggle-active', [MerchantAppController::class, 'toggleProductActive']);

        // Orders
        Route::get('/orders', [MerchantAppController::class, 'getOrders']);
        Route::get('/orders/{order}', [MerchantAppController::class, 'showOrder']);
        Route::patch('/orders/{order}', [MerchantAppController::class, 'updateOrder']);

        // Customers
        Route::get('/customers', [MerchantAppController::class, 'getCustomers']);
        Route::get('/customers/{customer}', [MerchantAppController::class, 'showCustomer']);

        // Import/Export
        Route::get('/export/products', [MerchantAppController::class, 'exportProducts']);
        Route::post('/import/products', [MerchantAppController::class, 'importProducts']);

        // Coupons
        Route::get('/coupons', [MerchantAppController::class, 'getCoupons']);
        Route::post('/coupons', [MerchantAppController::class, 'storeCoupon']);
        Route::put('/coupons/{coupon}', [MerchantAppController::class, 'updateCoupon']);
        Route::delete('/coupons/{coupon}', [MerchantAppController::class, 'deleteCoupon']);

        // Theme
        Route::get('/theme', [MerchantAppController::class, 'getTheme']);
        Route::post('/theme', [MerchantAppController::class, 'updateTheme']);   // _method=PUT (multipart)

        // Sections
        Route::get('/sections', [MerchantAppController::class, 'getSections']);
        Route::post('/sections', [MerchantAppController::class, 'storeSection']);
        Route::post('/sections/{section}', [MerchantAppController::class, 'updateSection']);   // _method=PUT (multipart)
        Route::delete('/sections/{section}', [MerchantAppController::class, 'deleteSection']);
        Route::patch('/sections/{section}/toggle-active', [MerchantAppController::class, 'toggleSectionActive']);
        Route::post('/sections/reorder', [MerchantAppController::class, 'reorderSections']);

        // Settings
        Route::get('/settings', [MerchantAppController::class, 'getSettings']);
        Route::put('/settings', [MerchantAppController::class, 'updateSettings']);
        Route::post('/settings/request-unlock', [MerchantAppController::class, 'requestUnlock']);

        // Domain
        Route::get('/domain', [MerchantAppController::class, 'getDomain']);
        Route::get('/domain/check-availability', [MerchantAppController::class, 'checkSubdomainAvailability']);
        Route::post('/domain/subdomain', [MerchantAppController::class, 'applySubdomain']);
        Route::post('/domain/custom', [MerchantAppController::class, 'applyCustomDomain']);
        Route::post('/domain/payment', [MerchantAppController::class, 'submitDomainPayment']);

        // Staff
        Route::get('/staff', [MerchantAppController::class, 'getStaff']);
        Route::post('/staff', [MerchantAppController::class, 'storeStaff']);
        Route::delete('/staff/{user}', [MerchantAppController::class, 'deleteStaff']);

        // Billing
        Route::get('/billing', [MerchantAppController::class, 'getBilling']);
        Route::post('/billing/payment', [MerchantAppController::class, 'submitPlanPayment']);

        // Account Security
        Route::post('/change-password', [MerchantAppController::class, 'changePassword']);
    });
});
