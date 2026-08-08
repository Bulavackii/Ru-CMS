<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Сторож выравнивания в редакторе.
 *
 * Запускать браузер в тестах негде, поэтому здесь проверяется то, что можно
 * проверить без него: наличие правил оформления, от которых зависит вывод на
 * сайте, и отсутствие подходов, на которых выравнивание уже ломалось. Это
 * защита от молчаливого отката, а не замена живой проверке в браузере.
 */
class EditorAlignmentTest extends TestCase
{
    private function editorFormat(): string
    {
        return file_get_contents(public_path('assets/js/ru-editor-format.js'));
    }

    public function test_form_alignment_rules_exist_in_stylesheet(): void
    {
        // Форма — блок, и text-align у родителя её не двигает. Центрирование
        // держится на метке sc-align-*, которую ставит render_shortcodes.
        // Пропадут эти правила — форма молча прилипнет к левому краю.
        $css = file_get_contents(public_path('assets/css/forms.css'));

        $this->assertStringContainsString('.sc-align-center > .rf', $css);
        $this->assertStringContainsString('.sc-align-right > .rf', $css);
    }

    public function test_button_state_is_read_from_markup(): void
    {
        // Подсветка считалась через queryCommandState, а он знает только про
        // выравнивание текста: у плашки шорткода не горело ничего, а у
        // картинки горела заведомо не та кнопка. Состояние берётся из разметки.
        $js = $this->editorFormat();

        $this->assertStringContainsString('function alignStateOf(editor)', $js);
        $this->assertStringContainsString(
            "active: function (editor) { return alignStateOf(editor) === (item[4] || 'justify'); }",
            $js,
            'Подсветка выравнивания вернулась к запросу состояния у браузера'
        );
    }

    public function test_chip_is_searched_inside_the_selection(): void
    {
        // Выделенная плашка лежит ВНУТРИ выделения, а closest ищет вверх по
        // дереву — одного closest не хватает, кнопки не срабатывали.
        $this->assertStringContainsString('function chipUnderSelection(editor)', $this->editorFormat());
    }

    public function test_author_width_survives_alignment(): void
    {
        // Выравнивание сбрасывало ширину, заданную растягиванием за уголок:
        // видео на 320px после нажатия кнопки снова становилось во всю строку.
        // Подгонка ставится только когда своей ширины нет, а снимается только
        // если её ставили мы (значение fit-content).
        $js = $this->editorFormat();

        $this->assertStringContainsString(
            "if (mode && !box.style.width && !box.classList.contains('pc-audio'))",
            $js,
            'Выравнивание снова трогает ширину, выбранную автором'
        );
        $this->assertStringContainsString("if (!mode && box.style.width === 'fit-content')", $js);
    }

    public function test_players_are_reachable_from_the_bubble(): void
    {
        // Всплывающая панель показывалась только у картинки: у вставленного
        // ролика не было ни выравнивания, ни ширины, ни удаления — убрать его
        // из материала было нечем.
        $js = file_get_contents(public_path('assets/js/ru-editor-media.js'));

        $this->assertStringContainsString("closest('img, video, audio, iframe')", $js);
        $this->assertStringContainsString("target.closest('figure, .pc-player')", $js);
    }

    public function test_media_alignment_has_a_single_definition(): void
    {
        // У панели была СВОЯ копия выравнивания — с тем же багом полной
        // ширины, который в общей функции уже починен. Две копии одного
        // поведения в этом проекте уже расходились не раз.
        $js = file_get_contents(public_path('assets/js/ru-editor-media.js'));

        $this->assertStringNotContainsString('function align(img, mode)', $js);
        $this->assertStringContainsString('RuEditor.alignMedia(editor,', $js);
        $this->assertStringContainsString('RuEditor.alignMedia = alignMedia;', $this->editorFormat());
    }

    public function test_audio_player_never_floats(): void
    {
        // Обтекание сжимает полосу проигрывателя по содержимому: в замере
        // флекс схлопывался до 30 пикселей вместо полной строки.
        $this->assertStringContainsString(
            "if (box.classList.contains('pc-audio'))",
            $this->editorFormat()
        );
    }

    public function test_paragraph_split_carries_alignment(): void
    {
        // Наследование выравнивания на новый абзац завязано на события ввода,
        // а не на клавишу Enter: абзац делят и вставка, и голосовой ввод, и
        // мобильная автозамена — key там не приходит вовсе.
        $js = $this->editorFormat();

        $this->assertStringContainsString("event.inputType !== 'insertParagraph'", $js);
        $this->assertStringNotContainsString("event.key !== 'Enter'", $js);
    }
}
