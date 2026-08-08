<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Forms\Console\Commands\SeedDefaultFormsCommand as Seeder;
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

    public function test_label_formatting_allows_a_link_but_not_markup(): void
    {
        // Подпись — это часто не одно слово: «согласен с политикой», где
        // последние два обязаны быть ссылкой. Вписать туда HTML владелец не
        // может, поэтому есть свой маленький набор.
        $html = FormService::format('Согласен с [политикой](/privacy) и **условиями**');

        $this->assertStringContainsString('<a href="/privacy"', $html);
        $this->assertStringContainsString('<strong>условиями</strong>', $html);

        // Всё остальное — обычный текст, а не разметка.
        $this->assertStringNotContainsString(
            '<script>',
            FormService::format('Текст <script>alert(1)</script> дальше')
        );
    }

    public function test_dangerous_links_in_labels_are_dropped(): void
    {
        // Подпись пишет администратор, но нажимает ссылку ПОСЕТИТЕЛЬ: адрес со
        // схемой javascript: или data: сюда попасть не должен ни при каких
        // условиях. Текст при этом остаётся — молча съедать подпись хуже.
        foreach (['javascript:alert(1)', 'data:text/html;base64,PHM+', '//evil.example'] as $bad) {
            $html = FormService::format('Клик [сюда](' . $bad . ')');

            $this->assertStringNotContainsString('<a ', $html, 'Пропущен адрес: ' . $bad);
            $this->assertStringContainsString('сюда', $html);
        }
    }

    public function test_external_links_open_safely(): void
    {
        $html = FormService::format('[Наружу](https://example.com)');

        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function test_field_icon_is_limited_to_a_class_name(): void
    {
        // Значение уходит прямо в атрибут class: произвольная строка позволила
        // бы закрыть кавычку и дописать свой атрибут.
        $this->assertSame('fas fa-user', Form::safeIcon('fas fa-user'));
        $this->assertSame('', Form::safeIcon('fa" onload="alert(1)'));
        $this->assertSame('', Form::safeIcon('<script>'));

        $form = $this->form([
            ['type' => 'text', 'name' => 'name', 'label' => 'Имя', 'width' => 'full',
             'options' => [], 'icon' => 'fa" onload="alert(1)'],
        ]);

        $this->assertSame('', $form->normalizedFields()[0]['icon'], 'Опасная иконка дошла до отрисовки');
    }

    public function test_default_forms_cover_every_field_type(): void
    {
        // Набор форм по умолчанию — это ещё и обучение примером: открыв их,
        // владелец видит, как собирается форма нужного вида. Если очередной
        // тип поля нигде не показан, показывать его негде вовсе.
        $used = [];

        foreach (Seeder::definitions() as $definition) {
            foreach ($definition['fields'] as $field) {
                $used[$field['type']] = true;
            }
        }

        $this->assertSame(
            [],
            array_values(array_diff(Form::FIELD_TYPES, array_keys($used))),
            'Эти типы полей не показаны ни в одной форме по умолчанию'
        );
    }

    public function test_default_forms_are_seeded_and_render(): void
    {
        Seeder::seed();

        $forms = Form::all();

        $this->assertGreaterThanOrEqual(10, $forms->count(), 'Набор образцов подозрительно мал');
        $this->assertSame(
            $forms->count(),
            $forms->pluck('slug')->unique()->count(),
            'Слаги форм по умолчанию повторяются — шорткоды будут вести не туда'
        );

        $service = app(FormService::class);

        foreach ($forms as $form) {
            $html = $service->render($form);

            $this->assertStringContainsString('rf-form', $html, 'Форма «' . $form->title . '» не отрисовалась');
            $this->assertNotEmpty($service->rules($form)['rules'], 'У формы «' . $form->title . '» нет ни одного правила');
        }
    }

    public function test_field_width_is_computed_from_the_type(): void
    {
        // Ширину у владельца не спрашивают: короткий ответ встаёт в половину
        // строки, длинный — во всю. Правило одно на конструктор и на сидер.
        Seeder::seed();

        foreach (Form::all() as $form) {
            foreach ($form->normalizedFields() as $field) {
                $this->assertSame(
                    Form::widthFor($field['type']),
                    $field['width'],
                    'Ширина поля «' . $field['name'] . '» не совпадает с правилом'
                );
            }
        }
    }

    public function test_guests_cannot_reach_the_builder(): void
    {
        $this->get(route('admin.forms.index'))->assertRedirect();
    }
}
