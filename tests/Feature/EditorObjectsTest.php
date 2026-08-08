<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forms\Models\Form;
use Tests\TestCase;

/**
 * Сторож общего слоя объектов редактора.
 *
 * Браузер в тестах не поднять, поэтому здесь проверяется то, что можно без
 * него: что слой один, что его подключили, и что выравнивание доходит до
 * сайта. Это защита от молчаливого отката, а не замена живой проверке.
 */
class EditorObjectsTest extends TestCase
{
    use RefreshDatabase;

    private function js(string $name): string
    {
        return file_get_contents(public_path('assets/js/' . $name));
    }

    public function test_objects_layer_is_loaded_before_the_rest(): void
    {
        // Порядок важен: остальные файлы зовут RuEditor.objects из обработчиков,
        // но набор коробок нужен им уже при разборе.
        $view = file_get_contents(resource_path('views/components/ru-editor.blade.php'));

        $this->assertStringContainsString('ru-editor-objects.js', $view, 'Слой объектов не подключён');
        $this->assertLessThan(
            strpos($view, 'ru-editor-format.js'),
            strpos($view, 'ru-editor-objects.js'),
            'Слой объектов должен подключаться раньше остальных'
        );
    }

    public function test_alignment_has_a_single_implementation(): void
    {
        // Раньше выравнивание жило в трёх местах с разными наборами
        // исключений, и «работает у картинки, но не у видео» было нормой.
        $objects = $this->js('ru-editor-objects.js');

        $this->assertStringContainsString('function align(editor, mode)', $objects);
        $this->assertStringContainsString('RuEditor.objects.alignState(editor)', $this->js('ru-editor-format.js'));

        $media = $this->js('ru-editor-media.js');

        $this->assertStringNotContainsString('function align(img, mode)', $media, 'В панели снова своя копия выравнивания');
        $this->assertStringContainsString('RuEditor.objects.wrap(editor,', $media);
    }

    public function test_every_operation_uses_the_same_box(): void
    {
        // Растягивание, удаление и выравнивание обязаны двигать ОДНУ и ту же
        // обёртку. Раньше растягивание знало только про figure, и ширина
        // ролика уходила на сам тег мимо его обёртки.
        $this->assertStringContainsString('RuEditor.objects.BOXES', $this->js('ru-editor-resize.js'));
        $this->assertStringContainsString('RuEditor.objects.BOXES', $this->js('ru-editor-media.js'));
        $this->assertStringContainsString("var BOXES = 'figure,.pc-player';", $this->js('ru-editor-objects.js'));
    }

    public function test_delete_and_backspace_remove_objects(): void
    {
        $objects = $this->js('ru-editor-objects.js');

        $this->assertStringContainsString("event.key !== 'Delete' && event.key !== 'Backspace'", $objects);
        $this->assertStringContainsString('function neighbourObject(editor, forward)', $objects);
    }

    public function test_shortcode_paragraph_becomes_a_block(): void
    {
        // Абзац не держит внутри себя блок: разборщик HTML выбрасывает форму
        // наружу, и выравнивание остаётся на пустой оболочке, а сама форма
        // встаёт отдельно и всегда слева.
        $form = Form::create([
            'title'     => 'Обратная связь',
            'slug'      => 'svyaz',
            'is_active' => true,
            'fields'    => [
                ['type' => 'text', 'name' => 'name', 'label' => 'Имя', 'required' => true, 'width' => 'full', 'options' => []],
            ],
            'settings'  => [],
        ]);

        $html = render_shortcodes('<p style="text-align: center;">[form slug="' . $form->slug . '"]</p>');

        $this->assertStringStartsWith('<div', $html, 'Абзац не стал блоком');
        $this->assertStringContainsString('text-align: center', $html, 'Выравнивание потерялось');

        // Абзац с текстом вокруг шорткода остаётся абзацем.
        $this->assertStringStartsWith('<p>', render_shortcodes('<p>До [form slug="' . $form->slug . '"] после</p>'));
        $this->assertStringStartsWith('<p ', render_shortcodes('<p style="text-align: center;">Просто текст</p>'));
    }

    public function test_audio_player_gets_a_width_it_can_be_moved_within(): void
    {
        // Полосу шириной с колонку двигать некуда: выравнивание по центру у
        // элемента во всю строку не делает ничего видимого. Ширина по
        // умолчанию ставится только при её отсутствии — растянутую автором
        // за ручки полосу трогать нельзя.
        $js = $this->js('ru-editor-objects.js');

        $this->assertStringContainsString("box.style.width = 'min(560px, 100%)';", $js);
        $this->assertStringContainsString('if (!box.style.width) {', $js);
    }

    public function test_alignment_follows_the_clicked_object(): void
    {
        // Курсор — не единственный признак выбора. У проигрывателя с родными
        // кнопками щелчок съедают сами кнопки, курсор на него не встаёт, и
        // выравнивание уходило в СОСЕДНИЙ блок: кнопка загоралась, а полоса
        // не двигалась. Опираемся на выбор, который ведут растягивание и
        // всплывающая панель, и снимаем его, когда курсор ушёл в текст.
        $js = $this->js('ru-editor-objects.js');

        $this->assertStringContainsString('var chosen = editor.selectedMedia;', $js);
        $this->assertStringContainsString("addEventListener('selectionchange'", $js);
        $this->assertStringContainsString('editor.selectedMedia = null;', $js);
    }

    public function test_player_preview_never_reaches_the_database(): void
    {
        // В рамке редактора проигрыватель звука рисует тот же скрипт, что и на
        // сайте, — иначе вид в панели и на странице разошёлся бы. Но его
        // разметка нарисована для показа: сохранись она, на сайте поверх неё
        // построилась бы вторая такая же панель. Класс готовности снимается по
        // той же причине: с ним родной проигрыватель спрятан, и не отработай
        // скрипт на странице, от звука не осталось бы ничего.
        $this->assertStringContainsString("'data-ru-transient': '1'", $this->js('content-players.js'));

        $core = $this->js('ru-editor.js');

        $this->assertStringContainsString("querySelectorAll('[data-ru-transient]')", $core);
        $this->assertStringContainsString("querySelectorAll('.is-ready')", $core);
    }

    public function test_editor_loads_the_site_player_script(): void
    {
        // Одна сборка оформления на оба места. Вторая копия неизбежно
        // разошлась бы с первой — так в этом проекте уже случалось не раз.
        $view = file_get_contents(resource_path('views/components/ru-editor.blade.php'));

        $this->assertStringContainsString('content-players.js', $view);
        $this->assertStringContainsString("registerPlugin('player-preview'", $this->js('ru-editor-media.js'));
    }

    public function test_form_is_inline_so_alignment_moves_it(): void
    {
        // Тем же способом, что и всё остальное содержимое: строчный блок
        // двигается обычным text-align у блока, в котором стоит.
        $css = file_get_contents(public_path('assets/css/forms.css'));

        $this->assertMatchesRegularExpression('~\.rf\s*\{[^}]*display:\s*inline-block~s', $css);
    }
}
