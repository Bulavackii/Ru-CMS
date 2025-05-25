<?php

use App\Http\Controllers\Api\JwtAuthController;
use Illuminate\Support\Facades\Route;

Route::post('login', [JwtAuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [JwtAuthController::class, 'me']);
    Route::post('logout', [JwtAuthController::class, 'logout']);
});

