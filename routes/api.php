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

    // Grup Khusus Admin
    Route::middleware('role:admin')->group(function () {
        // CRUD Jenis Voucher
        Route::post('/admin/vouchers', [VoucherController::class, 'store']);
        Route::delete('/admin/vouchers/{id}', [VoucherController::class, 'destroy']);
        
        // Import Token yang tadi sudah dibuat
        Route::post('/admin/import-tokens', [ImportController::class, 'import']);
    });
    
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });
});
