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

    /* ── Полный экран ────────────────────────────────────────────────── */

    RuEditor.registerButton('fullscreen', {
        icon: 'fas fa-expand',
        title: 'Во весь экран (F11 в редакторе, Esc — выйти)',
        action: function (editor) { editor.exec('fullscreen'); },
        active: function (editor) { return editor.fullscreen; }
    });

    RuEditor.registerCommand('fullscreen', function (editor) {
        editor.fullscreen = !editor.fullscreen;
        editor.root.classList.toggle('is-fullscreen', editor.fullscreen);
        // Страница под развёрнутым редактором не должна прокручиваться: иначе
        // колесо мыши уводит фон, а не текст. Класс вешаем и на html: в разных
        // раскладках прокручивается то тело документа, то корневой элемент, и
        // запрет только на body в половине случаев не делает ничего.
        document.body.classList.toggle('ru-ed-locked', editor.fullscreen);
        document.documentElement.classList.toggle('ru-ed-locked', editor.fullscreen);

        // Esc должен выходить откуда угодно. Обработчик в ядре висит на
        // документе РАМКИ и срабатывает, только пока курсор в тексте: стоило
        // нажать кнопку на панели — и клавиша переставала работать, а выйти
        // из развёрнутого редактора было уже нечем, кроме той же кнопки.
        if (editor.fullscreen) {
            editor._escapeFullscreen = function (event) {
                if (event.key === 'Escape' && editor.fullscreen && !document.querySelector('.ru-ed-dialog-back')) {
                    event.preventDefault();
                    editor.exec('fullscreen');
                }
            };

            document.addEventListener('keydown', editor._escapeFullscreen);
        } else if (editor._escapeFullscreen) {
            document.removeEventListener('keydown', editor._escapeFullscreen);
            editor._escapeFullscreen = null;
        }

        if (editor.fullscreen) {
            editor._heightBefore = editor.frame.style.height;
            editor.frame.style.height = '';
        } else {
            editor.frame.style.height = editor._heightBefore || '420px';
        }

        var icon = editor.root.querySelector('[data-ru-btn="fullscreen"] i');

        if (icon) {
            icon.className = editor.fullscreen ? 'fas fa-compress' : 'fas fa-expand';
        }

        editor.focus();
    });

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
