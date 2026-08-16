<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Modules\Captcha\Console\Commands\SeedDefaultCaptchaPresetsCommand as CaptchaSeeder;
use Modules\Captcha\Models\CaptchaPreset;
use Modules\Captcha\Services\CaptchaService;
use Tests\TestCase;

/**
 * 🛡️ Конструктор каптчи: сборки, вставка в материалы, проверка ответа.
 *
 * Что здесь закреплено — всё это было сломано до 26.07.2026:
 *
 * — ДВЕ КАПТЧИ НА СТРАНИЦЕ ЗАТИРАЛИ ДРУГ ДРУГА. Код лежал в одной ячейке
 *   сессии (captcha_code), поэтому вторая перезаписывала первую и проверка
 *   первой формы проваливалась всегда. Это главный тест файла.
 * — ОТВЕТ ОТДАВАЛСЯ КЛИЕНТОМ В РАЗМЕТКЕ: generateMath() выводил
 *   <input type="hidden" name="captcha_math_answer" value="42">, слайдер —
 *   свою позицию. Каптчу можно было пройти, открыв исходный код страницы.
 * — СЛАЙДЕР НЕ ПРОХОДИЛСЯ В ПРИНЦИПЕ: render() слал строку 'slider',
 *   verify() делал (int) от неё, получал 0, и попадание не засчитывалось
 *   никогда. Обработчика перетаскивания при этом не существовало вовсе.
 */
class CaptchaPresetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function service(): CaptchaService
    {
        return app('captcha');
    }

    /** Ответ, который сервер запомнил для выданного экземпляра. */
    private function answerFor(string $id): string
    {
        return (string) Session::get('captcha.instances.' . $id . '.code');
    }

    // ── Главное ограничение ───────────────────────────────────────────────

    public function test_two_captchas_on_one_page_do_not_break_each_other(): void
    {
        $service = $this->service();

        $first = $service->generate('math');
        $second = $service->generate('image');

        $this->assertNotSame($first['id'], $second['id'], 'У экземпляров должны быть разные идентификаторы');

        $firstAnswer = $this->answerFor($first['id']);
        $secondAnswer = $this->answerFor($second['id']);

        // Раньше выдача второй каптчи убивала первую
        $this->assertTrue(
            $service->verify($firstAnswer, 'math', $first['id']),
            'Первая каптча перестала проходить после выдачи второй'
        );
        $this->assertTrue(
            $service->verify($secondAnswer, 'image', $second['id']),
            'Вторая каптча не проходит'
        );
    }

    public function test_answer_of_one_captcha_does_not_pass_another(): void
    {
        $service = $this->service();

        $first = $service->generate('math');
        $second = $service->generate('math');

        $firstAnswer = $this->answerFor($first['id']);
        $secondAnswer = $this->answerFor($second['id']);

        if ($firstAnswer === $secondAnswer) {
            $this->markTestSkipped('Примеры совпали случайно — проверка бессмысленна');
        }

        $this->assertFalse($service->verify($firstAnswer, 'math', $second['id']));
    }

    // ── Утечка ответа ─────────────────────────────────────────────────────

    /**
     * @dataProvider typeProvider
     */
    public function test_answer_never_appears_in_the_markup(string $type, array $options = []): void
    {
        $html = $this->service()->render($type, $options);

        // Ответ берём по идентификатору ИМЕННО этой разметки, а не по типу.
        // Прежняя версия перебирала все экземпляры в сессии и брала последний
        // подходящий по типу — а их там может быть несколько, если каптча
        // рисовалась раньше в том же прогоне. Тогда сравнивался ответ от одной
        // каптчи с разметкой другой, и тест то проходил, то падал на случайном
        // совпадении цифр.
        preg_match('~name="captcha_id" value="([^"]+)"~', $html, $match);

        $this->assertNotEmpty($match[1] ?? '', 'В разметке нет идентификатора экземпляра');

        $answer = (string) Session::get('captcha.instances.' . $match[1] . '.code', '');

        $this->assertNotSame('', $answer, 'Сервер не запомнил ответ');

        // ⚠️ Сравниваем ТОЧЕЧНО, а не «нет ли числа где-нибудь в разметке».
        // Ответ — короткое число, и в оформлении оно встречается сплошь и
        // рядом: в размерах (width:120px), в цветах, в случайном
        // идентификаторе экземпляра, в пикселях base64. Из-за этого проверка
        // плавала — падала примерно раз на прогон и проходила при повторе,
        // причём дважды: сначала на идентификаторе, потом на стилях.
        //
        // Утечка, которая реально опасна, — это ответ, доступный клиенту как
        // ЗНАЧЕНИЕ: в поле формы, в data-атрибуте или прямо текстом. Их и
        // проверяем, а оформление и двоичные данные из сравнения выкидываем.
        $безОформления = preg_replace('~\s(?:style|class)="[^"]*"~i', '', $html);
        // ⚠️ Идентификатор экземпляра выкидываем в ОБОИХ местах, где он
        // встречается: и в скрытом поле, и в data-атрибуте обёртки. Второй
        // раньше оставался, а это случайные шестнадцатеричные знаки — в них
        // однозначный ответ («8») находится сам собой примерно раз на
        // прогон. Ровно так тест и падал через раз.
        $безОформления = preg_replace('~name="captcha_id" value="[^"]*"~', '', $безОформления);
        $безОформления = preg_replace('~\sdata-captcha-id="[^"]*"~', '', $безОформления);
        $безОформления = preg_replace('~data:image/[a-z+]+;base64,[A-Za-z0-9+/=]+~', '', $безОформления);

        // Значения атрибутов + видимый текст — всё, что клиент может прочесть.
        preg_match_all('~(?:value|data-[a-z-]+)="([^"]*)"~i', $безОформления, $значения);
        $текст = trim(preg_replace('~\s+~u', ' ', strip_tags($безОформления)));

        $читаемое = array_merge($значения[1], [$текст]);

        foreach ($читаемое as $кусок) {
            $this->assertStringNotContainsString(
                $answer,
                $кусок,
                "Ответ каптчи типа {$type} утёк в разметку — её можно пройти, открыв исходный код"
            );
        }
    }

    public static function typeProvider(): array
    {
        return [
            'картинка' => ['image', []],
            'пример'   => ['math', ['min' => 100, 'max' => 999, 'operations' => ['+']]],
            // Ответ намеренно длинный и непохожий на числа из CSS: короткие
            // вроде «4» или «12» встречаются в любых стилях и в base64,
            // и проверка срабатывала бы вхолостую
            'вопрос'   => ['question', ['questions' => [['q' => 'Кодовое слово?', 'a' => 'ЯблокоГрушаСлива']]]],
            'слайдер'  => ['slider', []],
        ];
    }

    // ── Слайдер ───────────────────────────────────────────────────────────

    public function test_slider_can_actually_be_passed(): void
    {
        $service = $this->service();
        $captcha = $service->generate('slider', ['tolerance' => 10]);
        $target = (int) $this->answerFor($captcha['id']);

        $this->assertTrue($service->verify((string) $target, 'slider', $captcha['id']));
    }

    public function test_slider_rejects_a_miss_and_an_untouched_handle(): void
    {
        $service = $this->service();

        $miss = $service->generate('slider', ['tolerance' => 10]);
        $target = (int) $this->answerFor($miss['id']);
        $this->assertFalse($service->verify((string) ($target + 50), 'slider', $miss['id']));

        // Пустое значение = ползунок не трогали
        $untouched = $service->generate('slider');
        $this->assertFalse($service->verify('', 'slider', $untouched['id']));
    }

    // ── Проверка ответа ───────────────────────────────────────────────────

    public function test_wrong_answer_is_rejected(): void
    {
        $service = $this->service();
        $captcha = $service->generate('math');

        $this->assertFalse($service->verify('заведомо неверно', 'math', $captcha['id']));
    }

    public function test_correct_answer_works_only_once(): void
    {
        $service = $this->service();
        $captcha = $service->generate('math');
        $answer = $this->answerFor($captcha['id']);

        $this->assertTrue($service->verify($answer, 'math', $captcha['id']));
        $this->assertFalse(
            $service->verify($answer, 'math', $captcha['id']),
            'Угаданный код нельзя переиспользовать повторной отправкой формы'
        );
    }

    public function test_preset_type_is_respected(): void
    {
        // Пресет «слайдер», проверенный как image, не должен пропускать никого:
        // иначе тип в сборке ничего не значит
        $service = $this->service();
        $captcha = $service->generate('slider');
        $target = $this->answerFor($captcha['id']);

        $this->assertFalse($service->verify($target, 'image', $captcha['id']));
    }

    public function test_legacy_call_without_an_id_still_works(): void
    {
        // На этой сигнатуре держатся модуль Комментариев и правило 'captcha:image'
        $service = $this->service();
        $captcha = $service->generate('image');

        $this->assertTrue($service->verify($this->answerFor($captcha['id']), 'image'));
    }

    public function test_validation_rule_picks_the_right_instance(): void
    {
        $service = $this->service();

        $first = $service->generate('math');
        $second = $service->generate('image');

        $firstAnswer = $this->answerFor($first['id']);

        // Правило без параметра берёт тип из самого экземпляра по captcha_id
        $validator = validator(
            ['captcha' => $firstAnswer, 'captcha_id' => $first['id']],
            ['captcha' => 'required|captcha']
        );

        $this->assertTrue($validator->passes(), 'Правило не нашло нужный экземпляр по captcha_id');
        $this->assertNotSame($first['id'], $second['id']);
    }

    // ── Пресеты ───────────────────────────────────────────────────────────

    public function test_preset_is_created_through_the_constructor(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.captcha.presets.store'), [
                'name' => 'Обратная связь',
                'type' => 'math',
                'min' => 2,
                'max' => 15,
                'operations' => ['+', '-'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.captcha.index'));

        $preset = CaptchaPreset::firstOrFail();

        $this->assertSame('Обратная связь', $preset->name);
        $this->assertSame('math', $preset->type);
        $this->assertSame(2, $preset->options['min']);
        $this->assertSame(['+', '-'], $preset->options['operations']);
        $this->assertTrue($preset->is_active);

        // Кириллица в названии не должна давать пустой слаг
        $this->assertNotSame('', $preset->slug);
    }

    public function test_only_meaningful_options_are_saved(): void
    {
        // Ручки чужого типа не должны попадать в сборку: обещать настройку,
        // которая ни на что не влияет, — врать пользователю
        $this->actingAs($this->admin())->post(route('admin.captcha.presets.store'), [
            'name' => 'Картинка',
            'type' => 'image',
            'length' => 6,
            'min' => 5,          // это от «примера»
            'tolerance' => 30,   // это от слайдера
        ])->assertRedirect();

        $options = CaptchaPreset::firstOrFail()->options;

        $this->assertSame(6, $options['length']);
        $this->assertArrayNotHasKey('min', $options);
        $this->assertArrayNotHasKey('tolerance', $options);
    }

    public function test_preview_returns_a_working_captcha(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.captcha.preview'), ['type' => 'math', 'min' => 1, 'max' => 10]);

        $response->assertStatus(200);
        $html = $response->json('html');

        $this->assertStringContainsString('captcha-wrapper', $html);
        $this->assertStringContainsString('name="captcha_id"', $html);
    }

    public function test_preset_can_be_duplicated_and_deleted(): void
    {
        $admin = $this->admin();
        $preset = CaptchaPreset::create(['name' => 'Основная', 'type' => 'image', 'options' => ['length' => 5]]);

        $this->actingAs($admin)->post(route('admin.captcha.presets.duplicate', $preset))->assertRedirect();

        $copy = CaptchaPreset::where('id', '!=', $preset->id)->firstOrFail();
        $this->assertFalse($copy->is_active, 'Копия не должна включаться сама');
        $this->assertNotSame($preset->slug, $copy->slug);

        $this->actingAs($admin)->delete(route('admin.captcha.presets.destroy', $copy))->assertRedirect();
        $this->assertNull(CaptchaPreset::find($copy->id));
    }

    // ── Вставка в материалы ───────────────────────────────────────────────

    public function test_shortcode_is_expanded_when_content_is_shown(): void
    {
        $preset = CaptchaPreset::create([
            'name' => 'Форма', 'type' => 'math', 'options' => ['min' => 1, 'max' => 10], 'is_active' => true,
        ]);

        $html = render_shortcodes('<p>Текст</p>' . $preset->shortcode() . '<p>Ещё текст</p>');

        $this->assertStringContainsString('captcha-wrapper', $html);
        $this->assertStringContainsString('name="captcha_id"', $html);
        $this->assertStringNotContainsString('[captcha', $html);
    }

    public function test_shortcode_of_a_missing_preset_does_not_break_the_page(): void
    {
        // Сборку могли удалить уже после того, как её вставили в материал
        $html = render_shortcodes('<p>Текст</p>[captcha preset="udalennaya"]<p>Дальше</p>');

        $this->assertStringContainsString('Текст', $html);
        $this->assertStringContainsString('Дальше', $html);
        $this->assertStringNotContainsString('[captcha', $html);
    }

    public function test_disabled_preset_renders_nothing(): void
    {
        $preset = CaptchaPreset::create(['name' => 'Выключенная', 'type' => 'image', 'is_active' => false]);

        $this->assertSame('', captcha_preset($preset->slug));
    }

    public function test_content_without_shortcodes_is_untouched(): void
    {
        $content = '<p>Обычный текст без вставок</p>';

        $this->assertSame($content, render_shortcodes($content));
    }

    public function test_presets_appear_in_the_material_editors(): void
    {
        CaptchaPreset::create(['name' => 'Форма обратной связи', 'type' => 'image', 'is_active' => true]);

        $admin = $this->admin();

        // Сборки теперь уезжают в настройки редактора, а не рисуются отдельным
        // выпадающим списком под полем: выбор стал кнопкой панели инструментов
        // и вставляет шорткод в позицию курсора, а не «куда получится».
        foreach ([route('admin.news.create'), route('admin.pages.create')] as $url) {
            $this->actingAs($admin)->withSession(['app_locale' => 'ru'])->get($url)
                ->assertStatus(200)
                ->assertSee('Форма обратной связи', false)
                ->assertSee('captchaPresets', false)
                ->assertSee('ru-editor.js', false);
        }
    }

    public function test_editor_offers_the_constructor_when_nothing_is_saved(): void
    {
        // Пустой список ничего не объясняет — в меню кнопки остаётся один
        // пункт со ссылкой в конструктор, а сам список сборок пуст.
        $this->assertSame(0, CaptchaPreset::count());

        $this->actingAs($this->admin())->withSession(['app_locale' => 'ru'])
            ->get(route('admin.news.create'))
            ->assertStatus(200)
            ->assertSee('&quot;captchaPresets&quot;:[]', false)
            ->assertSee(route('admin.captcha.index'), false);
    }

    public function test_constructor_page_lists_saved_presets(): void
    {
        CaptchaPreset::create(['name' => 'Комментарии', 'type' => 'slider', 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.captcha.index'))
            ->assertStatus(200)
            ->assertSee('Комментарии', false)
            ->assertSee('[captcha preset=', false);
    }

    public function test_preset_routes_are_closed_for_non_admins(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post(route('admin.captcha.presets.store'), [
            'name' => 'Чужая', 'type' => 'image',
        ])->assertStatus(403);

        $this->assertSame(0, CaptchaPreset::count());
    }

    public function test_shortcode_route_helper_is_available_everywhere(): void
    {
        // Хелперы объявлены в файле маршрутов модуля — если модуль отключат,
        // страница не должна падать с «call to undefined function»
        $this->assertTrue(function_exists('captcha_preset'));
        $this->assertTrue(function_exists('captcha_field'));
        $this->assertTrue(Route::has('admin.captcha.index'));
    }

    public function test_default_presets_cover_every_type(): void
    {
        // Набор сборок — это и рабочие варианты, и показ возможностей: если
        // очередной вид проверки нигде не заведён, посмотреть на него негде.
        $types = [];

        foreach (CaptchaSeeder::definitions() as $definition) {
            $types[$definition['type']] = true;
        }

        $this->assertSame(
            [],
            array_values(array_diff(CaptchaPreset::TYPES, array_keys($types))),
            'Эти виды каптчи не показаны ни в одной сборке по умолчанию'
        );
    }

    public function test_default_presets_are_seeded_and_render(): void
    {
        CaptchaSeeder::seed();

        $presets = CaptchaPreset::all();

        $this->assertGreaterThanOrEqual(6, $presets->count(), 'Набор сборок подозрительно мал');
        $this->assertSame(
            $presets->count(),
            $presets->pluck('slug')->unique()->count(),
            'Слаги сборок повторяются — шорткоды будут вести не туда'
        );

        foreach ($presets as $preset) {
            $html = captcha_preset($preset->slug);

            $this->assertNotSame('', $html, 'Сборка «' . $preset->name . '» ничего не нарисовала');
            $this->assertStringContainsString('name="captcha"', $html, 'В сборке «' . $preset->name . '» нет поля ответа');
        }
    }

    public function test_seeding_twice_does_not_duplicate(): void
    {
        CaptchaSeeder::seed();
        $first = CaptchaPreset::count();

        CaptchaSeeder::seed();

        $this->assertSame($first, CaptchaPreset::count(), 'Повторный прогон задваивает сборки');
    }
}
