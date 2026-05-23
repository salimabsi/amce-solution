<?php

use Illuminate\Support\Facades\Route;
use Presentation\Api\Controllers\DriverController;
use Presentation\Api\Controllers\OrderController;

Route::post('/orders/{id}/assign', [OrderController::class, 'assign']);
Route::get('/drivers/{id}/orders', [DriverController::class, 'orders']);
