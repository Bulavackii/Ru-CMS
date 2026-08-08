<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Controllers\Admin\FormController;
use Modules\Forms\Controllers\FormSubmissionController;
use Modules\Forms\Models\Form;

// ── Приём заявок с сайта ──────────────────────────────────────────────────
//
// Публичный маршрут: его дёргает посетитель. CSRF на месте (middleware web),
// частота ограничена в контроллере, простые боты отсеиваются полем-ловушкой.
Route::post('/form/{slug}', [FormSubmissionController::class, 'store'])
    ->middleware('web')
    ->name('forms.submit')
    ->where('slug', '[a-z0-9\-_]+');

// ── Панель ────────────────────────────────────────────────────────────────
Route::prefix('admin/forms')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.forms.')
    ->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');

        // Строго ДО /{form}: иначе «preview» уедет в привязку модели как
        // идентификатор формы и вернёт 404 вместо превью.
        Route::post('/preview', [FormController::class, 'preview'])->name('preview');

        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::put('/{form}', [FormController::class, 'update'])->name('update');
        Route::post('/{form}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');

        Route::get('/{form}/submissions', [FormController::class, 'submissions'])->name('submissions');
        Route::patch('/{form}/submissions/{submission}', [FormController::class, 'markRead'])->name('submissions.read');
        Route::delete('/{form}/submissions/{submission}', [FormController::class, 'destroySubmission'])->name('submissions.destroy');
        Route::get('/{form}/submissions/{submission}/file/{field}', [FormController::class, 'download'])
            ->name('submissions.download')
            ->where('field', '[a-z0-9_]+');
    });

// ── Хелпер для шаблонов ───────────────────────────────────────────────────

if (! function_exists('form_render')) {
    /**
     * Вывод формы по слагу.
     *
     * Несуществующая или выключенная форма НЕ роняет страницу и ничего не
     * рисует: материал с забытым шорткодом должен открываться как обычно.
     */
    function form_render(string $slug, array $options = []): string
    {
        $form = Form::findActive($slug);

        if (! $form) {
            return '';
        }

        return app(\Modules\Forms\Services\FormService::class)->render($form, $options);
    }
}
