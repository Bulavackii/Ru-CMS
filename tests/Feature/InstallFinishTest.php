<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Финальный экран установки.
 *
 * Владелец поймал это вживую: шаг /install/finish ставил всё содержимое и
 * тут же отдавал итоговую страницу одним длинным запросом. Соединение
 * оборвалось на последних байтах — и итог пропал НАВСЕГДА: файл-замок уже
 * создан, поэтому повторный заход упирался в BlockIfInstalled, тот уводил
 * на форму входа, а форма — уже вошедшего админа в личный кабинет.
 *
 * Тесты закрепляют разделение: тяжёлый запрос отдаёт короткий редирект, а
 * итог живёт по отдельному адресу /install/done ЗА пределами блокировки.
 */
class InstallFinishTest extends TestCase
{
    use RefreshDatabase;

    /** Сводка, которую finish() кладёт в сессию перед редиректом. */
    private const SUMMARY = ['warnings' => [], 'country' => 'RU'];

    protected function setUp(): void
    {
        parent::setUp();

        // Все проверки описывают поведение УЖЕ установленной системы.
        // Файл-замок машинно-локальный: без него смысла в тесте нет, но и
        // создавать его молча нельзя — владелец удаляет его намеренно,
        // когда смотрит мастер в браузере.
        if (! File::exists(storage_path('install.lock'))) {
            $this->markTestSkipped('Нет storage/install.lock — система считается неустановленной.');
        }

        // ⚠️ На адресах /install* драйвер сессии принудительно переводится
        // на file (SkipDatabaseForInstall — чтобы мастер работал до
        // появления базы). Тесты стартуют на array, и без этой строки
        // withSession() писал бы в один store, а запрос читал другой:
        // сводка «терялась», хотя в бою оба шага читают одну и ту же
        // файловую сессию.
        config(['session.driver' => 'file']);
    }

    public function test_done_page_shows_the_summary_from_session(): void
    {
        $response = $this->withSession(['install.summary' => self::SUMMARY])
            ->get(route('install.done'));

        $response->assertOk();
        $response->assertViewIs('Install::finish');
        $response->assertViewHas('selectedCountry');
    }

    public function test_done_page_sends_a_stranger_to_the_site(): void
    {
        // Чужой браузер, закладка или истёкшая сессия: показывать нечего,
        // а установка уже позади — уводим на главную, а не на форму входа.
        $this->get(route('install.done'))->assertRedirect('/');
    }

    public function test_broken_connection_returns_the_installer_to_the_summary(): void
    {
        // Ровно тот случай, что поймал владелец: соединение оборвалось,
        // браузер повторил запрос мастера. Раньше это заканчивалось
        // личным кабинетом, теперь — итогом установки.
        $this->withSession(['install.summary' => self::SUMMARY])
            ->get(route('install.finish'))
            ->assertRedirect(route('install.done'));
    }

    public function test_wizard_stays_closed_for_everyone_else(): void
    {
        $this->get(route('install.welcome'))->assertRedirect(route('login'));
    }

    public function test_summary_page_leads_to_the_site_and_to_the_panel(): void
    {
        $html = $this->withSession(['install.summary' => self::SUMMARY])
            ->get(route('install.done'))->getContent();

        // Отсчёт уводит на главную страницу сайта — так попросил владелец.
        $this->assertStringContainsString('window.location.href = ' . \Illuminate\Support\Js::from(url('/')), $html);
        $this->assertStringContainsString('href="' . route('admin.dashboard') . '"', $html);
    }
}
