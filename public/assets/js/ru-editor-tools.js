/* ============================================================================
   RU Editor — режимы работы: полный экран, исходный код, предпросмотр,
   поиск с заменой, автосохранение, подсказка по клавишам.
   ----------------------------------------------------------------------------
   Полноэкранный режим здесь свой, а не «плагин за деньги»: у прежнего
   редактора он был в платном наборе, из-за чего длинные материалы верстали в
   окошке высотой 500 пикселей.
   ========================================================================= */

(function (window, document) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /* ── Развернуть на высоту экрана ─────────────────────────────────── */

    /*
     * Здесь было «настоящее» полноэкранное перекрытие: position:fixed на весь
     * экран, запрет прокрутки страницы, изоляция слоя. У владельца оно
     * стабильно давало рябь по всему экрану, причём и с выключенным
     * аппаратным ускорением. Воспроизвести не удалось: три попытки чинить
     * (убрать слои с размытием фона, изолировать слой, вынести узел в body)
     * результата не дали, а последняя вдобавок стирала содержимое — перенос
     * узла с <iframe> перезагружает фрейм.
     *
     * Поэтому механизм заменён, а не подлатан. Ценность режима — вертикальное
     * место под длинный материал, и её даёт обычная вёрстка: рамка
     * растягивается на высоту окна ПРЯМО НА СТРАНИЦЕ. Ни перекрытия, ни
     * фиксированного позиционирования, ни запрета прокрутки, ни слоёв — рябить
     * попросту нечему.
     */

    RuEditor.registerButton('fullscreen', {
        icon: 'fas fa-up-right-and-down-left-from-center',
        title: 'Развернуть на высоту экрана (Esc — свернуть)',
        action: function (editor) { editor.exec('fullscreen'); },
        active: function (editor) { return editor.fullscreen; }
    });

    RuEditor.registerCommand('fullscreen', function (editor) {
        editor.fullscreen = !editor.fullscreen;
        editor.root.classList.toggle('is-tall', editor.fullscreen);

        if (editor.fullscreen) {
            editor._heightBefore = editor.frame.style.height;
            fitFrame(editor);

            editor._fitFrame = function () { fitFrame(editor); };
            window.addEventListener('resize', editor._fitFrame);

            // Esc сворачивает откуда угодно: обработчик в ядре висит на
            // документе рамки и работает, только пока курсор в тексте.
            editor._escapeTall = function (event) {
                if (event.key === 'Escape' && editor.fullscreen && !document.querySelector('.ru-ed-dialog-back')) {
                    event.preventDefault();
                    editor.exec('fullscreen');
                }
            };

            document.addEventListener('keydown', editor._escapeTall);

            // Подводим редактор к верху окна, иначе развёрнутая рамка уходит
            // за нижний край и полезная высота теряется.
            editor.root.scrollIntoView({ block: 'start', behavior: 'smooth' });
        } else {
            editor.frame.style.height = editor._heightBefore || '420px';
            resetWidth(editor);

            if (editor._fitFrame) {
                window.removeEventListener('resize', editor._fitFrame);
                editor._fitFrame = null;
            }

            if (editor._escapeTall) {
                document.removeEventListener('keydown', editor._escapeTall);
                editor._escapeTall = null;
            }
        }

        var icon = editor.root.querySelector('[data-ru-btn="fullscreen"] i');

        if (icon) {
            icon.className = editor.fullscreen
                ? 'fas fa-down-left-and-up-right-to-center'
                : 'fas fa-up-right-and-down-left-from-center';
        }

        editor.focus();
    });

    /**
     * Размеры развёрнутого вида: высота — по окну, ширина — во всё свободное
     * место страницы.
     *
     * Вширь растягиваем отрицательными полями, то есть обычной вёрсткой:
     * редактор остаётся в потоке, ничего не перекрывает и слоёв не создаёт.
     * Раньше он рос только по высоте и оставался в колонке содержимого, а
     * запас по бокам простаивал.
     */
    function fitFrame(editor) {
        var chrome = editor.toolbar.offsetHeight +
                     (editor.status && editor.status.offsetParent !== null ? editor.status.offsetHeight : 0);

        editor.frame.style.height = Math.max(240, window.innerHeight - chrome - 24) + 'px';

        // Поля сбрасываем ДО замера: иначе мерили бы уже растянутый вид, и с
        // каждым пересчётом он уезжал бы всё дальше.
        resetWidth(editor);

        var rect = editor.root.getBoundingClientRect();
        var bounds = freeBounds(editor);

        editor.root.style.marginLeft = Math.min(0, bounds.left - rect.left) + 'px';
        editor.root.style.marginRight = Math.min(0, rect.right - bounds.right) + 'px';
    }

    /**
     * Докуда можно растянуться вбок, не заехав под закреплённые полосы.
     *
     * В панели слева висит боковое меню с position:fixed — оно нарисовано
     * поверх содержимого, и уехавший под него редактор потерял бы левый край
     * панели инструментов. Ищем такие полосы по вычисленному стилю, а не по
     * именам классов: редактор не должен знать разметку панели и обязан так же
     * вести себя на сайте.
     *
     * Помехой считаем только то, что занимает больше половины высоты окна и
     * прижато к краю: шапка и подвал по бокам не мешают.
     */
    function freeBounds(editor) {
        var width = document.documentElement.clientWidth;
        var bounds = { left: 0, right: width };

        Array.prototype.forEach.call(document.body.querySelectorAll('*'), function (node) {
            if (editor.root.contains(node) || node.contains(editor.root)) {
                return;
            }

            var style = window.getComputedStyle(node);

            if (style.position !== 'fixed' || style.display === 'none' || style.visibility === 'hidden') {
                return;
            }

            var box = node.getBoundingClientRect();

            if (box.width <= 0 || box.height < window.innerHeight / 2) {
                return;
            }

            if (box.left <= 0 && box.right > bounds.left && box.right < width / 2) {
                bounds.left = box.right;
            }

            if (box.right >= width && box.left < bounds.right && box.left > width / 2) {
                bounds.right = box.left;
            }
        });

        return bounds;
    }

    function resetWidth(editor) {
        editor.root.style.marginLeft = '';
        editor.root.style.marginRight = '';
    }

    /* ── Правка исходного кода ───────────────────────────────────────── */

    RuEditor.registerButton('code', {
        icon: 'fas fa-code',
        title: 'Исходный код',
        action: function (editor) { editor.exec('code'); },
        active: function (editor) { return editor.sourceMode; }
    });

    RuEditor.registerCommand('code', function (editor) {
        if (!editor.sourceMode) {
            // Содержимое снимаем ДО переключения флага: getContent() в режиме
            // кода отдаёт значение самого поля с кодом, и при обратном порядке
            // исходник заполнялся бы сам собой — то есть тем, что в нём уже
            // лежало с прошлого раза, а не текущим документом.
            editor.code.value = formatHtml(editor.getContent());
            editor.sourceMode = true;
            editor.code.style.height = editor.frame.offsetHeight + 'px';
            editor.code.removeAttribute('hidden');
            editor.frame.style.display = 'none';
            editor.code.focus();
        } else {
            var html = editor.code.value;

            editor.sourceMode = false;
            editor.code.setAttribute('hidden', '');
            editor.frame.style.display = '';
            // Возврат из кода прогоняет разметку через ту же чистку, что и
            // обычное сохранение: руками в исходник можно вписать что угодно,
            // включая обработчики событий.
            editor.setContent(html);
            editor.focus();
        }

        // Пока открыт исходник, кнопки оформления бессмысленны.
        editor.buttonNodes.forEach(function (item) {
            if (item.name !== 'code' && item.name !== 'fullscreen') {
                item.node.disabled = editor.sourceMode;
            }
        });
    });

    /**
     * Раскладка разметки по строкам. Не форматтер общего назначения: задача
     * скромная — чтобы в исходнике можно было что-то найти глазами, а не
     * читать полотно в одну строку.
     */
    function formatHtml(html) {
        var blocks = 'p|div|h[1-6]|ul|ol|li|table|thead|tbody|tr|td|th|blockquote|pre|figure|figcaption|section|details|summary|hr';

        return String(html)
            .replace(new RegExp('<(' + blocks + ')(\\s|>)', 'gi'), '\n<$1$2')
            .replace(new RegExp('</(' + blocks + ')>', 'gi'), '</$1>\n')
            .replace(/\n{2,}/g, '\n')
            .trim();
    }

    /* ── Предпросмотр ────────────────────────────────────────────────── */

    RuEditor.registerButton('preview', {
        icon: 'fas fa-eye',
        title: 'Предпросмотр',
        action: function (editor) { editor.exec('preview'); }
    });

    RuEditor.registerCommand('preview', function (editor) {
        var html = editor.getContent();

        RuEditor.dialog({
            title: t('preview.title', 'Предпросмотр'),
            wide: true,
            submitLabel: t('dialog.close', 'Закрыть'),
            fields: [],
            onOpen: function (api) {
                var frame = el('iframe', {
                    style: 'width:100%;height:60vh;border:1px solid #e5e7eb;background:#fff'
                });

                api.body.insertBefore(frame, api.body.firstChild);

                var doc = frame.contentDocument;
                var css = (editor.options.contentCss || []).map(function (href) {
                    return '<link rel="stylesheet" href="' + RuEditor.escapeHtml(href) + '">';
                }).join('');

                doc.open();
                doc.write('<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">' + css +
                          '<style>body{margin:0;padding:16px;font:15px/1.65 system-ui,sans-serif}</style>' +
                          '</head><body class="' + RuEditor.escapeHtml(editor.options.bodyClass || '') + '">' +
                          html + '</body></html>');
                doc.close();
            },
            onSubmit: function () {}
        });
    });

    /* ── Границы блоков ──────────────────────────────────────────────── */

    RuEditor.registerButton('visualblocks', {
        icon: 'fas fa-border-all',
        title: 'Показать границы блоков',
        action: function (editor) { editor.exec('visualblocks'); },
        active: function (editor) { return editor.body.classList.contains('ru-ed-blocks'); }
    });

    RuEditor.registerCommand('visualblocks', function (editor) {
        editor.body.classList.toggle('ru-ed-blocks');
    });

    /* ── Поиск и замена ──────────────────────────────────────────────── */

    RuEditor.registerButton('searchreplace', {
        icon: 'fas fa-magnifying-glass',
        title: 'Найти и заменить',
        action: function (editor) { editor.exec('searchreplace'); }
    });

    RuEditor.registerCommand('searchreplace', function (editor) {
        RuEditor.dialog({
            title: t('search.title', 'Найти и заменить'),
            submitLabel: t('search.replace_all', 'Заменить всё'),
            fields: [
                { name: 'find', label: t('search.find', 'Найти'), type: 'text', required: true },
                { name: 'replace', label: t('search.replace', 'Заменить на'), type: 'text' },
                { name: 'sensitive', label: t('search.sensitive', 'Учитывать регистр'), type: 'check', value: false }
            ],
            onSubmit: function (values, api) {
                var find = values.find;

                if (!find) {
                    return false;
                }

                var flags = values.sensitive ? 'g' : 'gi';
                var pattern = new RegExp(find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), flags);
                var count = 0;

                // Ходим по текстовым узлам, а не по innerHTML: замена в разметке
                // задела бы имена тегов, классы и адреса ссылок.
                var walker = editor.doc.createTreeWalker(editor.body, window.NodeFilter.SHOW_TEXT, null, false);
                var nodes = [];
                var node;

                while ((node = walker.nextNode())) {
                    nodes.push(node);
                }

                nodes.forEach(function (textNode) {
                    if (textNode.parentNode.closest('[data-ru-shortcode]')) {
                        return;
                    }

                    var replaced = textNode.nodeValue.replace(pattern, function (match) {
                        count++;
                        return values.replace || '';
                    });

                    if (replaced !== textNode.nodeValue) {
                        textNode.nodeValue = replaced;
                    }
                });

                editor._snapshot();
                editor.save();
                editor._updateState();

                api.note(t('search.done', 'Заменено вхождений: ') + count);
                api.keepOpen = true;

                return false;
            }
        });
    });

    /* ── Автосохранение черновика ────────────────────────────────────── */

    /**
     * Черновик в localStorage. Спасает от закрытой вкладки и слетевшего
     * сеанса, но НЕ заменяет сохранение материала: ключ привязан к адресу
     * страницы и полю, и на другом устройстве черновика не будет.
     *
     * Восстановление предлагается один раз и только если поле пустое или
     * отличается от черновика — молча подменять уже написанное нельзя.
     */
    RuEditor.registerPlugin('autosave', {
        init: function (editor) {
            if (editor.options.autosave === false || !window.localStorage) {
                return;
            }

            var key = 'ru-ed:' + window.location.pathname + ':' + editor.id;
            var saved = null;

            try {
                saved = window.localStorage.getItem(key);
            } catch (error) {
                return;
            }

            if (saved && saved !== editor.getContent()) {
                offerRestore(editor, key, saved);
            }

            editor.on('change', RuEditor.debounce(function () {
                try {
                    window.localStorage.setItem(key, editor.getContent());
                } catch (error) {
                    /* Хранилище переполнено или запрещено — не повод падать. */
                }
            }, 2000));

            if (editor.form) {
                // Материал сохранён — черновик больше не нужен, иначе он будет
                // предлагаться поверх уже сохранённого текста.
                editor.form.addEventListener('submit', function () {
                    try {
                        window.localStorage.removeItem(key);
                    } catch (error) {}
                });
            }
        }
    });

    function offerRestore(editor, key, saved) {
        var bar = el('div', {
            class: 'ru-ed-note',
            style: 'display:flex;align-items:center;gap:10px;margin:0;border-left:0;border-right:0;border-top:0'
        }, [
            el('span', { text: t('autosave.found', 'Найден несохранённый черновик этой страницы.') })
        ]);

        var restore = el('button', {
            type: 'button',
            class: 'ru-ed-primary',
            style: 'height:26px;padding:0 10px;font-size:12px',
            text: t('autosave.restore', 'Восстановить')
        });

        var drop = el('button', {
            type: 'button',
            class: 'ru-ed-ghost',
            style: 'height:26px;padding:0 10px;font-size:12px',
            text: t('autosave.discard', 'Удалить черновик')
        });

        restore.addEventListener('click', function () {
            editor.setContent(saved);
            bar.remove();
        });

        drop.addEventListener('click', function () {
            try {
                window.localStorage.removeItem(key);
            } catch (error) {}
            bar.remove();
        });

        bar.appendChild(restore);
        bar.appendChild(drop);
        editor.root.insertBefore(bar, editor.shell);
    }

    /* ── Подсказка по клавишам ───────────────────────────────────────── */

    RuEditor.registerButton('help', {
        icon: 'fas fa-circle-question',
        title: 'Горячие клавиши',
        action: function (editor) { editor.exec('help'); }
    });

    RuEditor.registerCommand('help', function () {
        var rows = [
            ['Ctrl + B', t('help.bold', 'Полужирный')],
            ['Ctrl + I', t('help.italic', 'Курсив')],
            ['Ctrl + U', t('help.underline', 'Подчёркнутый')],
            ['Ctrl + K', t('help.link', 'Ссылка')],
            ['Ctrl + Z', t('help.undo', 'Отменить')],
            ['Ctrl + Y', t('help.redo', 'Повторить')],
            ['Ctrl + Shift + V', t('help.paste', 'Вставить без оформления')],
            ['Tab', t('help.tab', 'Следующая ячейка таблицы')],
            ['Esc', t('help.esc', 'Закрыть список или выйти из полного экрана')]
        ];

        RuEditor.dialog({
            title: t('help.title', 'Горячие клавиши'),
            submitLabel: t('dialog.close', 'Закрыть'),
            fields: [],
            onOpen: function (api) {
                var list = el('dl', { style: 'display:grid;grid-template-columns:auto 1fr;gap:6px 14px;margin:0;font-size:13px' });

                rows.forEach(function (row) {
                    list.appendChild(el('dt', {
                        text: row[0],
                        style: 'font:600 12px ui-monospace,Consolas,monospace;color:#374151'
                    }));
                    list.appendChild(el('dd', { text: row[1], style: 'margin:0;color:#6b7280' }));
                });

                api.body.insertBefore(list, api.body.firstChild);
            },
            onSubmit: function () {}
        });
    });

    /* ── Мелочи, которые экономят время при наборе ───────────────────── */

    RuEditor.registerPlugin('assist', {
        init: function (editor) {
            editor.doc.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    leaveHeading(editor, event);
                    linkify(editor);
                } else if (event.key === ' ') {
                    linkify(editor);
                }
            });

            // Уход со страницы с несохранённым текстом. Черновик в хранилище
            // спасает не всегда: на другом устройстве его не будет, а материал
            // могут писать час. Предупреждение стандартное, браузер сам решает,
            // показывать ли его.
            if (editor.options.confirmLeave === false) {
                return;
            }

            var dirty = false;

            editor.on('change', function () { dirty = true; });

            if (editor.form) {
                editor.form.addEventListener('submit', function () { dirty = false; });
            }

            window.addEventListener('beforeunload', function (event) {
                if (dirty) {
                    event.preventDefault();
                    // Текст сообщения браузеры давно игнорируют и показывают
                    // своё, но непустое значение обязательно.
                    event.returnValue = '';
                }
            });
        }
    });

    /**
     * Enter в конце заголовка начинает обычный абзац, а не второй заголовок.
     *
     * Поведение по умолчанию продолжает тот же блок: набрал заголовок, нажал
     * Enter — и следующий абзац тоже заголовок, что замечают через три строки.
     */
    function leaveHeading(editor, event) {
        var block = editor.closest('h1,h2,h3,h4,h5,h6,blockquote');

        if (!block) {
            return;
        }

        var range = editor.getRange();

        if (!range || !range.collapsed) {
            return;
        }

        // Курсор именно в КОНЦЕ блока? В середине Enter должен делить текст,
        // как обычно.
        var tail = range.cloneRange();

        tail.selectNodeContents(block);
        tail.setStart(range.endContainer, range.endOffset);

        if (tail.toString().trim() !== '') {
            return;
        }

        event.preventDefault();

        var paragraph = editor.doc.createElement('p');

        paragraph.appendChild(editor.doc.createElement('br'));
        block.parentNode.insertBefore(paragraph, block.nextSibling);

        var placed = editor.doc.createRange();

        placed.setStart(paragraph, 0);
        placed.collapse(true);
        editor.setRange(placed);

        editor._snapshot();
        editor.save();
        editor._updateState();
    }

    var ADDRESS = /(^|[\s(])((?:https?:\/\/|www\.)[^\s<>()]{4,})$/i;

    /**
     * Набранный адрес сам становится ссылкой по пробелу или переводу строки.
     * Иначе на каждую ссылку нужно открывать диалог, хотя адрес уже написан.
     */
    function linkify(editor) {
        var range = editor.getRange();

        if (!range || !range.collapsed || range.startContainer.nodeType !== 3) {
            return;
        }

        var node = range.startContainer;

        // Внутри уже существующей ссылки делать нечего.
        if (node.parentNode.closest && node.parentNode.closest('a')) {
            return;
        }

        var match = node.nodeValue.slice(0, range.startOffset).match(ADDRESS);

        if (!match) {
            return;
        }

        var address = match[2];
        var start = range.startOffset - address.length;
        var link = editor.doc.createElement('a');

        link.setAttribute('href', /^www\./i.test(address) ? 'https://' + address : address);
        link.textContent = address;

        var target = editor.doc.createRange();

        target.setStart(node, start);
        target.setEnd(node, range.startOffset);
        target.deleteContents();
        target.insertNode(link);

        var after = editor.doc.createRange();

        after.setStartAfter(link);
        after.collapse(true);
        editor.setRange(after);
    }

    /* ── Клавиши и доступность ───────────────────────────────────────── */

    RuEditor.registerPlugin('keys', {
        init: function (editor) {
            editor.doc.addEventListener('keydown', function (event) {
                var mod = event.ctrlKey || event.metaKey;

                if (event.key === 'F11') {
                    event.preventDefault();
                    editor.exec('fullscreen');
                    return;
                }

                // Ctrl+Shift+V — вставка без оформления. Флаг снимает сам
                // обработчик вставки в ядре, сразу после срабатывания.
                if (mod && event.shiftKey && event.key.toLowerCase() === 'v') {
                    editor._plainPasteOnce = true;
                }

                // Ctrl+S сохраняет материал, а не страницу браузером: рефлекс
                // у всех один, и терять текст из-за него обидно.
                if (mod && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    editor.save();

                    if (editor.form) {
                        editor.form.requestSubmit();
                    }
                }
            });

            // Панель — одна остановка табуляции, внутри ходим стрелками.
            // Иначе двадцать кнопок пришлось бы перебирать по одной, чтобы
            // добраться до текста.
            var buttons = function () {
                return Array.prototype.slice.call(
                    editor.toolbar.querySelectorAll('.ru-ed-btn:not(:disabled)')
                );
            };

            // Выключенные кнопки тоже надо убрать из обхода: они не попадают в
            // список выше и остались бы с табуляцией по умолчанию — вместо
            // одной остановки на всю панель их получалось несколько.
            var all = function () {
                return Array.prototype.slice.call(editor.toolbar.querySelectorAll('.ru-ed-btn'));
            };

            editor.toolbar.addEventListener('keydown', function (event) {
                var list = buttons();
                var index = list.indexOf(document.activeElement);

                if (index === -1) {
                    return;
                }

                var next = null;

                if (event.key === 'ArrowRight') {
                    next = list[(index + 1) % list.length];
                } else if (event.key === 'ArrowLeft') {
                    next = list[(index - 1 + list.length) % list.length];
                } else if (event.key === 'Home') {
                    next = list[0];
                } else if (event.key === 'End') {
                    next = list[list.length - 1];
                }

                if (next) {
                    event.preventDefault();
                    next.focus();
                }
            });

            editor.on('ready', setRoving);
            editor.on('change', setRoving);
            editor.toolbar.addEventListener('focusin', setRoving);
            setRoving();

            function setRoving() {
                var live = buttons();
                var stop = live.indexOf(document.activeElement) !== -1 ? document.activeElement : live[0];

                all().forEach(function (button) {
                    button.tabIndex = button === stop ? 0 : -1;
                });
            }
        }
    });
}(window, document));
