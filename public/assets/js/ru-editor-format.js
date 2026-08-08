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

        // Список начинается сразу со шрифтов. Строки «Шрифт сайта» и
        // заголовка над своими шрифтами убраны: первая дублировала кнопку
        // «Убрать оформление», второй нечего было отделять — до него в списке
        // ничего не было, и заголовок висел первой строкой.
        var items = [];

        if (own.length) {
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

    /**
     * Лесенка размеров — та же, что в текстовых редакторах: 8-9-10-11-12,
     * дальше через два, после 28 — крупные ступени. Она привычна и покрывает
     * всё от сноски до заголовка на пол-экрана.
     *
     * Пиксели, а не пункты: в них же показан текущий размер на кнопке, и
     * подпись со списком должны быть в ОДНИХ единицах — иначе «11px» на
     * панели и «11» в списке означали бы разное, а это ловушка.
     */
    var SIZES = [8, 9, 10, 11, 12, 14, 16, 18, 20, 22, 24, 26, 28, 36, 48, 72];

    /** Размер под курсором, в пикселях. */
    function currentSize(editor) {
        var node = editor.selectedNode();

        if (node && node.nodeType === 3) {
            node = node.parentElement;
        }

        // Пока курсор никуда не поставлен, «под курсором» ничего нет — но
        // пустая кнопка на панели выглядит сломанной. Берём размер самого
        // текста: именно им и будет набрано то, что начнут печатать.
        if (!node || !node.isConnected) {
            node = editor.body;
        }

        var view = editor.doc.defaultView;
        var px = parseFloat(view.getComputedStyle(node).fontSize);

        return px ? String(Math.round(px)) : '';
    }

    RuEditor.registerButton('fontsize', {
        type: 'menu',
        // Подпись — сам размер, без слова «Размер»: слово занимает место на
        // панели и не сообщает ничего сверх подсказки при наведении, а число
        // с единицей отвечает на вопрос «каким набран текст под курсором»
        // сразу, ещё до того как список открыли.
        label: '',
        title: t('size.title', 'Размер шрифта'),

        currentLabel: function (editor) {
            var size = currentSize(editor);

            return size ? size + 'px' : '';
        },


        render: function (editor, menu) {
            menu.classList.add('ru-ed-menu--sizes');

            var now = currentSize(editor);

            SIZES.forEach(function (pt) {
                // Числа набраны ОДНИМ кеглем, а не каждое своим. Список,
                // набранный «в натуральную величину», растягивается на пол-
                // экрана, а числа в нём выстраиваются лесенкой и хуже
                // читаются — сравнивать их глазами становится труднее.
                var item = el('button', {
                    type: 'button',
                    class: 'ru-ed-size' + (String(pt) === now ? ' is-active' : ''),
                    text: String(pt)
                });

                item.addEventListener('mousedown', function (event) { event.preventDefault(); });
                item.addEventListener('click', function () {
                    editor.closeMenus();
                    editor.exec('applyStyle', { prop: 'font-size', value: pt + 'px' });
                });

                menu.appendChild(item);
            });
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

    /** Снять свойство и убрать узел, если держать больше нечего. */
    function stripProp(node, prop) {
        node.style[prop] = '';

        if (!node.getAttribute('style')) {
            node.removeAttribute('style');
        }

        if (node.tagName === 'SPAN' && !node.attributes.length) {
            while (node.firstChild) {
                node.parentNode.insertBefore(node.firstChild, node);
            }

            node.remove();
        }
    }

    /**
     * Вынести узел из предка, разрезав предка на «до» и «после».
     *
     * Нужно, когда снимают оформление с ЧАСТИ оформленного куска: остальное
     * должно сохранить и цвет, и размер, поэтому предка не чистят, а делят.
     */
    function splitOut(node, ancestor, stop) {
        // Границу запоминаем ДО разреза. Опустевший предок удаляется, его
        // parentNode становится null, и проверка «дошли ли до места» больше
        // никогда не выполняется: разрез уходит вверх по документу и сносит
        // тело рамки — редактор остаётся без содержимого.
        var limit = ancestor.parentNode;

        while (node.parentNode && node.parentNode !== limit && node.parentNode !== stop) {
            var parent = node.parentNode;
            var after = parent.cloneNode(false);
            var sibling = node.nextSibling;

            while (sibling) {
                var next = sibling.nextSibling;

                after.appendChild(sibling);
                sibling = next;
            }

            parent.parentNode.insertBefore(node, parent.nextSibling);

            if (after.childNodes.length) {
                parent.parentNode.insertBefore(after, node.nextSibling);
            }

            // Тело рамки не трогаем ни при каких условиях: пустое оно
            // законно, а его удаление — конец редактирования.
            if (!parent.childNodes.length && parent !== stop) {
                parent.remove();
            }
        }
    }

    /**
     * Снять оформление с выделения.
     *
     * Прежняя версия чистила только то, что лежало ВНУТРИ выделения, — а
     * цвет и размер обычно висят на объемлющем span, который снаружи.
     * Поэтому «убрать цвет» не делало ровно ничего в самом обычном случае:
     * выделили окрашенные слова и нажали сброс.
     *
     * Теперь выделение выносится из-под каждого оформленного предка. Предка
     * при этом не чистим: остальной текст в нём должен сохранить свой вид.
     */
    RuEditor.registerCommand('clearStyle', function (editor, options) {
        var range = editor.getRange();

        if (!range || range.collapsed) {
            return;
        }

        var prop = toCamel(options.prop);
        var marker = editor.doc.createElement('span');

        marker.appendChild(range.extractContents());

        Array.prototype.forEach.call(marker.querySelectorAll('[style]'), function (node) {
            stripProp(node, prop);
        });

        range.insertNode(marker);

        var ancestor = marker.parentElement;

        while (ancestor && ancestor !== editor.body) {
            var next = ancestor.parentElement;

            if (ancestor.style && ancestor.style[prop]) {
                // Остальное оформление предка переносим на выделение. Иначе
                // «убрать цвет» у куска, набранного цветным И крупным, уносило
                // бы заодно и размер: выделение выходит из-под предка, а всё,
                // что тот задавал, остаётся за его пределами.
                Array.prototype.forEach.call(ancestor.style, function (name) {
                    if (name !== options.prop && !marker.style.getPropertyValue(name)) {
                        marker.style.setProperty(name, ancestor.style.getPropertyValue(name));
                    }
                });

                splitOut(marker, ancestor, editor.body);

                // Опустевшая половина разреза остаётся мусором в разметке.
                if (!ancestor.childNodes.length && ancestor !== editor.body) {
                    ancestor.remove();
                }
            }

            ancestor = next;
        }

        // Пустые остатки разреза убираем: у куска, окрашенного целиком, по
        // краям оставались span без содержимого — в разметке мусор, а на
        // экране их не видно, и заметить это можно только в исходном коде.
        Array.prototype.forEach.call(editor.body.querySelectorAll('span[style]'), function (node) {
            // Считаем по СОДЕРЖИМОМУ, а не по числу узлов: после разреза
            // внутри остаётся пустой текстовый узел, и проверка на «нет
            // детей» такой остаток пропускала. Картинку или перенос строки
            // при этом не трогаем — текста в них тоже нет.
            if (!node.textContent && !node.firstElementChild) {
                node.remove();
            }
        });

        // Маркер мог набрать оформление предков — тогда он остаётся как есть.
        if (!marker.getAttribute('style')) {
            marker.removeAttribute('style');
        }

        if (marker.attributes.length) {
            editor._snapshot();
            editor.save();

            return;
        }

        // Ничего своего не несёт — разворачиваем, оставляя содержимое.
        var caret = editor.doc.createRange();

        caret.setStartBefore(marker.firstChild || marker);
        caret.setEndAfter(marker.lastChild || marker);

        while (marker.firstChild) {
            marker.parentNode.insertBefore(marker.firstChild, marker);
        }

        marker.remove();
        editor.setRange(caret);
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

    /**
     * Палитра собрана по образцу привычного редактора документов: сверху
     * основные цвета оформления с осветлёнными и затемнёнными оттенками,
     * ниже — набор простых цветов, отдельной строкой сброс и выбор своего.
     *
     * Почему не произвольная сетка из восемнадцати образцов, как было
     * раньше: там не было ни оттенков одного цвета, ни «убрать», ни
     * возможности взять цвет, которого в списке нет.
     */
    var THEME = ['#ffffff', '#000000', '#e7e6e6', '#44546a', '#4472c4',
                 '#ed7d31', '#a5a5a5', '#ffc000', '#5b9bd5', '#70ad47'];

    var STANDARD = ['#c00000', '#ff0000', '#ffc000', '#ffff00', '#92d050',
                    '#00b050', '#00b0f0', '#0070c0', '#002060', '#7030a0'];

    /** Цвета выделения — те же, что даёт маркер в редакторе документов. */
    var MARKERS = ['#ffff00', '#00ff00', '#00ffff', '#ff00ff', '#0000ff',
                   '#ff0000', '#000080', '#008080', '#008000', '#800080',
                   '#800000', '#808000', '#c0c0c0', '#808080', '#000000'];

    /** Осветление и затемнение — оттенки одного цвета в столбце. */
    function shade(hex, amount) {
        var to = amount > 0 ? 255 : 0;
        var k = Math.abs(amount);
        var n = parseInt(hex.slice(1), 16);
        var mix = function (channel) {
            return Math.round(channel + (to - channel) * k);
        };

        return '#' + [mix(n >> 16 & 255), mix(n >> 8 & 255), mix(n & 255)]
            .map(function (v) { return ('0' + v.toString(16)).slice(-2); })
            .join('');
    }

    /** Запомненный цвет рисуется полоской под значком кнопки. */
    function rememberColor(editor, name, color) {
        var button = editor.root.querySelector('[data-ru-btn="' + name + '"]');

        if (button) {
            button.style.setProperty('--ru-ink', color);
        }
    }

    function swatch(editor, name, command, color, title) {
        var box = el('button', {
            type: 'button',
            class: 'ru-ed-swatch',
            title: title || color,
            style: 'background:' + color
        });

        box.addEventListener('mousedown', function (event) { event.preventDefault(); });
        box.addEventListener('click', function () {
            editor.closeMenus();
            rememberColor(editor, name, color);
            editor.exec(command, color);
        });

        return box;
    }

    function colorButton(name, command, icon, title, marker) {
        RuEditor.registerButton(name, {
            type: 'menu',
            icon: icon,
            title: title,
            render: function (editor, menu) {
                menu.classList.add('ru-ed-menu--colors');

                var prop = command === 'foreColor' ? 'color' : 'background-color';

                var reset = el('button', {
                    type: 'button',
                    class: 'ru-ed-menu-item',
                    text: marker ? t('color.none', 'Нет цвета') : t('color.auto', 'Цвет по умолчанию')
                });

                reset.addEventListener('mousedown', function (event) { event.preventDefault(); });
                reset.addEventListener('click', function () {
                    editor.closeMenus();
                    editor.exec('clearStyle', { prop: prop });
                });

                menu.appendChild(reset);

                if (marker) {
                    menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: t('color.markers', 'Выделение') }));

                    var strip = el('div', { class: 'ru-ed-grid', style: 'grid-template-columns:repeat(5,1fr)' });

                    MARKERS.forEach(function (color) {
                        strip.appendChild(swatch(editor, name, command, color));
                    });

                    menu.appendChild(strip);
                } else {
                    menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: t('color.theme', 'Цвета оформления') }));

                    var grid = el('div', { class: 'ru-ed-grid' });

                    // Столбец — один цвет: сам он и четыре его оттенка. Так
                    // подобрать «то же, но светлее» можно не подбирая заново.
                    [0, 0.8, 0.6, -0.25, -0.5].forEach(function (amount) {
                        THEME.forEach(function (base) {
                            grid.appendChild(swatch(editor, name, command, amount ? shade(base, amount) : base));
                        });
                    });

                    menu.appendChild(grid);
                    menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: t('color.standard', 'Обычные цвета') }));

                    var row = el('div', { class: 'ru-ed-grid' });

                    STANDARD.forEach(function (color) {
                        row.appendChild(swatch(editor, name, command, color));
                    });

                    menu.appendChild(row);
                }

                // Свой цвет. Без него палитра — это потолок: нужного оттенка
                // в ней может просто не оказаться.
                var more = el('label', { class: 'ru-ed-menu-item', text: t('color.more', 'Другой цвет…') });
                var input = el('input', { type: 'color', class: 'ru-ed-color-input' });

                input.addEventListener('input', function () {
                    rememberColor(editor, name, input.value);
                    editor.exec(command, input.value);
                });

                input.addEventListener('change', function () {
                    editor.closeMenus();
                });

                more.appendChild(input);
                menu.appendChild(more);
            }
        });
    }

    colorButton('forecolor', 'foreColor', 'fas fa-font', 'Цвет текста', false);
    colorButton('backcolor', 'hiliteColor', 'fas fa-highlighter', 'Цвет выделения текста', true);

    RuEditor.registerCommand('foreColor', function (editor, color) {
        editor.exec('applyStyle', { prop: 'color', value: color });
    });

    RuEditor.registerCommand('hiliteColor', function (editor, color) {
        editor.exec('applyStyle', { prop: 'background-color', value: color });
    });

    /* ── Выравнивание ────────────────────────────────────────────────── */

    // Вся работа — в общем слое объектов (ru-editor-objects.js). Здесь только
    // кнопки. Раньше выравнивание было размазано по трём файлам с разными
    // наборами исключений, и «работает у картинки, но не у видео» было нормой.
    [
        ['alignleft', 'justifyLeft', 'fas fa-align-left', 'По левому краю', 'left'],
        ['aligncenter', 'justifyCenter', 'fas fa-align-center', 'По центру', 'center'],
        ['alignright', 'justifyRight', 'fas fa-align-right', 'По правому краю', 'right'],
        ['alignjustify', 'justifyFull', 'fas fa-align-justify', 'По ширине', 'justify']
    ].forEach(function (item) {
        RuEditor.registerButton(item[0], {
            icon: item[2],
            title: item[3],
            command: item[1],
            active: function (editor) { return RuEditor.objects.alignState(editor) === item[4]; }
        });

        RuEditor.registerCommand(item[1], function (editor) {
            if (!RuEditor.objects.align(editor, item[4])) {
                editor.native(item[1]);
            }
        });
    });

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
