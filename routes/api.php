<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\GatewayController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/purchase', [TransactionController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    
    // Client routes
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    
    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    
    // Admin/Manager/Finance protected routes
    Route::middleware('role:manager')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
    
    // Finance protected routes
    Route::middleware('role:finance')->group(function () {
        Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    });
    
    // Admin protected routes
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        
        // Gateway management
        Route::get('/gateways', [GatewayController::class, 'index']);
        Route::get('/gateways/{gateway}', [GatewayController::class, 'show']);
        Route::put('/gateways/{gateway}/status', [GatewayController::class, 'updateStatus']);
        Route::put('/gateways/{gateway}/priority', [GatewayController::class, 'updatePriority']);
    });
});
