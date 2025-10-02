<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionBatchController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'seller'])->group(function () {
    Route::apiResource('seller/products', ProductController::class);


    Route::apiResource('seller/auction-batches', AuctionBatchController::class);
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
    Route::post('auction-batches/{id}/bid', [AuctionBatchController::class, 'placeBid']);
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

    // Bids - CRUD
    Route::apiResource('admin/bids', BidController::class);
    Route::get('admin/batches/{batchId}/highest-bid', [BidController::class, 'highestBid']);
    Route::put('admin/bids/{id}/mark-winner', [BidController::class, 'markAsWinner']);
});
