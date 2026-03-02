<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionBatchController;
use App\Http\Controllers\Api\BatchLotController;
use App\Http\Controllers\Api\BidSetController;
use App\Http\Controllers\Api\AdminWinnerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminBatchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SellerDashboardController;

// =======================
// Public Routes
// =======================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Forgot & Reset Password
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);

// =====================
// Home Routes
// =====================
Route::get('/home/closed', [HomeController::class, 'closed']);
Route::get('/home/trending', [HomeController::class, 'trending']);


// =======================
// Banner Routes
// =====================
Route::get('/banners', [BannerController::class, 'index']);

// =======================
// Public Auction Routes (tidak perlu auth)
// =======================
Route::get('auction-batches', [AuctionBatchController::class, 'index']);
Route::get('auction-batches/{batch}', [AuctionBatchController::class, 'show']);


// =======================
// Seller Routes
// =======================
Route::middleware(['auth:sanctum', 'seller'])->group(function () {
    // CRUD Produk (legacy)
    Route::apiResource('seller/products', ProductController::class);

    // CRUD Batch & Lot (legacy)
    Route::apiResource('seller/auction-batches', AuctionBatchController::class);
    Route::apiResource('seller/auction-batches.lots', BatchLotController::class)->shallow();

    // Seller Dashboard API
    Route::get('seller/dashboard/stats', [SellerDashboardController::class, 'stats']);
    Route::get('seller/dashboard/products', [SellerDashboardController::class, 'products']);
    Route::get('seller/dashboard/batches', [SellerDashboardController::class, 'batches']);
    Route::get('seller/dashboard/batches/{id}', [SellerDashboardController::class, 'showBatch']);
    Route::post('seller/dashboard/products', [SellerDashboardController::class, 'storeProduct']);
    Route::put('seller/dashboard/products/{id}', [SellerDashboardController::class, 'updateProduct']);
    Route::delete('seller/dashboard/products/{id}', [SellerDashboardController::class, 'deleteProduct']);
    Route::post('seller/dashboard/batches', [SellerDashboardController::class, 'storeBatch']);
    Route::put('seller/dashboard/batches/{id}', [SellerDashboardController::class, 'updateBatch']);
    Route::delete('seller/dashboard/batches/{id}', [SellerDashboardController::class, 'deleteBatch']);
    Route::post('seller/dashboard/batches/{id}/submit-review', [SellerDashboardController::class, 'submitForReview']);

    // Lot management within batches
    Route::post('seller/dashboard/batches/{batchId}/lots', [SellerDashboardController::class, 'storeLot']);
    Route::put('seller/dashboard/batches/{batchId}/lots/{lotId}', [SellerDashboardController::class, 'updateLot']);
    Route::delete('seller/dashboard/batches/{batchId}/lots/{lotId}', [SellerDashboardController::class, 'deleteLot']);
});

// =======================
// User Routes (Authenticated)
// =======================
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Profile & History
    Route::get('user/profile', [UserController::class, 'profile']);
    Route::put('user/profile', [UserController::class, 'updateProfile']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);
    Route::get('user/profile-stats', [UserController::class, 'profileStats']);
    Route::get('user/auction-history', [UserController::class, 'auctionHistory']);
    Route::post('user/upload-payment-proof/{bidId}', [UserController::class, 'uploadPaymentProof']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);

    // Products
    Route::get('products/live', [ProductController::class, 'live']);
    Route::get('products/listing', [ProductController::class, 'listing']);
    Route::get('products/{product}/detail', [ProductController::class, 'detail']);

    // =======================
    // Auction Bidding (requires auth)
    // =======================
    // Submit bid untuk seluruh batch (opsional)
    Route::post('auction-batches/{batch}/submit-bid-set', [BidSetController::class, 'submit']);

    // Submit bid untuk satu lot
    Route::post('auction-batches/{batchId}/lots/{lotId}/submit-bid', [BidSetController::class, 'submitPerLot']);

    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::get('me/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('me/notifications/{id}/mark-read', [NotificationController::class, 'markRead']);
    Route::post('me/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
});

// =======================
// Admin Routes
// =======================
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // CRUD Produk
    Route::apiResource('admin/products', ProductController::class);

    // CRUD Batch
    Route::apiResource('admin/auction-batches', AuctionBatchController::class);

    // CRUD Kategori
    Route::apiResource('admin/categories', CategoryController::class);

    // CRUD Users
    Route::apiResource('admin/users', UserController::class);

    // Kelola Status Batch
    Route::post('admin/auction-batches/{batch}/approve', [AdminBatchController::class, 'approve']);
    Route::post('admin/auction-batches/{batch}/publish', [AdminBatchController::class, 'publish']);
    Route::post('admin/auction-batches/{batch}/close', [AdminBatchController::class, 'close']);

    // Pilih pemenang lot
    Route::post('admin/lots/{lotId}/select-winner', [AdminWinnerController::class, 'select']);
});
