<?php

namespace Modules\Forms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Forms\Services\FormService;

/**
 * 📦 Провайдер модуля «Формы».
 *
 * Конфиг мёржится обязательно: без mergeConfigFrom() config('forms.*') молча
 * возвращает null, и белый список расширений для вложений превращается в
 * пустой — то есть приложить к заявке нельзя было бы ничего. Тот же баг уже
 * ловили у модулей Каптча и Локализация.
 */
class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/forms.php', 'forms');

        $this->app->singleton(FormService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(base_path('modules/Forms/Views'), 'Forms');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Forms\Console\Commands\SeedDefaultFormsCommand::class,
                \Modules\Forms\Console\Commands\PruneSubmissionsCommand::class,
            ]);
        }
    }
}
