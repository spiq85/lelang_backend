<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionBatchController;
use App\Http\Controllers\Api\BatchLotController;
use App\Http\Controllers\Api\BidSetController;
use App\Http\Controllers\Api\AdminWinnerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminBatchController;
use Illuminate\Support\Facades\Route;


// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'seller'])->group(function () {
    // CRUD PRODUCT
    Route::apiResource('seller/products', ProductController::class);
    // CRUD BATCH   
    Route::apiResource('seller/auction-batches', AuctionBatchController::class);
    Route::apiResource('seller/auction-batches.lots', BatchLotController::class)->shallow();
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // User - Self
    Route::get('user/profile', [UserController::class, 'profile']);
    Route::put('user/profile', [UserController::class, 'updateProfile']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);
    Route::get('user/auction-history', [UserController::class, 'auctionHistory']);
    Route::post('user/upload-payment-proof/{bidId}', [UserController::class, 'uploadPaymentProof']);

    // Auction Bids
    Route::post('auction-batches/{batch}/submit-bid-set', [BidSetController::class, 'submit']);
});

// Admin routes (require authentication and admin role)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Products - CRUD
    Route::apiResource('admin/products', ProductController::class);

    // Auction Batches - CRUD
    Route::apiResource('admin/auction-batches', AuctionBatchController::class);

    // Categories - CRUD
    Route::apiResource('admin/categories', CategoryController::class);

    // Users - CRUD
    Route::apiResource('admin/users', UserController::class);

    // Approve Batch
    Route::post('admin/auction-batches/{batch}/approve', [AdminBatchController::class, 'approve']);
    Route::post('admin/auction-batches/{batch}/publish', [AdminBatchController::class, 'publish']);
    Route::post('admin/auction-batches/{batch}/close', [AdminBatchController::class, 'close']);

    // Select Winner
    Route::post('admin/lots/{lotId}/select-winner', [AdminWinnerController::class,'select']);
});
