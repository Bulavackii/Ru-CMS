/* ============================================================================
   RU Editor — таблицы, готовые блоки, шорткоды и каптча.
   ----------------------------------------------------------------------------
   Заготовки блоков (pc-*) те же, что оформляет content-blocks.css: один файл
   на сайт и на редактор, поэтому вставленный блок сразу выглядит так, как его
   увидит посетитель.

   Шорткоды показываются в тексте плашкой, а хранятся обычным текстом
   [captcha preset="…"] — раскрывает их render_shortcodes() при выводе
   материала. Двустороннее превращение живёт в ядре (setContent/cleanOutput):
   иначе в базу уехала бы разметка плашки, а не сам шорткод.
   ========================================================================= */

(function (window) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /* ── Таблицы ─────────────────────────────────────────────────────── */

    RuEditor.registerButton('table', {
        type: 'menu',
        icon: 'fas fa-table',
        title: 'Таблица',
        /**
         * Размер выбирается наведением по сетке, а не вводом двух чисел.
         * Числа требуют представить результат в уме; сетка показывает его
         * сразу — так это устроено во всех редакторах текста, и людям не
         * приходится переучиваться.
         */
        render: function (editor, menu) {
            if (!editor.closest('table')) {
                menu.appendChild(sizeGrid(editor));
                menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: t('table.more', 'Ещё') }));
            }

            menuItems(editor).forEach(function (item) {
                if (item.head) {
                    menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: item.head }));
                    return;
                }

                var node = el('button', { type: 'button', class: 'ru-ed-menu-item' }, [
                    item.icon ? el('i', { class: item.icon, 'aria-hidden': 'true' }) : null,
                    el('span', { text: item.label })
                ]);

                node.addEventListener('mousedown', function (event) { event.preventDefault(); });
                node.addEventListener('click', function () {
                    editor.closeMenus();
                    item.action(editor);
                });

                menu.appendChild(node);
            });
        },
        items: function (editor) {
            return menuItems(editor);
        }
    });

    function menuItems(editor) {
        {
            var inside = !!editor.closest('table');

            var list = [
                { label: t('table.insert', 'Вставить таблицу'), icon: 'fas fa-plus',
                  action: function (ed) { ed.exec('insertTable'); } }
            ];

            if (!inside) {
                return list;
            }

            return list.concat([
                { head: t('table.rows', 'Строки') },
                { label: t('table.row_before', 'Добавить строку выше'), action: function (ed) { ed.exec('tableRow', 'before'); } },
                { label: t('table.row_after', 'Добавить строку ниже'), action: function (ed) { ed.exec('tableRow', 'after'); } },
                { label: t('table.row_delete', 'Удалить строку'), action: function (ed) { ed.exec('tableRow', 'delete'); } },
                { head: t('table.cols', 'Столбцы') },
                { label: t('table.col_before', 'Добавить столбец слева'), action: function (ed) { ed.exec('tableCol', 'before'); } },
                { label: t('table.col_after', 'Добавить столбец справа'), action: function (ed) { ed.exec('tableCol', 'after'); } },
                { label: t('table.col_delete', 'Удалить столбец'), action: function (ed) { ed.exec('tableCol', 'delete'); } },
                { head: t('table.whole', 'Вся таблица') },
                { label: t('table.header', 'Шапка вкл/выкл'), action: function (ed) { ed.exec('tableHeader'); } },
                { label: t('table.delete', 'Удалить таблицу'), icon: 'fas fa-trash',
                  action: function (ed) { ed.exec('tableDelete'); } }
            ]);
        }
    }

    /** Сетка 8x8: наводишь — видишь будущий размер, щёлкаешь — вставляется. */
    function sizeGrid(editor) {
        var box = el('div', {});
        var grid = el('div', { class: 'ru-ed-tgrid' });
        var label = el('div', { class: 'ru-ed-tgrid-label', text: t('table.pick_size', 'Размер таблицы') });
        var cells = [];

        for (var r = 1; r <= 8; r++) {
            for (var c = 1; c <= 8; c++) {
                var cell = el('i', { 'data-r': r, 'data-c': c });

                cell.addEventListener('mouseenter', mark);
                cell.addEventListener('mousedown', function (event) { event.preventDefault(); });
                cell.addEventListener('click', pick);
                cells.push(cell);
                grid.appendChild(cell);
            }
        }

        function mark(event) {
            var r = +event.target.getAttribute('data-r');
            var c = +event.target.getAttribute('data-c');

            cells.forEach(function (cell) {
                cell.classList.toggle('is-on',
                    +cell.getAttribute('data-r') <= r && +cell.getAttribute('data-c') <= c);
            });

            label.textContent = c + ' x ' + r;
        }

        function pick(event) {
            var r = +event.target.getAttribute('data-r');
            var c = +event.target.getAttribute('data-c');

            editor.closeMenus();
            editor.exec('buildTable', { rows: r, cols: c, header: true });
        }

        grid.addEventListener('mouseleave', function () {
            cells.forEach(function (cell) { cell.classList.remove('is-on'); });
            label.textContent = t('table.pick_size', 'Размер таблицы');
        });

        box.appendChild(grid);
        box.appendChild(label);

        return box;
    }

    RuEditor.registerCommand('insertTable', function (editor) {
        editor.saveSelection();

        RuEditor.dialog({
            title: t('table.insert', 'Вставить таблицу'),
            fields: [
                {
                    type: 'row',
                    fields: [
                        { name: 'rows', label: t('table.rows', 'Строки'), type: 'number', value: 3, min: 1, max: 50 },
                        { name: 'cols', label: t('table.cols', 'Столбцы'), type: 'number', value: 3, min: 1, max: 20 }
                    ]
                },
                { name: 'header', label: t('table.with_header', 'Первая строка — шапка'), type: 'check', value: true }
            ],
            onSubmit: function (values) {
                editor.restoreSelection();
                editor.exec('buildTable', {
                    rows: parseInt(values.rows, 10),
                    cols: parseInt(values.cols, 10),
                    header: values.header
                });
            }
        });
    });

    /** Сама вставка. Общая для сетки и для диалога — разметка одна. */
    RuEditor.registerCommand('buildTable', function (editor, spec) {
        var rows = Math.max(1, Math.min(50, spec.rows || 3));
        var cols = Math.max(1, Math.min(20, spec.cols || 3));
        var html = '<table>';

        if (spec.header) {
            html += '<thead><tr>' + repeat('<th>&nbsp;</th>', cols) + '</tr></thead>';
            rows--;
        }

        html += '<tbody>';

        for (var r = 0; r < rows; r++) {
            html += '<tr>' + repeat('<td>&nbsp;</td>', cols) + '</tr>';
        }

        // Пустой абзац следом: без него ниже таблицы некуда поставить курсор,
        // если она оказалась последним блоком материала.
        html += '</tbody></table><p><br></p>';

        editor.insertHtml(html);
    });

    function repeat(chunk, times) {
        var out = '';

        for (var i = 0; i < times; i++) {
            out += chunk;
        }

        return out;
    }

    function cellOf(editor) {
        return editor.closest('td,th');
    }

    RuEditor.registerCommand('tableRow', function (editor, mode) {
        var cell = cellOf(editor);

        if (!cell) {
            return;
        }

        var row = cell.parentNode;

        if (mode === 'delete') {
            var table = row.closest('table');

            row.remove();

            // Таблица без единой строки — мусор в разметке: убираем целиком.
            if (!table.querySelector('tr')) {
                table.remove();
            }
            return;
        }

        var copy = row.cloneNode(true);

        Array.prototype.forEach.call(copy.children, function (node) {
            // Копия строки нужна ради структуры, а не содержимого: новая
            // строка должна быть пустой, иначе автор удаляет текст руками.
            node.innerHTML = '&nbsp;';

            // Дубль шапки строкой ниже был бы второй шапкой — делаем обычной.
            if (node.tagName === 'TH' && mode === 'after') {
                var td = editor.doc.createElement('td');

                td.innerHTML = '&nbsp;';
                node.parentNode.replaceChild(td, node);
            }
        });

        row.parentNode.insertBefore(copy, mode === 'before' ? row : row.nextSibling);
    });

    RuEditor.registerCommand('tableCol', function (editor, mode) {
        var cell = cellOf(editor);

        if (!cell) {
            return;
        }

        var index = Array.prototype.indexOf.call(cell.parentNode.children, cell);
        var table = cell.closest('table');

        Array.prototype.forEach.call(table.querySelectorAll('tr'), function (row) {
            var target = row.children[index];

            if (!target) {
                return;
            }

            if (mode === 'delete') {
                target.remove();
                return;
            }

            var fresh = editor.doc.createElement(target.tagName);

            fresh.innerHTML = '&nbsp;';
            row.insertBefore(fresh, mode === 'before' ? target : target.nextSibling);
        });

        if (mode === 'delete' && !table.querySelector('td,th')) {
            table.remove();
        }
    });

    RuEditor.registerCommand('tableHeader', function (editor) {
        var cell = cellOf(editor);

        if (!cell) {
            return;
        }

        var table = cell.closest('table');
        var head = table.querySelector('thead');

        if (head) {
            // Убираем шапку: строка не должна исчезнуть вместе с данными,
            // поэтому переносим её в тело обычными ячейками.
            var body = table.querySelector('tbody') || table;

            Array.prototype.slice.call(head.rows).forEach(function (row) {
                Array.prototype.slice.call(row.cells).forEach(function (th) {
                    var td = editor.doc.createElement('td');

                    td.innerHTML = th.innerHTML;
                    th.parentNode.replaceChild(td, th);
                });

                body.insertBefore(row, body.firstChild);
            });

            head.remove();
            return;
        }

        var first = table.querySelector('tr');

        if (!first) {
            return;
        }

        var thead = editor.doc.createElement('thead');

        Array.prototype.slice.call(first.cells).forEach(function (td) {
            var th = editor.doc.createElement('th');

            th.innerHTML = td.innerHTML;
            td.parentNode.replaceChild(th, td);
        });

        thead.appendChild(first);
        table.insertBefore(thead, table.firstChild);
    });

    RuEditor.registerCommand('tableDelete', function (editor) {
        var cell = cellOf(editor);

        if (cell) {
            cell.closest('table').remove();
        }
    });

    /* Tab внутри таблицы ходит по ячейкам — иначе фокус улетает из формы. */
    RuEditor.registerPlugin('table-keys', {
        init: function (editor) {
            editor.on('tab', function (event) {
                var cell = cellOf(editor);

                if (!cell) {
                    return;
                }

                event.preventDefault();

                var cells = Array.prototype.slice.call(cell.closest('table').querySelectorAll('td,th'));
                var next = cells[cells.indexOf(cell) + (event.shiftKey ? -1 : 1)];

                if (next) {
                    var range = editor.doc.createRange();

                    range.selectNodeContents(next);
                    range.collapse(true);
                    editor.setRange(range);
                }
            });
        }
    });

    /* ── Готовые блоки оформления ────────────────────────────────────── */

    var CARD = '<div class="pc-card"><span class="pc-ico"><i class="fas fa-bolt"></i></span>' +
               '<h3>Заголовок карточки</h3><p>Короткое пояснение в одну-две строки.</p></div>';

    var BLOCKS = [
        {
            key: 'lead', label: 'Вводный абзац', icon: 'fas fa-paragraph',
            html: '<p class="pc-lead">Короткое вступление, которое задаёт тон всему материалу.</p>'
        },
        {
            key: 'grid', label: 'Сетка карточек', icon: 'fas fa-table-cells',
            html: '<div class="pc-grid">' + CARD + CARD + CARD + '</div>'
        },
        {
            key: 'check', label: 'Список с галочками', icon: 'fas fa-list-check',
            html: '<ul class="pc-check"><li>Первый пункт</li><li>Второй пункт</li><li>Третий пункт</li></ul>'
        },
        {
            key: 'stats', label: 'Строка цифр', icon: 'fas fa-chart-simple',
            html: '<ul class="pc-stats">' +
                  '<li><span>Заказов</span><strong>1 200</strong></li>' +
                  '<li><span>Клиентов</span><strong>340</strong></li>' +
                  '<li><span>Лет в работе</span><strong>12</strong></li></ul>'
        },
        {
            key: 'steps', label: 'Нумерованные шаги', icon: 'fas fa-list-ol',
            html: '<ol class="pc-steps"><li><strong>Заявка.</strong> Что происходит на первом шаге.</li>' +
                  '<li><strong>Расчёт.</strong> Что происходит дальше.</li>' +
                  '<li><strong>Работа.</strong> Чем всё заканчивается.</li></ol>'
        },
        {
            key: 'faq', label: 'Вопросы и ответы', icon: 'fas fa-circle-question',
            html: '<div class="pc-faq"><details class="pc-faq__item"><summary>Первый вопрос?</summary>' +
                  '<p>Ответ на первый вопрос.</p></details>' +
                  '<details class="pc-faq__item"><summary>Второй вопрос?</summary>' +
                  '<p>Ответ на второй вопрос.</p></details></div>'
        },
        {
            key: 'note', label: 'Врезка-примечание', icon: 'fas fa-circle-info',
            html: '<p class="pc-note">Важное замечание, которое нельзя пропустить.</p>'
        },
        {
            key: 'split', label: 'Текст и картинка', icon: 'fas fa-image',
            html: '<div class="pc-split"><div><h3>Заголовок раздела</h3>' +
                  '<p>Пояснение к тому, что показано рядом.</p></div>' +
                  '<div><img src="/images/placeholder.png" alt="" loading="lazy"></div></div>'
        },
        {
            key: 'tech', label: 'Чипы технологий', icon: 'fas fa-tags',
            html: '<ul class="pc-tech"><li>PHP</li><li>Laravel</li><li>PostgreSQL</li><li>Tailwind</li></ul>'
        },
        {
            key: 'cta', label: 'Полоса призыва', icon: 'fas fa-bullhorn',
            html: '<div class="pc-cta"><h3>Готовы начать?</h3><p>Короткая фраза о выгоде.</p>' +
                  '<p><a href="/contacts">Связаться с нами</a></p></div>'
        },
        {
            key: 'hr', label: 'Разделительная линия', icon: 'fas fa-minus',
            html: '<hr>'
        }
    ];

    RuEditor.registerButton('ruBlocks', {
        type: 'menu',
        icon: 'fas fa-shapes',
        label: 'Блоки',
        title: 'Готовые блоки оформления',
        items: function () {
            return BLOCKS.map(function (block) {
                return {
                    label: t('block.' + block.key, block.label),
                    icon: block.icon,
                    action: function (editor) {
                        // Пустой абзац следом: без него курсор остаётся внутри
                        // блока и следующий текст уезжает в его разметку.
                        editor.insertHtml(block.html + '<p><br></p>');
                    }
                };
            });
        }
    });

    /* ── Спецсимволы ─────────────────────────────────────────────────── */

    var CHARS = ['«', '»', '„', '“', '—', '–', '…', '№', '§', '©', '®', '™',
                 '°', '±', '×', '÷', '≈', '≤', '≥', '→', '←', '↑', '↓', '€', '₽', '$'];

    RuEditor.registerButton('charmap', {
        type: 'menu',
        icon: 'fas fa-omega',
        title: 'Спецсимволы',
        render: function (editor, menu) {
            var grid = el('div', {
                style: 'display:grid;grid-template-columns:repeat(7,1fr);gap:2px;padding:4px'
            });

            CHARS.forEach(function (char) {
                var button = el('button', {
                    type: 'button',
                    text: char,
                    style: 'width:28px;height:28px;font-size:15px;background:none;border:1px solid #e5e7eb;cursor:pointer'
                });

                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', function () {
                    editor.closeMenus();
                    editor.exec('insertChar', char);
                });

                grid.appendChild(button);
            });

            menu.appendChild(grid);
        }
    });

    RuEditor.registerCommand('insertChar', function (editor, char) {
        editor.insertText(char);
    });

    /* ── Каптча: вставка сохранённой сборки ──────────────────────────── */

    /**
     * Кнопка появляется, только если сборки есть. Пустой список ничего не
     * объясняет — вместо него пункт со ссылкой в конструктор.
     *
     * Прежний выбор жил ОТДЕЛЬНЫМ выпадающим списком под полем содержимого:
     * он не был частью редактора, не понимал, куда стоит курсор, и вставлял
     * шорткод «куда получится». Теперь это обычная кнопка панели.
     */
    RuEditor.registerButton('captcha', {
        type: 'menu',
        icon: 'fas fa-shield-halved',
        title: 'Каптча',
        items: function (editor) {
            var presets = editor.options.captchaPresets || [];

            if (!presets.length) {
                return [{
                    label: t('captcha.none', 'Сборок каптчи пока нет'),
                    hint: t('captcha.build', 'создать'),
                    action: function () {
                        if (editor.options.captchaUrl) {
                            window.open(editor.options.captchaUrl, '_blank');
                        }
                    }
                }];
            }

            var items = [{ head: t('captcha.pick', 'Вставить сборку') }];

            presets.forEach(function (preset) {
                items.push({
                    label: preset.name,
                    hint: preset.type || '',
                    action: function (ed) {
                        ed.exec('insertShortcode', 'captcha preset="' + preset.slug + '"');
                    }
                });
            });

            if (editor.options.captchaUrl) {
                items.push({
                    label: t('captcha.manage', 'Конструктор сборок'),
                    icon: 'fas fa-arrow-up-right-from-square',
                    action: function () {
                        window.open(editor.options.captchaUrl, '_blank');
                    }
                });
            }

            return items;
        }
    });

    /**
     * Шорткод показывается плашкой, а хранится текстом. Плашка нужна, чтобы
     * автор видел вставку и мог её удалить одним нажатием: обычный текст
     * [captcha preset="x"] посреди абзаца легко принять за опечатку.
     */
    RuEditor.registerCommand('insertShortcode', function (editor, code) {
        var chip = editor.doc.createElement('span');

        chip.setAttribute('data-ru-shortcode', '[' + code + ']');
        chip.setAttribute('contenteditable', 'false');
        chip.textContent = '[' + code + ']';

        editor.insertNode(chip);
        editor.insertHtml('&nbsp;');
    });
}(window));
