<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Служебные страницы панели: сводка о системе, геолокация и отчёт об ошибке.
 */
class SystemPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_system_info_page_opens(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/system-info')
            ->assertOk()
            ->assertViewIs('admin.error.system-info')
            ->assertViewHas('groups')
            ->assertViewHas('extensions')
            ->assertSee('Информация о системе');
    }

    public function test_system_info_survives_database_without_version_function(): void
    {
        // Тесты гоняются на SQLite, где нет select version() — страница
        // обязана открыться, а не упасть.
        $this->actingAs($this->admin())->get('/admin/system-info')->assertOk();
    }

    public function test_system_info_warns_about_enabled_debug(): void
    {
        config(['app.debug' => true]);

        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/system-info')
            ->assertOk()
            ->assertSee('Отладка включена', false);
    }

    public function test_existing_storage_link_is_detected(): void
    {
        // Проверка была через is_link()/is_dir(). На Windows связь создаётся
        // junction-ом, и обе функции возвращают false при рабочей связи —
        // предупреждение висело при существующем симлинке.
        if (! file_exists(public_path('storage'))) {
            $this->markTestSkipped('В этом окружении связи public/storage нет.');
        }

        $this->actingAs($this->admin())
            ->get('/admin/system-info')
            ->assertOk()
            ->assertViewHas('storageLinked', true);
    }

    public function test_geolocation_page_marks_local_address(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/geolocation')
            ->assertOk()
            ->assertViewIs('admin.error.geolocation')
            ->assertViewHas('isLocal', true)
            // Для локального адреса внешний сервис не зовём вовсе.
            ->assertViewHas('location', null);
    }

    public function test_error_report_form_opens(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/error-report')
            ->assertOk()
            ->assertViewIs('admin.error.report-error')
            ->assertViewHas('mailReady');
    }

    public function test_error_report_is_sent(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.from.address' => 'site@example.com',
                'mail.mailers.smtp.host' => 'smtp.example.com']);

        $this->actingAs($this->admin())
            ->post('/admin/error-report', ['message' => 'Кнопка сохранения ничего не делает.'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
    }

    public function test_error_report_does_not_crash_when_mail_is_not_configured(): void
    {
        // Раньше здесь была 500: Mail бросал исключение, а его никто не ловил.
        config(['mail.default' => 'smtp', 'mail.from.address' => '', 'mail.mailers.smtp.host' => null]);

        $this->actingAs($this->admin())
            ->post('/admin/error-report', ['message' => 'Кнопка сохранения ничего не делает.'])
            ->assertSessionHasErrors('message')
            ->assertSessionMissing('success');
    }

    public function test_short_message_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/error-report', ['message' => 'ой'])
            ->assertSessionHasErrors('message');
    }

    public function test_executable_attachment_is_rejected(): void
    {
        // Расширения не проверялись вовсе — прикрепить можно было .php.
        $this->actingAs($this->admin())
            ->post('/admin/error-report', [
                'message' => 'Прикладываю файл с описанием проблемы.',
                'file' => UploadedFile::fake()->create('shell.php', 10),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_attachment_is_stored_on_private_disk(): void
    {
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
        config(['mail.default' => 'smtp', 'mail.from.address' => 'site@example.com',
                'mail.mailers.smtp.host' => 'smtp.example.com']);

        $this->actingAs($this->admin())
            ->post('/admin/error-report', [
                'message' => 'Прикладываю скриншот с ошибкой.',
                'file' => UploadedFile::fake()->image('bug.png'),
            ])
            ->assertSessionHasNoErrors();

        // Публичный диск отдаётся по прямой ссылке без авторизации —
        // вложениям там не место.
        $this->assertNotEmpty(Storage::disk('local')->files('error-attachments'));
        $this->assertEmpty(Storage::disk('public')->files('error-attachments'));
    }

    public function test_pages_are_closed_for_guests(): void
    {
        foreach (['/admin/system-info', '/admin/geolocation', '/admin/error-report'] as $url) {
            $this->get($url)->assertRedirect();
        }
    }
}
