{{--
    Визуальный редактор RU Editor — единая точка подключения.

    До него редактор поднимался СЕМЬЮ независимыми вызовами tinymce.init() в
    разных вьюхах, и наборы возможностей у них давно разъехались: в новостях
    были таблицы и картинки, в уведомлениях только ссылки и списки, в
    фрагментах — третий набор. Теперь набор задаётся одним параметром toolbar,
    а всё остальное одинаково везде.

    Параметры:
      name         — имя поля формы (обязателен)
      value        — начальное содержимое
      id           — идентификатор поля, по умолчанию совпадает с name
      height       — высота рамки в пикселях
      preset       — готовый набор кнопок: full | page | simple | mail
      toolbar      — свой набор кнопок, перебивает preset
      placeholder  — подсказка в пустом редакторе
      contentCss   — подключать ли оформление содержимого сайта
      bodyClass    — класс на теле документа внутри рамки
--}}
@props([
    'name',
    'value' => '',
    'id' => null,
    'height' => 480,
    'preset' => 'full',
    'toolbar' => null,
    'placeholder' => null,
    'contentCss' => true,
    'bodyClass' => 'page-content',
])

@php
    $editorId = $id ?? $name;

    // Наборы кнопок. Полный — для новостей и страниц, где верстают материал
    // целиком; простой — для писем и уведомлений, где сложная разметка только
    // мешает почтовым клиентам.
    $presets = [
        'full'   => 'undo redo | blocks | fontfamily fontsize | bold italic underline strikethrough | '
                  . 'forecolor backcolor | alignleft aligncenter alignright alignjustify | '
                  . 'bullist numlist outdent indent | link unlink | image media | removeformat',
        'page'   => 'undo redo | blocks | bold italic underline | '
                  . 'alignleft aligncenter alignright | bullist numlist | link unlink | image | removeformat',
        'simple' => 'undo redo | bold italic underline | bullist numlist | link unlink | removeformat',
        'mail'   => 'undo redo | bold italic underline | bullist numlist | link unlink | removeformat',
    ];

    $toolbarSpec = $toolbar ?: ($presets[$preset] ?? $presets['full']);

    $config = [
        'height'      => (int) $height,
        'toolbar'     => $toolbarSpec,
        'bodyClass'   => $bodyClass,
        'placeholder' => $placeholder ?? __('admin.editor.placeholder'),
        'lang'        => app()->getLocale(),
        // Оформление содержимого — тот же файл, что подключён на сайте.
        // Автор видит текст ровно таким, каким его увидит посетитель.
        'contentCss'  => $contentCss ? [asset('assets/css/content-blocks.css')] : [],
        'csrf'        => csrf_token(),
        // Загрузка идёт в модуль Файлы, а не в отдельный каталог: только так
        // картинка из материала попадает в медиатеку и её потом можно выбрать
        // повторно. Прежний загрузчик редактора складывал файлы мимо неё, и
        // два хранилища жили независимо друг от друга.
        'uploadUrl'   => Route::has('admin.files.upload') ? route('admin.files.upload') : null,
        'browseUrl'   => Route::has('admin.files.browse') ? route('admin.files.browse') : null,
        'uploadHint'  => __('admin.editor.upload_hint', ['size' => max_upload_label()]),
    ];
@endphp

<div class="ru-ed-holder">
    <textarea name="{{ $name }}"
              id="{{ $editorId }}"
              class="ru-ed-target"
              data-ru-editor-config="{{ json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
        >{{ old($name, $value) }}</textarea>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/ru-editor.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/ru-editor.js') }}"></script>
        <script src="{{ asset('assets/js/ru-editor-ui.js') }}"></script>
        <script src="{{ asset('assets/js/ru-editor-format.js') }}"></script>
        <script src="{{ asset('assets/js/ru-editor-media.js') }}"></script>

        <script>
            // Строки интерфейса приходят из словаря PHP, а не зашиты в скрипт:
            // панель переводится на семь языков, редактор — её часть.
            window.RuEditor.setStrings(@js(__('admin.editor.js')));

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('textarea.ru-ed-target').forEach(function (node) {
                    var config = {};

                    try {
                        config = JSON.parse(node.dataset.ruEditorConfig || '{}');
                    } catch (error) {
                        window.console && window.console.error('RuEditor: не разобрал настройки', error);
                    }

                    window.RuEditor.create(node, config);
                });
            });
        </script>
    @endpush
@endonce
