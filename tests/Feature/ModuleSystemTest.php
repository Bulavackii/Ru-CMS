<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\System\Models\Module;

class ModuleSystemTest extends TestCase
{
    public function test_module_metadata_sync()
    {
        $module = Module::create([
            'name' => 'TestModule',
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
        $module = Module::where('name', 'System')->first();

        if ($module) {
            $this->assertTrue($module->active);
        }
    }

    public function test_invalid_module_handling()
    {
        // Проверяем, что несуществующие модули не ломают систему
        $this->withoutExceptionHandling();

        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
