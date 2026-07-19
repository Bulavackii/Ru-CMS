<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Controllers\Admin\ModuleDistributionController;

// Маршруты для децентрализованной загрузки модулей
Route::prefix('admin/modules/distribution')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        // Просмотр доступных модулей
        Route::get('/', [ModuleDistributionController::class, 'available'])
            ->name('admin.modules.distribution.available');

        // Установка из URL
        Route::post('/install-url', [ModuleDistributionController::class, 'installFromUrl'])
            ->name('admin.modules.distribution.installUrl');

        // Установка из GitHub
        Route::post('/install-github', [ModuleDistributionController::class, 'installFromGitHub'])
            ->name('admin.modules.distribution.installGithub');

        // Экспорт модуля
        Route::get('/export/{id}', [ModuleDistributionController::class, 'export'])
            ->name('admin.modules.distribution.export');

        // Проверка обновлений
        Route::get('/check-updates/{id}', [ModuleDistributionController::class, 'checkUpdates'])
            ->name('admin.modules.distribution.checkUpdates');

        // Добавить репозиторий
        Route::post('/add-repository', [ModuleDistributionController::class, 'addRepository'])
            ->name('admin.modules.distribution.addRepository');
    });
