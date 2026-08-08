<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormService;
use Tests\TestCase;

/**
 * Модуль «Формы»: приём заявок и конструктор.
 *
 * Маршрут приёма публичный — его дёргает кто угодно из интернета, поэтому
 * проверки здесь не про удобство, а про то, что нельзя обойти правила формы,
 * подсунуть чужой адрес возврата или залить исполняемый файл.
 */
class FormsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function form(array $fields = null, array $settings = []): Form
    {
        return Form::create([
            'title'     => 'Обратная связь',
            'slug'      => 'svyaz',
            'is_active' => true,
            'fields'    => $fields ?? [
                ['type' => 'text',  'name' => 'name',  'label' => 'Имя',     'required' => true,  'width' => 'half', 'options' => []],
                ['type' => 'email', 'name' => 'email', 'label' => 'Почта',   'required' => true,  'width' => 'half', 'options' => []],
                ['type' => 'text',  'name' => 'note',  'label' => 'Заметка', 'required' => false, 'width' => 'full', 'options' => []],
            ],
            'settings'  => $settings,
        ]);
    }

    public function test_visitor_submission_is_stored(): void
    {
        $form = $this->form();

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['name' => 'Иван', 'email' => 'ivan@example.com', 'note' => 'Здравствуйте'],
            '_return' => url('/'),
        ])->assertRedirect()->assertSessionHas('form_sent', $form->slug);

        $submission = FormSubmission::first();

        $this->assertNotNull($submission, 'Заявка не сохранилась');
        $this->assertSame('Иван', $submission->data['name']);
        $this->assertSame(1, $form->fresh()->submissions_count);
    }

    public function test_required_fields_are_enforced_on_the_server(): void
    {
        // Обязательность живёт в описании формы, а не в разметке: в браузере
        // атрибут required убирается за секунду.
        $form = $this->form();

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['name' => '', 'email' => 'не почта'],
            '_return' => url('/'),
        ])->assertRedirect();

        $this->assertSame(0, FormSubmission::count(), 'Пустая заявка сохранилась');
        $this->assertSame(0, $form->fresh()->submissions_count);
    }

    public function test_select_accepts_only_offered_options(): void
    {
        // Иначе в заявку приедет что угодно, включая разметку.
        $form = $this->form([
            ['type' => 'select', 'name' => 'svc', 'label' => 'Услуга', 'required' => true,
             'width' => 'full', 'options' => ['Консультация', 'Расчёт']],
        ]);

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['svc' => '<script>alert(1)</script>'],
            '_return' => url('/'),
        ])->assertRedirect();

        $this->assertSame(0, FormSubmission::count());

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['svc' => 'Расчёт'],
            '_return' => url('/'),
        ])->assertRedirect();

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_honeypot_is_rejected_silently(): void
    {
        // Ответ как при успехе: сообщать боту, что его узнали, — значит
        // помочь обойти проверку в следующий раз.
        $form = $this->form();

        $this->post(route('forms.submit', $form->slug), [
            'fields'                 => ['name' => 'Бот', 'email' => 'bot@example.com'],
            FormService::HONEYPOT    => 'https://spam.example',
            '_return'                => url('/'),
        ])->assertRedirect()->assertSessionHas('form_sent', $form->slug);

        $this->assertSame(0, FormSubmission::count(), 'Заявка от бота сохранилась');
    }

    public function test_return_url_cannot_point_to_another_site(): void
    {
        // Адрес возврата приезжает полем формы: без проверки форма стала бы
        // открытым перенаправлением на чужой сайт.
        $form = $this->form();

        $response = $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['name' => 'Иван', 'email' => 'ivan@example.com'],
            '_return' => 'https://evil.example/phish',
        ]);

        $this->assertStringNotContainsString(
            'evil.example',
            (string) $response->headers->get('Location'),
            'Форма перенаправляет на чужой сайт'
        );
    }

    public function test_disabled_form_is_not_accessible(): void
    {
        $form = $this->form();
        $form->update(['is_active' => false]);

        $this->post(route('forms.submit', $form->slug), [
            'fields' => ['name' => 'Иван', 'email' => 'ivan@example.com'],
        ])->assertNotFound();
    }

    public function test_shortcode_renders_the_form_and_survives_a_missing_one(): void
    {
        $form = $this->form();

        $html = render_shortcodes('<p>До</p>[form slug="' . $form->slug . '"]<p>После</p>');

        $this->assertStringContainsString('rf-form', $html, 'Шорткод не раскрылся');
        $this->assertStringContainsString('До', $html);

        // Материал со ссылкой на удалённую форму обязан открываться как обычно.
        $this->assertStringNotContainsString(
            '[form',
            render_shortcodes('[form slug="net-takoy-formy"]'),
            'Шорткод несуществующей формы остался в тексте'
        );
    }

    public function test_executable_attachments_are_rejected(): void
    {
        Storage::fake('local');

        $form = $this->form([
            ['type' => 'file', 'name' => 'doc', 'label' => 'Документ', 'required' => true,
             'width' => 'full', 'options' => []],
        ]);

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['doc' => UploadedFile::fake()->create('shell.php', 10)],
            '_return' => url('/'),
        ])->assertRedirect();

        $this->assertSame(0, FormSubmission::count(), 'Принят исполняемый файл');
    }

    public function test_attachments_are_stored_outside_public_storage(): void
    {
        // В заявке может приехать паспорт или договор: всё, что попадает в
        // storage/app/public, доступно по прямой ссылке без авторизации.
        Storage::fake('local');
        Storage::fake('public');

        $form = $this->form([
            ['type' => 'file', 'name' => 'doc', 'label' => 'Документ', 'required' => true,
             'width' => 'full', 'options' => []],
        ]);

        $this->post(route('forms.submit', $form->slug), [
            'fields'  => ['doc' => UploadedFile::fake()->create('dogovor.pdf', 20, 'application/pdf')],
            '_return' => url('/'),
        ])->assertRedirect();

        $submission = FormSubmission::first();

        $this->assertNotNull($submission);
        $this->assertIsArray($submission->data['doc']);
        Storage::disk('local')->assertExists($submission->data['doc']['path']);
        $this->assertSame([], Storage::disk('public')->allFiles(), 'Вложение попало в публичное хранилище');
    }

    public function test_builder_page_opens_and_names_are_made_safe(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.forms.index'))
            ->assertOk()
            ->assertViewIs('Forms::admin.index');

        // Имя поля становится ключом массива запроса, поэтому кириллица и
        // пробелы приводятся к латинице, а совпадения разводятся.
        $this->actingAs($this->admin())->post(route('admin.forms.store'), [
            'title'  => 'Анкета',
            'fields' => [
                ['type' => 'text', 'label' => 'Ваше имя', 'name' => 'Ваше имя'],
                ['type' => 'text', 'label' => 'Ваше имя', 'name' => 'Ваше имя'],
            ],
        ])->assertRedirect();

        $names = array_column(Form::where('title', 'Анкета')->first()->fields, 'name');

        $this->assertSame(['vase_imia', 'vase_imia_2'], $names);
    }

    public function test_submissions_of_another_form_are_not_reachable(): void
    {
        $first = $this->form();
        $second = Form::create(['title' => 'Вторая', 'slug' => 'vtoraya', 'fields' => [], 'is_active' => true]);

        $submission = FormSubmission::create([
            'form_id' => $first->id,
            'data'    => ['name' => 'Иван'],
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.forms.submissions.destroy', [$second, $submission]))
            ->assertNotFound();

        $this->assertDatabaseHas('form_submissions', ['id' => $submission->id]);
    }

    public function test_guests_cannot_reach_the_builder(): void
    {
        $this->get(route('admin.forms.index'))->assertRedirect();
    }
}
