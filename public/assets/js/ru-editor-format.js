/* ============================================================================
   RU Editor — оформление текста: начертания, блоки, списки, цвет, ссылки.
   ----------------------------------------------------------------------------
   Всё зарегистрировано через публичные реестры ядра (registerButton /
   registerCommand) — тем же способом, каким свою кнопку добавит кто угодно.
   Ничего «привилегированного» у встроенных кнопок нет.
   ========================================================================= */

(function (window) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /* ── Отмена и повтор ─────────────────────────────────────────────── */

    RuEditor.registerCommand('undo', function (editor) {
        editor.undo();
    });

    RuEditor.registerCommand('redo', function (editor) {
        editor.redo();
    });

    RuEditor.registerButton('undo', {
        icon: 'fas fa-rotate-left',
        title: 'Отменить (Ctrl+Z)',
        // Кнопка сама зовёт историю: через exec() снимок лёг бы поверх того,
        // что мы только что откатили.
        action: function (editor) { editor.undo(); },
        enabled: function (editor) { return editor.canUndo(); }
    });

    RuEditor.registerButton('redo', {
        icon: 'fas fa-rotate-right',
        title: 'Повторить (Ctrl+Y)',
        action: function (editor) { editor.redo(); },
        enabled: function (editor) { return editor.canRedo(); }
    });

    /* ── Начертания ──────────────────────────────────────────────────── */

    [
        ['bold', 'fas fa-bold', 'Полужирный (Ctrl+B)'],
        ['italic', 'fas fa-italic', 'Курсив (Ctrl+I)'],
        ['underline', 'fas fa-underline', 'Подчёркнутый (Ctrl+U)'],
        ['strikeThrough', 'fas fa-strikethrough', 'Зачёркнутый'],
        ['subscript', 'fas fa-subscript', 'Подстрочный'],
        ['superscript', 'fas fa-superscript', 'Надстрочный']
    ].forEach(function (item) {
        RuEditor.registerButton(item[0].toLowerCase(), {
            icon: item[1],
            title: item[2],
            command: item[0],
            active: function (editor) { return editor.queryState(item[0]); }
        });
    });

    /* ── Блоки: абзац, заголовки, цитата, код ────────────────────────── */

    var BLOCKS = [
        { tag: 'p', label: 'Абзац' },
        { tag: 'h1', label: 'Заголовок 1', style: 'font-size:20px;font-weight:800' },
        { tag: 'h2', label: 'Заголовок 2', style: 'font-size:18px;font-weight:800' },
        { tag: 'h3', label: 'Заголовок 3', style: 'font-size:16px;font-weight:700' },
        { tag: 'h4', label: 'Заголовок 4', style: 'font-size:15px;font-weight:700' },
        { tag: 'h5', label: 'Заголовок 5', style: 'font-size:14px;font-weight:700' },
        { tag: 'h6', label: 'Заголовок 6', style: 'font-size:13px;font-weight:700' },
        { tag: 'blockquote', label: 'Цитата', style: 'font-style:italic' },
        { tag: 'pre', label: 'Программный код', style: 'font-family:ui-monospace,Consolas,monospace' }
    ];

    function currentBlock(editor) {
        var node = editor.closest(BLOCKS.map(function (b) { return b.tag; }).join(','));

        return node ? node.tagName.toLowerCase() : 'p';
    }

    RuEditor.registerButton('blocks', {
        type: 'menu',
        label: 'Абзац',
        title: 'Тип блока',
        currentLabel: function (editor) {
            var tag = currentBlock(editor);
            var found = BLOCKS.filter(function (b) { return b.tag === tag; })[0];

            return found ? found.label : 'Абзац';
        },
        items: function () {
            return BLOCKS.map(function (block) {
                return {
                    label: block.label,
                    style: block.style,
                    active: function (editor) { return currentBlock(editor) === block.tag; },
                    action: function (editor) { editor.exec('formatBlock', block.tag); }
                };
            });
        }
    });

    RuEditor.registerCommand('formatBlock', function (editor, tag) {
        // Некоторые движки хотят угловые скобки, некоторые — нет; с ними
        // работает везде.
        editor.native('formatBlock', '<' + String(tag).toLowerCase() + '>');
    });

    /* ── Шрифт и кегль ───────────────────────────────────────────────── */

    /**
     * Гарнитуры.
     *
     * Две группы, и это не косметика. Свои — те, что лежат в проекте
     * (public/assets/fonts) и подключены и в рамке редактора, и на сайте:
     * выбрал такую — посетитель увидит ровно её. Их список приходит из
     * настроек (реестр LOCAL_FONTS в PHP), чтобы не держать второй перечень,
     * который неизбежно разойдётся с первым.
     *
     * Системные есть не у всех: на телефоне и на Linux половины из них нет, и
     * текст покажется запасной гарнитурой из стека. Поэтому у каждой указан
     * стек, а не одно имя. Гарнитур без кириллицы (Impact, Tahoma) в списке
     * нет вовсе: на русском тексте они всё равно подменяются системными.
     */
    var SYSTEM_FONTS = [
        { label: 'Системный', value: 'system-ui, -apple-system, sans-serif' },
        { label: 'Arial', value: 'Arial, Helvetica, sans-serif' },
        { label: 'Verdana', value: 'Verdana, Geneva, sans-serif' },
        { label: 'Trebuchet MS', value: '"Trebuchet MS", Verdana, sans-serif' },
        { label: 'Georgia', value: 'Georgia, "Times New Roman", serif' },
        { label: 'Times New Roman', value: '"Times New Roman", Times, serif' },
        { label: 'Palatino', value: '"Palatino Linotype", Palatino, Georgia, serif' },
        { label: 'Courier New', value: '"Courier New", ui-monospace, monospace' },
        { label: 'Моноширинный', value: 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace' }
    ];

    /** Запасной стек по виду гарнитуры — на случай, если шрифт не подгрузился. */
    var FALLBACK = {
        sans: 'system-ui, sans-serif',
        serif: 'Georgia, serif',
        mono: 'ui-monospace, monospace',
        hand: 'cursive'
    };

    function fontItems(editor) {
        var own = editor.options.fonts || [];

        var items = [{
            label: t('font.site', 'Шрифт сайта'),
            action: function (ed) { ed.exec('clearStyle', { prop: 'font-family' }); }
        }];

        if (own.length) {
            items.push({ head: t('font.own_head', 'Шрифты проекта') });

            own.forEach(function (font) {
                var stack = '"' + font.family + '", ' + (FALLBACK[font.kind] || FALLBACK.sans);

                items.push({
                    label: font.label,
                    style: 'font-family:' + stack,
                    action: function (ed) {
                        ed.exec('applyStyle', { prop: 'font-family', value: stack });
                    }
                });
            });
        }

        items.push({ head: t('font.system_head', 'Системные') });

        SYSTEM_FONTS.forEach(function (font) {
            items.push({
                label: font.label,
                style: 'font-family:' + font.value,
                action: function (ed) {
                    ed.exec('applyStyle', { prop: 'font-family', value: font.value });
                }
            });
        });

        return items;
    }

    RuEditor.registerButton('fontfamily', {
        type: 'menu',
        label: 'Шрифт',
        title: 'Гарнитура',
        items: fontItems
    });

    var SIZES = ['12px', '14px', '16px', '18px', '20px', '24px', '30px', '36px', '48px'];

    RuEditor.registerButton('fontsize', {
        type: 'menu',
        label: 'Кегль',
        title: 'Размер шрифта',
        items: function () {
            return [{ label: 'Как в тексте', action: function (editor) {
                editor.exec('clearStyle', { prop: 'font-size' });
            } }].concat(SIZES.map(function (size) {
                return {
                    label: size,
                    style: 'font-size:' + size,
                    action: function (editor) {
                        editor.exec('applyStyle', { prop: 'font-size', value: size });
                    }
                };
            }));
        }
    });

    /**
     * Применить встроенный стиль к выделению.
     *
     * execCommand('fontSize') умеет только семь ступеней и пишет устаревший
     * тег <font> — в разметке сайта ему делать нечего. Оборачиваем выделение
     * в span со стилем сами.
     */
    RuEditor.registerCommand('applyStyle', function (editor, options) {
        var range = editor.getRange();

        if (!range || range.collapsed) {
            return;
        }

        var span = editor.doc.createElement('span');

        span.style[toCamel(options.prop)] = options.value;

        try {
            span.appendChild(range.extractContents());
            range.insertNode(span);
            editor.selectNode(span);
        } catch (error) {
            // Выделение пересекает границы блоков — оборачиваем поштучно.
            editor.native('insertHTML',
                '<span style="' + options.prop + ':' + options.value + '">' +
                editor.getSelection().toString() + '</span>');
        }
    });

    RuEditor.registerCommand('clearStyle', function (editor, options) {
        var range = editor.getRange();

        if (!range) {
            return;
        }

        var box = editor.doc.createElement('div');

        box.appendChild(range.extractContents());

        Array.prototype.forEach.call(box.querySelectorAll('[style]'), function (node) {
            node.style[toCamel(options.prop)] = '';

            if (!node.getAttribute('style')) {
                node.removeAttribute('style');
            }
            // Пустой span без атрибутов только мусорит разметку.
            if (node.tagName === 'SPAN' && !node.attributes.length) {
                while (node.firstChild) {
                    node.parentNode.insertBefore(node.firstChild, node);
                }
                node.remove();
            }
        });

        range.insertNode(box.firstChild ? drain(box, editor.doc) : editor.doc.createTextNode(''));
    });

    function drain(box, doc) {
        var fragment = doc.createDocumentFragment();

        while (box.firstChild) {
            fragment.appendChild(box.firstChild);
        }

        return fragment;
    }

    function toCamel(prop) {
        return prop.replace(/-([a-z])/g, function (all, letter) {
            return letter.toUpperCase();
        });
    }

    /* ── Цвет ────────────────────────────────────────────────────────── */

    var PALETTE = [
        '#111827', '#374151', '#6b7280', '#9ca3af', '#d1d5db', '#ffffff',
        '#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#0891b2', '#2563eb',
        '#6366f1', '#7c3aed', '#c026d3', '#db2777', '#78350f', '#065f46'
    ];

    function colorButton(name, command, icon, title) {
        RuEditor.registerButton(name, {
            type: 'menu',
            icon: icon,
            title: title,
            render: function (editor, menu) {
                menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: t('color.pick', 'Цвет') }));

                var grid = el('div', {
                    style: 'display:grid;grid-template-columns:repeat(6,1fr);gap:4px;padding:4px'
                });

                PALETTE.forEach(function (color) {
                    var swatch = el('button', {
                        type: 'button',
                        title: color,
                        style: 'width:24px;height:24px;border:1px solid #d1d5db;cursor:pointer;background:' + color
                    });

                    swatch.addEventListener('mousedown', function (event) { event.preventDefault(); });
                    swatch.addEventListener('click', function () {
                        editor.closeMenus();
                        editor.exec(command, color);
                    });

                    grid.appendChild(swatch);
                });

                menu.appendChild(grid);

                var reset = el('button', {
                    type: 'button',
                    class: 'ru-ed-menu-item',
                    text: t('color.reset', 'Убрать цвет')
                });

                reset.addEventListener('mousedown', function (event) { event.preventDefault(); });
                reset.addEventListener('click', function () {
                    editor.closeMenus();
                    editor.exec('clearStyle', { prop: command === 'foreColor' ? 'color' : 'background-color' });
                });

                menu.appendChild(reset);
            }
        });
    }

    colorButton('forecolor', 'foreColor', 'fas fa-palette', 'Цвет текста');
    colorButton('backcolor', 'hiliteColor', 'fas fa-highlighter', 'Цвет фона');

    RuEditor.registerCommand('foreColor', function (editor, color) {
        editor.exec('applyStyle', { prop: 'color', value: color });
    });

    RuEditor.registerCommand('hiliteColor', function (editor, color) {
        editor.exec('applyStyle', { prop: 'background-color', value: color });
    });

    /* ── Выравнивание ────────────────────────────────────────────────── */

    [
        ['alignleft', 'justifyLeft', 'fas fa-align-left', 'По левому краю', 'left'],
        ['aligncenter', 'justifyCenter', 'fas fa-align-center', 'По центру', 'center'],
        ['alignright', 'justifyRight', 'fas fa-align-right', 'По правому краю', 'right'],
        ['alignjustify', 'justifyFull', 'fas fa-align-justify', 'По ширине', null]
    ].forEach(function (item) {
        RuEditor.registerButton(item[0], {
            icon: item[2],
            title: item[3],
            command: item[1],
            active: function (editor) { return editor.queryState(item[1]); }
        });

        // Своя команда поверх браузерной: выделена картинка, ролик или
        // проигрыватель — двигаем ЕГО, иначе выравнивается абзац.
        //
        // Раньше кнопки звали только execCommand, а он работает с текстом:
        // у картинки, ставшей блоком, text-align:center не центрует ничего, и
        // выравнивание молча не срабатывало на всех вставках сразу.
        RuEditor.registerCommand(item[1], function (editor) {
            if (item[4] && alignMedia(editor, item[4])) {
                return;
            }

            if (alignChip(editor, item[4])) {
                return;
            }

            editor.native(item[1]);
        });
    });

    function alignMedia(editor, mode) {
        var node = editor.selectedMedia;

        if (!node || !node.isConnected) {
            return false;
        }

        // У картинки с подписью и у проигрывателя двигаем обёртку целиком:
        // иначе подпись осталась бы на прежнем месте, а у звука сдвинулся бы
        // сам тег внутри своей рамки, а не рамка.
        var box = node.closest('figure, .pc-player') || node;

        box.style.float = '';
        box.style.display = '';
        box.style.marginLeft = '';
        box.style.marginRight = '';

        if (mode === 'center') {
            // Блок плюс авто-поля: единственный способ, который работает и
            // для картинки, и для видео, и для проигрывателя звука.
            box.style.display = 'block';
            box.style.marginLeft = 'auto';
            box.style.marginRight = 'auto';
        } else if (mode === 'left') {
            box.style.float = 'left';
            box.style.marginRight = '1rem';
        } else if (mode === 'right') {
            box.style.float = 'right';
            box.style.marginLeft = '1rem';
        }

        editor._snapshot();
        editor.save();

        return true;
    }

    /**
     * Выравнивание плашки шорткода — формы, каптчи, карты.
     *
     * Плашка не картинка: двигать её саму нельзя, у неё нет своей ширины.
     * Выравнивается АБЗАЦ, в котором она стоит. Браузерная команда этого не
     * делает: плашка помечена contenteditable="false", и execCommand на ней
     * не срабатывает — кнопки молча ничего не меняли.
     *
     * Если плашка лежит прямо в теле документа, без абзаца (так бывает после
     * вставки в пустой редактор), заворачиваем её в абзац: выравнивать иначе
     * нечего.
     */
    function alignChip(editor, mode) {
        var chip = editor.closest('[data-ru-shortcode]');

        if (!chip) {
            return false;
        }

        var block = chip.parentElement;

        if (!block || block === editor.body) {
            block = editor.doc.createElement('p');
            chip.parentNode.insertBefore(block, chip);
            block.appendChild(chip);
        }

        block.style.textAlign = mode || 'justify';

        editor._snapshot();
        editor.save();

        return true;
    }

    /* ── Списки и отступы ────────────────────────────────────────────── */

    RuEditor.registerButton('bullist', {
        icon: 'fas fa-list-ul',
        title: 'Маркированный список',
        command: 'insertUnorderedList',
        active: function (editor) { return editor.queryState('insertUnorderedList'); }
    });

    RuEditor.registerButton('numlist', {
        icon: 'fas fa-list-ol',
        title: 'Нумерованный список',
        command: 'insertOrderedList',
        active: function (editor) { return editor.queryState('insertOrderedList'); }
    });

    RuEditor.registerButton('outdent', {
        icon: 'fas fa-outdent',
        title: 'Уменьшить отступ',
        command: 'outdent'
    });

    RuEditor.registerButton('indent', {
        icon: 'fas fa-indent',
        title: 'Увеличить отступ',
        command: 'indent'
    });

    /* ── Ссылки ──────────────────────────────────────────────────────── */

    RuEditor.registerButton('link', {
        icon: 'fas fa-link',
        title: 'Ссылка (Ctrl+K)',
        action: function (editor) { editor.exec('link'); },
        active: function (editor) { return !!editor.closest('a'); }
    });

    RuEditor.registerButton('unlink', {
        icon: 'fas fa-link-slash',
        title: 'Убрать ссылку',
        command: 'unlink',
        enabled: function (editor) { return !!editor.closest('a'); }
    });

    RuEditor.registerCommand('link', function (editor) {
        var existing = editor.closest('a');
        var selected = editor.getSelection().toString();

        editor.saveSelection();

        RuEditor.dialog({
            title: t('link.title', 'Ссылка'),
            fields: [
                { name: 'href', label: t('link.href', 'Адрес'), type: 'text', value: existing ? existing.getAttribute('href') : '', required: true,
                  hint: t('link.hint', 'Внутренний путь (/contacts) или полный адрес (https://…)') },
                { name: 'text', label: t('link.text', 'Текст'), type: 'text', value: existing ? existing.textContent : selected },
                { name: 'blank', label: t('link.blank', 'Открывать в новой вкладке'), type: 'check',
                  value: existing ? existing.getAttribute('target') === '_blank' : false }
            ],
            onSubmit: function (values) {
                editor.restoreSelection();

                var href = values.href.trim();

                if (!href) {
                    return;
                }

                var text = (values.text || '').trim() || href;

                if (existing) {
                    existing.setAttribute('href', href);
                    existing.textContent = text;
                    setBlank(existing, values.blank);
                    editor._snapshot();
                    editor.save();
                    return;
                }

                var link = editor.doc.createElement('a');

                link.setAttribute('href', href);
                link.textContent = text;
                setBlank(link, values.blank);

                editor.exec('insertNodeCommand', link);
            }
        });
    });

    function setBlank(link, blank) {
        if (blank) {
            link.setAttribute('target', '_blank');
            // rel обязателен вместе с target: без него открытая вкладка
            // получает доступ к window.opener исходной страницы.
            link.setAttribute('rel', 'noopener');
        } else {
            link.removeAttribute('target');
            link.removeAttribute('rel');
        }
    }

    RuEditor.registerCommand('insertNodeCommand', function (editor, node) {
        editor.insertNode(node);
    });

    /* ── Плашка у ссылки ─────────────────────────────────────────────── */

    /**
     * Клик по ссылке показывает её адрес и три действия.
     *
     * Без этого чтобы увидеть, куда ведёт ссылка, приходилось открывать диалог
     * правки, а чтобы просто её проверить — сохранять материал и идти на сайт.
     * Плашка та же, что у картинки, поэтому поведение узнаётся сразу.
     */
    RuEditor.registerPlugin('link-tools', {
        init: function (editor) {
            var bubble = el('div', { class: 'ru-ed-bubble', hidden: true });
            var address = el('span', { class: 'ru-ed-bubble-url' });
            var current = null;

            bubble.appendChild(address);

            [
                ['fas fa-arrow-up-right-from-square', t('link.open', 'Открыть в новой вкладке'), function () {
                    window.open(current.getAttribute('href'), '_blank', 'noopener');
                }],
                ['fas fa-pen', t('link.edit', 'Изменить'), function () {
                    editor.selectNode(current);
                    editor.exec('link');
                }],
                ['fas fa-link-slash', t('link.remove', 'Убрать ссылку'), function () {
                    while (current.firstChild) {
                        current.parentNode.insertBefore(current.firstChild, current);
                    }
                    current.remove();
                    hide();
                    editor._snapshot();
                    editor.save();
                }]
            ].forEach(function (item) {
                var button = el('button', { type: 'button', title: item[1], html: '<i class="' + item[0] + '"></i>' });

                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', item[2]);
                bubble.appendChild(button);
            });

            editor.shell.appendChild(bubble);

            editor.doc.addEventListener('click', function (event) {
                var link = event.target.closest && event.target.closest('a');

                if (!link) {
                    hide();
                    return;
                }

                current = link;
                address.textContent = link.getAttribute('href') || '';
                place();
            });

            editor.doc.addEventListener('scroll', place, true);
            window.addEventListener('resize', place);

            function place() {
                if (!current || !current.isConnected) {
                    hide();
                    return;
                }

                var box = current.getBoundingClientRect();
                var frame = editor.frame.getBoundingClientRect();
                var shell = editor.shell.getBoundingClientRect();

                bubble.removeAttribute('hidden');
                // Плашка живёт в документе страницы, а ссылка — внутри рамки:
                // координаты переводим из одной системы в другую.
                bubble.style.top = Math.max(0, (frame.top - shell.top) + box.bottom + 6) + 'px';
                bubble.style.left = ((frame.left - shell.left) + box.left) + 'px';
            }

            function hide() {
                current = null;
                bubble.setAttribute('hidden', '');
            }
        }
    });

    /* ── Очистка оформления ──────────────────────────────────────────── */

    RuEditor.registerButton('removeformat', {
        icon: 'fas fa-eraser',
        title: 'Убрать оформление',
        command: 'removeFormat'
    });

    RuEditor.registerCommand('removeFormat', function (editor) {
        editor.native('removeFormat');
        // Родной removeFormat не трогает встроенные стили на span-ах, которые
        // мы же и ставим цветом и кеглем — снимаем их отдельно.
        var range = editor.getRange();

        if (!range || range.collapsed) {
            return;
        }

        var box = editor.doc.createElement('div');

        box.appendChild(range.extractContents());

        Array.prototype.forEach.call(box.querySelectorAll('[style]'), function (node) {
            node.removeAttribute('style');
        });

        Array.prototype.forEach.call(box.querySelectorAll('span:not([class])'), function (node) {
            while (node.firstChild) {
                node.parentNode.insertBefore(node.firstChild, node);
            }
            node.remove();
        });

        var fragment = editor.doc.createDocumentFragment();

        while (box.firstChild) {
            fragment.appendChild(box.firstChild);
        }

        range.insertNode(fragment);
    });
}(window));
