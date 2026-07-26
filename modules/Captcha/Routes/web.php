<?php

use Illuminate\Support\Facades\Route;
use Modules\Captcha\Controllers\CaptchaController;
use Modules\Captcha\Models\CaptchaPreset;

// API маршруты для каптчи
Route::prefix('api/captcha')
    ->middleware(['web'])
    ->group(function () {
        // Генерация каптчи
        Route::get('/generate/{type?}', [CaptchaController::class, 'generate'])
            ->name('api.captcha.generate')
            ->where('type', 'image|slider|math|question');

        // Проверка каптчи
        Route::post('/verify', [CaptchaController::class, 'verify'])
            ->name('api.captcha.verify');

        // Рендер для Blade
        Route::get('/render/{type?}', [CaptchaController::class, 'render'])
            ->name('api.captcha.render')
            ->where('type', 'image|slider|math|question');

        // JavaScript виджет
        Route::get('/widget', [CaptchaController::class, 'widget'])
            ->name('api.captcha.widget');
    });

// Панель. Вьюха Views/admin/index.blade.php лежала в модуле с самого начала,
// но маршрута к ней не было вовсе — открыть её было нельзя ни по ссылке, ни
// по прямому адресу. Закрыто теми же middleware, что и остальная панель.
Route::prefix('admin/captcha')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.captcha.')
    ->group(function () {
        Route::get('/', [CaptchaController::class, 'admin'])->name('index');

        // Живое превью конструктора: тот же генератор, что и на сайте,
        // поэтому в превью видно ровно то, что получит посетитель
        Route::post('/preview', [CaptchaController::class, 'preview'])->name('preview');

        Route::post('/presets', [CaptchaController::class, 'store'])->name('presets.store');
        Route::put('/presets/{preset}', [CaptchaController::class, 'update'])->name('presets.update');
        Route::post('/presets/{preset}/duplicate', [CaptchaController::class, 'duplicate'])->name('presets.duplicate');
        Route::delete('/presets/{preset}', [CaptchaController::class, 'destroy'])->name('presets.destroy');
    });

// ── Хелперы для шаблонов ──────────────────────────────────────────────────

if (!function_exists('captcha_img')) {
    /** Только сама каптча, без поля ответа. Оставлено для совместимости. */
    function captcha_img($type = 'image', $options = [])
    {
        return app('captcha')->generate($type, $options)['html'];
    }
}

if (!function_exists('captcha_js')) {
    function captcha_js($selector = '#captcha-container', $type = 'image')
    {
        return app('captcha')->renderJS($selector, $type);
    }
}

if (!function_exists('captcha_field')) {
    /**
     * Готовый блок для формы: каптча + поле ответа + скрытый идентификатор
     * экземпляра. Именно его вставляют пресеты.
     */
    function captcha_field($type = 'image', $options = [])
    {
        return app('captcha')->render($type, $options);
    }
}

if (!function_exists('captcha_preset')) {
    /**
     * Вывод сохранённой сборки по слагу.
     *
     * Несуществующий или выключенный пресет НЕ роняет страницу и ничего не
     * рисует: материал с забытым шорткодом должен открываться как обычно.
     */
    function captcha_preset(string $slug, array $override = [])
    {
        $preset = CaptchaPreset::findActive($slug);

        if (!$preset) {
            return '';
        }

        return app('captcha')->render($preset->type, array_merge((array) $preset->options, $override));
    }
}
