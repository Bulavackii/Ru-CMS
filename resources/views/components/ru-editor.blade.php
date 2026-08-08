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

    // Сборки каптчи читаем один раз на страницу: редакторов на форме бывает
    // несколько, а запрос к базе на каждый из них ни к чему.
    $captchaPresets = \Illuminate\Support\Facades\Route::has('admin.captcha.index')
        && class_exists(\Modules\Captcha\Models\CaptchaPreset::class)
        ? \Modules\Captcha\Models\CaptchaPreset::activeList()
            ->map(fn ($preset) => [
                'slug' => $preset->slug,
                'name' => $preset->name,
                'type' => $preset->type ?? '',
            ])->values()->all()
        : [];

    // Сохранённые формы — тем же приёмом, что и сборки каптчи: один запрос на
    // страницу, сколько бы редакторов на ней ни было.
    $formList = \Illuminate\Support\Facades\Route::has('admin.forms.index')
        && class_exists(\Modules\Forms\Models\Form::class)
        ? \Modules\Forms\Models\Form::activeList()
            ->map(fn ($form) => ['slug' => $form->slug, 'title' => $form->title])
            ->values()->all()
        : [];

    // Наборы кнопок. Полный — для новостей и страниц, где верстают материал
    // целиком; простой — для писем и уведомлений, где сложная разметка только
    // мешает почтовым клиентам.
    $presets = [
        // Готовые блоки (формы, каптча, блоки оформления) стоят в НАЧАЛЕ, сразу
        // после отмены: это то, ради чего в редактор заходят чаще всего, а
        // раньше они были в середине длинной ленты кнопок и терялись между
        // выравниванием и списками.
        'full'   => 'undo redo | forms captcha ruBlocks | blocks | fontfamily fontsize | '
                  . 'bold italic underline strikethrough | '
                  . 'forecolor backcolor | alignleft aligncenter alignright alignjustify | '
                  . 'bullist numlist outdent indent | link unlink | image media audio file table | '
                  . 'charmap | removeformat | '
                  . 'searchreplace visualblocks code preview fullscreen help',
        'page'   => 'undo redo | forms captcha ruBlocks | blocks | fontfamily | bold italic underline | '
                  . 'alignleft aligncenter alignright | bullist numlist | link unlink | image media audio file table | '
                  . 'removeformat | visualblocks code preview fullscreen',
        'simple' => 'undo redo | bold italic underline | bullist numlist | link unlink | removeformat | code fullscreen',
        'mail'   => 'undo redo | bold italic underline | bullist numlist | link unlink | removeformat | code',
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
        // Оформление содержимого плюс сами гарнитуры. Без вторых выбор шрифта
        // в панели ничего бы не менял: рамка редактора — отдельный документ,
        // и шрифты страницы в неё не попадают. Файлы крошечные (по паре
        // килобайт), а woff2 скачивается только если шрифт реально выбран.
        'contentCss'  => $contentCss
            ? [
                asset_v('assets/css/content-blocks.css'),
                // Гарнитуры содержимого одним файлом. Без них выбор шрифта в
                // панели ничего бы не показывал: рамка редактора — отдельный
                // документ, и шрифты страницы в неё не попадают.
                asset_v('assets/css/content-fonts.css'),
            ]
            : [],
        'csrf'        => csrf_token(),
        // Загрузка идёт в модуль Файлы, а не в отдельный каталог: только так
        // картинка из материала попадает в медиатеку и её потом можно выбрать
        // повторно. Прежний загрузчик редактора складывал файлы мимо неё, и
        // два хранилища жили независимо друг от друга.
        'uploadUrl'   => Route::has('admin.files.upload') ? route('admin.files.upload') : null,
        'browseUrl'   => Route::has('admin.files.browse') ? route('admin.files.browse') : null,
        // Свои шрифты — из одного реестра с темами и с рамкой редактора.
        // Второй список в скрипте неизбежно разошёлся бы с этим.
        'fonts'       => collect(LOCAL_FONTS)
            ->map(fn ($font, $slug) => [
                'slug'   => $slug,
                'family' => $font['family'],
                'label'  => $font['label'],
                'kind'   => $font['kind'] ?? 'sans',
            ])->values()->all(),
        'uploadHint'  => __('admin.editor.upload_hint', ['size' => max_upload_label()]),
        // Предел проверяется ещё в браузере: иначе человек честно ждёт
        // закачки мегабайтов, сервер обрывает приём, и в ответ приходит
        // голое «413» — техническая подробность, из которой ничего не следует.
        'maxUploadBytes'   => max_upload_kb() * 1024,
        'uploadLimitLabel' => max_upload_label(),
        // Сохранённые сборки каптчи. Прежде выбор жил ОТДЕЛЬНЫМ выпадающим
        // списком под полем содержимого: он не был частью редактора, не знал,
        // где стоит курсор, и вставлял шорткод «куда получится».
        'captchaPresets' => $captchaPresets,
        'captchaUrl'     => Route::has('admin.captcha.index') ? route('admin.captcha.index') : null,
        // Готовые формы: вставляются шорткодом в позицию курсора.
        'forms'          => $formList,
        'formsUrl'       => Route::has('admin.forms.index') ? route('admin.forms.index') : null,
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
        <link rel="stylesheet" href="{{ asset_v('assets/css/ru-editor.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/ru-editor.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-ui.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-format.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-media.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-blocks.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-resize.js') }}"></script>
        <script src="{{ asset_v('assets/js/ru-editor-tools.js') }}"></script>

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
