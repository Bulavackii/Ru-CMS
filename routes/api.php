<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PageController;
use Illuminate\Support\Facades\Route;

/**
 * API v1 - Headless CMS
 * Documentation: /api/docs
 */

Route::prefix('v1')->middleware([\App\Http\Middleware\ApiRateLimit::class])->group(function () {
    // Публичные маршруты
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{id}', [PageController::class, 'show']);
    Route::get('/pages/slug/{slug}', [PageController::class, 'showBySlug']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::get('/news/slug/{slug}', [NewsController::class, 'showBySlug']);

    // Защищенные маршруты (требуют токен)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/admin/news', [NewsController::class, 'adminIndex']);
        Route::post('/admin/news', [NewsController::class, 'store']);
        Route::put('/admin/news/{id}', [NewsController::class, 'update']);
        Route::delete('/admin/news/{id}', [NewsController::class, 'destroy']);

        Route::get('/admin/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('/admin/pages', [PageController::class, 'adminIndex']);
        Route::post('/admin/pages', [PageController::class, 'store']);
        Route::put('/admin/pages/{id}', [PageController::class, 'update']);
        Route::delete('/admin/pages/{id}', [PageController::class, 'destroy']);
    });
});
