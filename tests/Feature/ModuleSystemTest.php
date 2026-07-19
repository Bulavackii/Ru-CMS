<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\System\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_metadata_sync()
    {
        // syncModuleMetadata() сопоставляет строку в БД с реальной папкой
        // modules/<name>/module.json — придуманное имя без папки на диске
        // (а) никогда не получит title из sync и (б) активные-но-без-папки
        // модули loadActiveModules() удаляет как "осиротевшие". Используем
        // реально существующий модуль (Categories) без title, чтобы
        // проверить именно синхронизацию метаданных.
        $module = Module::create([
            'name' => 'Categories',
            'version' => '1.0.0',
            'active' => true,
        ]);

        // Проверяем, что метаданные синхронизируются
        $this->app->getProvider(\App\Providers\ModuleServiceProvider::class)->boot();

        $module->refresh();
        $this->assertNotNull($module->title);
    }

    public function test_module_loading()
    {
        // RefreshDatabase не сидирует таблицу modules, поэтому строку для
        // 'System' создаём явно, иначе тест не выполняет ни одной проверки.
        Module::create([
            'name' => 'System',
            'version' => '1.0.0',
            'active' => true,
        ]);

        $this->app->getProvider(\App\Providers\ModuleServiceProvider::class)->boot();

        // modules/System реально существует на диске, так что
        // loadActiveModules() не должен удалить эту строку как "осиротевшую".
        $module = Module::where('name', 'System')->first();
        $this->assertNotNull($module);
        $this->assertTrue($module->active);
    }

    public function test_invalid_module_handling()
    {
        // Проверяем, что несуществующие модули не ломают систему
        $this->withoutExceptionHandling();

        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
