<?php

use Illuminate\Support\Facades\Route;
use Presentation\Api\Controllers\DriverController;
use Presentation\Api\Controllers\OrderController;

Route::get('/orders/pending', [OrderController::class, 'pending']);
Route::get('/orders/pending-db', [OrderController::class, 'pendingDb']); // BENCHMARK-ONLY
Route::post('/orders', [OrderController::class, 'store']);
Route::post('/orders/{id}/assign', [OrderController::class, 'assign']);
Route::get('/drivers', [DriverController::class, 'index']);
Route::get('/drivers/{id}/orders', [DriverController::class, 'orders']);
