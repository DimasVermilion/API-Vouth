<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\ImportController;
use App\Http\Controllers\Api\Admin\VoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    
    // Route yang bisa diakses Admin & Siswa (Melihat daftar voucher)
    Route::get('/vouchers', [VoucherController::class, 'index']);

    // Route Admin
   Route::middleware('role:admin')->prefix('admin')->group(function () {
        // CRUD Kategori Voucher
        Route::post('/vouchers', [App\Http\Controllers\Api\Admin\VoucherController::class, 'store']);
        Route::delete('/vouchers/{id}', [App\Http\Controllers\Api\Admin\VoucherController::class, 'destroy']);
        
        // Token
        Route::post('/tokens/import', [App\Http\Controllers\Api\Admin\VoucherTokenController::class, 'importToken']);
        Route::get('/tokens', [App\Http\Controllers\Api\Admin\VoucherTokenController::class, 'index']);
    });

    Route::prefix('user')->group(function () {
        Route::get('/vouchers', [App\Http\Controllers\Api\User\ProductController::class, 'index']);
        Route::get('/my-vouchers', [App\Http\Controllers\Api\Payment\PaymentController::class, 'myVouchers']);
    });
    
    // Payment Routes
     Route::middleware('role:siswa')->group(function () {
    Route::post('/payment/checkout', [App\Http\Controllers\Api\Payment\PaymentController::class, 'createInvoice']);
    Route::get('/payment/check/{external_id}', [App\Http\Controllers\Api\Payment\PaymentController::class, 'checkStatus']);
    Route::get('/purchase-history', [App\Http\Controllers\Api\Payment\PaymentController::class, 'purchaseHistory']);
     });


    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });
});
