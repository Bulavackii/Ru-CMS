<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\File;
use Tests\TestCase;

/**
 * Медиатека: постраничный вывод и удаление — по одному и пачкой.
 *
 * По этому разделу тестов не было вовсе, хотя удаление здесь необратимо и
 * трогает диск. Отдельно проверяется, что массовое удаление ведёт в контроллер
 * модуля: маршрут с тем же именем есть и в routes/web.php, где он указывает на
 * дубль в ядре — тот сносит запись и файл, но оставляет уменьшенные копии.
 */
class FilesLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeFile(string $name = 'photo.jpg'): File
    {
        Storage::fake('public');
        Storage::disk('public')->put('files/2026/08/' . $name, 'x');

        return File::create([
            'name'          => $name,
            'original_name' => $name,
            'path'          => 'files/2026/08/' . $name,
            'mime_type'     => 'image/jpeg',
            'size'          => 1,
        ]);
    }

    public function test_bulk_delete_goes_to_the_module_controller(): void
    {
        $route = \Route::getRoutes()->getByName('admin.files.bulkDelete');

        $this->assertNotNull($route, 'Маршрут массового удаления не зарегистрирован');
        $this->assertSame(
            \Modules\Files\Controllers\Admin\FileController::class . '@bulkDelete',
            $route->getAction('uses'),
            'Массовое удаление уходит в дубль контроллера в ядре'
        );
    }

    public function test_bulk_delete_removes_files_and_their_records(): void
    {
        $first = $this->makeFile('one.jpg');
        $second = $this->makeFile('two.jpg');
        $kept = $this->makeFile('three.jpg');

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.files.bulkDelete'), ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->assertJson(['success' => true, 'deleted' => 2]);

        $this->assertDatabaseMissing('files', ['id' => $first->id]);
        $this->assertDatabaseMissing('files', ['id' => $second->id]);
        $this->assertDatabaseHas('files', ['id' => $kept->id]);

        Storage::disk('public')->assertMissing($first->path);
        Storage::disk('public')->assertExists($kept->path);
    }

    public function test_bulk_delete_requires_at_least_one_existing_file(): void
    {
        // Пустой список и выдуманные номера не должны доходить до диска.
        $admin = $this->admin();

        $this->actingAs($admin)
            ->deleteJson(route('admin.files.bulkDelete'), ['ids' => []])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->deleteJson(route('admin.files.bulkDelete'), ['ids' => [999999]])
            ->assertStatus(422);
    }

    public function test_page_size_is_limited_to_known_values(): void
    {
        // Произвольное число из адреса позволило бы вытащить всю библиотеку
        // одним запросом — размер страницы зажат в известные варианты.
        $this->actingAs($this->admin())
            ->get(route('admin.files.index', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('files', fn ($files) => $files->perPage() === 24);

        $this->actingAs($this->admin())
            ->get(route('admin.files.index', ['per_page' => 48]))
            ->assertOk()
            ->assertViewHas('files', fn ($files) => $files->perPage() === 48);
    }
}
