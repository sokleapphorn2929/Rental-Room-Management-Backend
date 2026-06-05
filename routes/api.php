<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AmenityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/admins', [AuthController::class, 'index']);
Route::post('/admins', [AuthController::class, 'store']);
Route::get('/admins/{id}', [AuthController::class, 'show']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify', [AuthController::class, 'verifyLogin']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/admins/{id}', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/admins/{id}', [AuthController::class, 'destroy'])->middleware('auth:sanctum');

Route::post('/admins/request-deletion-code', [AuthController::class, 'sendDeletionCode'])->middleware('auth:sanctum');

Route::get('auth/google/url', [GoogleController::class, 'getGoogleUrl']);
Route::post('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::post('auth/google/verify', [GoogleController::class, 'verifyCode']);

// Route::post('/ai/process', [AIController::class, 'processPrompt']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/buildings')->group(function () {
        Route::get('/', [BuildingController::class, 'index']);
        Route::post('/', [BuildingController::class, 'store']);
        Route::get('/{id}', [BuildingController::class, 'show']);
        Route::put('/{id}', [BuildingController::class, 'update']);
        Route::delete('/{id}', [BuildingController::class, 'destroy']);

        Route::post('/store-ai', [BuildingController::class, 'storeFromAi']);
    });

    Route::prefix('/rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index']);
        Route::post('/', [RoomController::class, 'store']);
        Route::get('/{id}', [RoomController::class, 'show']);
        Route::put('/{id}', [RoomController::class, 'update']);
        Route::delete('/{id}', [RoomController::class, 'destroy']);

        Route::post('/store-ai', [RoomController::class, 'storeFromAi']);
    });

    Route::prefix('/maintenance')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index']);
        Route::post('/', [MaintenanceController::class, 'store']);
        Route::get('/{id}', [MaintenanceController::class, 'show']);
        Route::put('/{id}', [MaintenanceController::class, 'update']);
        Route::delete('/{id}', [MaintenanceController::class, 'destroy']);

        Route::post('/store-ai', [MaintenanceController::class, 'storeFromAi']);
    });

    Route::prefix('/amenities')->group(function () {
        Route::get('/', [AmenityController::class, 'index']);
        Route::post('/', [AmenityController::class, 'store']);
        Route::get('/{id}', [AmenityController::class, 'show']);
        Route::put('/{id}', [AmenityController::class, 'update']);
        Route::delete('/{id}', [AmenityController::class, 'destroy']);

        Route::post('/store-ai', [AmenityController::class, 'storeFromAi']);
    });

    Route::prefix('/tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{id}', [TenantController::class, 'show']);
        Route::put('/{id}', [TenantController::class, 'update']);
        Route::delete('/{id}', [TenantController::class, 'destroy']);

        Route::post('/store-ai', [TenantController::class, 'storeFromAi']);
    });

    Route::prefix('/contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/{id}', [ContractController::class, 'show']);
        Route::put('/{id}', [ContractController::class, 'update']);
        Route::delete('/{id}', [ContractController::class, 'destroy']);

        Route::post('/store-ai', [ContractController::class, 'storeFromAi']);
    });

    Route::prefix('/invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}', [InvoiceController::class, 'update']);
        Route::delete('/{id}', [InvoiceController::class, 'destroy']);

        Route::post('/store-ai', [InvoiceController::class, 'storeFromAi']);
    });

    Route::post('/ai/process', [AIController::class, 'processPrompt']);
});



// Route::prefix('/buildings')->group(function () {
//     Route::get('/', [BuildingController::class, 'index']);
//     Route::post('/', [BuildingController::class, 'store']);
// });