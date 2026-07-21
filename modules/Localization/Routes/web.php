<?php

use Illuminate\Support\Facades\Route;
use Modules\Localization\Controllers\Admin\LocalizationController as AdminController;
use Modules\Localization\Controllers\Admin\TranslationController;
use Modules\Localization\Controllers\Frontend\LocalizationController as FrontendController;

/*
|--------------------------------------------------------------------------
| 🌍 Маршруты модуля Localization
|--------------------------------------------------------------------------
|
| Админские маршруты: /admin/localization/*
| Фронтенд маршруты: /localization/*
|
*/

// 🛡️ Админские маршруты (только для администраторов)
Route::prefix('admin')->middleware(['web', 'admin'])->group(function () {
    Route::prefix('localization')->group(function () {
        // 📊 Главная страница
        Route::get('/', [AdminController::class, 'index'])->name('admin.localization.index');

        // 📝 Создание и редактирование стран
        Route::get('/create', [AdminController::class, 'create'])->name('admin.localization.create');
        Route::post('/store', [AdminController::class, 'store'])->name('admin.localization.store');
        Route::get('/edit/{code}', [AdminController::class, 'edit'])->name('admin.localization.edit');
        Route::put('/update/{code}', [AdminController::class, 'update'])->name('admin.localization.update');
        Route::delete('/destroy/{code}', [AdminController::class, 'destroy'])->name('admin.localization.destroy');

        // ⚙️ Управление настройками
        Route::get('/settings/{code}', [AdminController::class, 'settings'])->name('admin.localization.settings');
        Route::post('/settings/{code}/save', [AdminController::class, 'saveSetting'])->name('admin.localization.settings.save');
        Route::delete('/settings/{code}/delete', [AdminController::class, 'deleteSetting'])->name('admin.localization.settings.delete');

        // 📝 Графический редактор переводов (resources/lang/<locale>/*.php)
        Route::prefix('translations')->name('admin.localization.translations.')->group(function () {
            Route::get('/', [TranslationController::class, 'index'])->name('index');
            Route::post('/', [TranslationController::class, 'store'])->name('store');
            Route::get('/{locale}/{group?}', [TranslationController::class, 'edit'])->name('edit');
            Route::put('/{locale}/{group}', [TranslationController::class, 'update'])->name('update');
            Route::delete('/{locale}', [TranslationController::class, 'destroy'])->name('destroy');
        });

        // 📋 Импорт и утилиты
        Route::post('/import-presets', [AdminController::class, 'importPresets'])->name('admin.localization.import.presets');
        Route::post('/clear-cache', [AdminController::class, 'clearCache'])->name('admin.localization.clear.cache');

        // 📊 API для админки
        Route::get('/api/stats', [AdminController::class, 'stats'])->name('admin.localization.api.stats');
        Route::get('/api/countries', [AdminController::class, 'countries'])->name('admin.localization.api.countries');
        Route::get('/api/{code}/settings', [AdminController::class, 'countrySettings'])->name('admin.localization.api.country.settings');
    });
});

// 🌐 Фронтенд маршруты (публичные API)
Route::prefix('localization')->middleware(['web'])->group(function () {
    // 📊 Основные данные
    Route::get('/current', [FrontendController::class, 'current'])->name('localization.current');
    Route::get('/countries', [FrontendController::class, 'countries'])->name('localization.countries');
    Route::get('/frontend-data', [FrontendController::class, 'frontendData'])->name('localization.frontend.data');

    // 💰 Форматирование
    Route::post('/format/currency', [FrontendController::class, 'formatCurrency'])->name('localization.format.currency');
    Route::post('/format/date', [FrontendController::class, 'formatDate'])->name('localization.format.date');
    Route::post('/format/time', [FrontendController::class, 'formatTime'])->name('localization.format.time');
    Route::post('/format/datetime', [FrontendController::class, 'formatDateTime'])->name('localization.format.datetime');
    Route::post('/format/number', [FrontendController::class, 'formatNumber'])->name('localization.format.number');

    // 📝 Переводы и настройки
    Route::get('/translate', [FrontendController::class, 'translate'])->name('localization.translate');
    Route::get('/setting', [FrontendController::class, 'setting'])->name('localization.setting');

    // ⏰ Управление часовым поясом
    Route::post('/set-timezone', [FrontendController::class, 'setTimezone'])->name('localization.set.timezone');
    
    // 🔄 Переключение страны
    Route::post('/switch', [FrontendController::class, 'switchCountry'])->name('localization.switch');
});

// 🔄 Алиасы для удобства (без префикса)
Route::get('/localize/currency', [FrontendController::class, 'formatCurrency']);
Route::get('/localize/date', [FrontendController::class, 'formatDate']);
Route::get('/localize/time', [FrontendController::class, 'formatTime']);
Route::get('/localize/datetime', [FrontendController::class, 'formatDateTime']);
Route::get('/localize/number', [FrontendController::class, 'formatNumber']);
Route::get('/localize/translate', [FrontendController::class, 'translate']);
