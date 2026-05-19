<?php

use Illuminate\Support\Facades\Route;

// Routes registered here in Phase 3
Route::get('/ping', fn () => response()->json(['status' => 'ok']));
