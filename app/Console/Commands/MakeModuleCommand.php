<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * 🛠️ Команда для генерации модуля
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Имя модуля}';
    protected $description = 'Создать новый модуль с базовой структурой';

    public function handle(): int
    {
        $name = $this->argument('name');
        $modulePath = base_path("modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Модуль {$name} уже существует!");
            return 1;
        }

        $this->info("Создание модуля {$name}...");

        // Создание структуры директорий
        $directories = [
            'Controllers/Admin',
            'Controllers/Frontend',
            'Controllers/Api',
            'Models',
            'Services',
            'Views/admin',
            'Views/frontend',
            'Routes',
            'Config',
            'Lang/ru',
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$modulePath}/{$dir}", 0755, true);
            $this->line("  ✓ Создана директория: {$dir}");
        }

        // Создание файлов
        $this->createModuleJson($modulePath, $name);
        $this->createServiceProvider($modulePath, $name);
        $this->createRoutes($modulePath, $name);
        $this->createController($modulePath, $name);
        $this->createModel($modulePath, $name);
        $this->createConfig($modulePath, $name);
        $this->createView($modulePath, $name);

        $this->info("\n✅ Модуль {$name} успешно создан!");
        $this->line("\nСледующие шаги:");
        $this->line("1. Зарегистрируйте модуль в bootstrap/app.php:");
        $this->line("   \$app->register(Modules\\{$name}\\Providers\\{$name}ServiceProvider::class);");
        $this->line("2. Создайте миграцию: php artisan make:migration create_{$this->getTableName($name)}_table (миграции всех модулей живут в единой database/migrations/)");
        $this->line("3. Настройте маршруты в modules/{$name}/Routes/web.php");

        return 0;
    }

    protected function createModuleJson(string $path, string $name): void
    {
        $content = json_encode([
            'name' => $name,
            'title' => $this->getTitle($name),
            'version' => '1.0.0',
            'active' => true,
            'priority' => 50,
            'description' => "Модуль {$name}",
            'providers' => [
                "Modules\\{$name}\\Providers\\{$name}ServiceProvider"
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        File::put("{$path}/module.json", $content);
    }

    protected function createServiceProvider(string $path, string $name): void
    {
        $content = <<<PHP
<?php

namespace Modules\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов
    }

    public function boot(): void
    {
        \$modulePath = base_path('modules/{$name}');

        // Загрузка маршрутов
        if (file_exists(\$modulePath . '/Routes/web.php')) {
            \$this->loadRoutesFrom(\$modulePath . '/Routes/web.php');
        }

        // Загрузка представлений
        if (is_dir(\$modulePath . '/Views')) {
            \$this->loadViewsFrom(\$modulePath . '/Views', '{$name}');
        }

        // Миграции модуля живут в единой database/migrations/.

        // Загрузка переводов
        if (is_dir(\$modulePath . '/Lang')) {
            \$this->loadTranslationsFrom(\$modulePath . '/Lang', '{$name}');
        }
    }
}
PHP;

        File::put("{$path}/Providers/{$name}ServiceProvider.php", $content);
    }

    protected function createRoutes(string $path, string $name): void
    {
        $lowerName = strtolower($name);
        
        $content = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{$name}\\Controllers\\Admin\\{$name}Controller;

Route::prefix('admin/{$lowerName}')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.{$lowerName}.')
    ->group(function () {
        Route::get('/', [{$name}Controller::class, 'index'])->name('index');
    });
PHP;

        File::put("{$path}/Routes/web.php", $content);
    }

    protected function createController(string $path, string $name): void
    {
        $content = <<<PHP
<?php

namespace Modules\\{$name}\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;

class {$name}Controller extends Controller
{
    public function index()
    {
        return view('{$name}::admin.index');
    }
}
PHP;

        File::put("{$path}/Controllers/Admin/{$name}Controller.php", $content);
    }

    protected function createModel(string $path, string $name): void
    {
        $modelName = Str::singular($name);
        
        $content = <<<PHP
<?php

namespace Modules\\{$name}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$modelName} extends Model
{
    protected \$fillable = [
        // Добавьте поля
    ];
}
PHP;

        File::put("{$path}/Models/{$modelName}.php", $content);
    }

    protected function createConfig(string $path, string $name): void
    {
        $lowerName = strtolower($name);
        
        $content = <<<PHP
<?php

return [
    // Настройки модуля {$name}
];
PHP;

        File::put("{$path}/Config/{$lowerName}.php", $content);
    }

    protected function createView(string $path, string $name): void
    {
        $content = <<<BLADE
@extends('layouts.admin')

@section('title', '{$this->getTitle($name)}')

@section('content')
<div class="space-y-6">
    <h1 class="text-3xl font-bold">{$this->getTitle($name)}</h1>
    <p>Содержимое модуля</p>
</div>
@endsection
BLADE;

        File::put("{$path}/Views/admin/index.blade.php", $content);
    }

    protected function getTitle(string $name): string
    {
        return Str::title(str_replace('_', ' ', Str::snake($name)));
    }

    protected function getTableName(string $name): string
    {
        return Str::plural(Str::snake($name));
    }
}

