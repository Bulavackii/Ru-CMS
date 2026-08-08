/* ============================================================================
   RU Editor — визуальный редактор содержимого. Ядро.
   ----------------------------------------------------------------------------
   Свой редактор вместо стороннего: у TinyMCE 8 лицензия GPLv2+ либо
   коммерческая, а CMS распространяется закрыто — открывать весь исходный код
   ради поля ввода никто не собирается.

   Почему рамка редактирования — IFRAME, а не div на странице.
   Оформление содержимого (content-blocks.css) написано под страницу сайта, а
   вокруг него в панели лежат Tailwind-сброс, body.admin-sharp с правилом
   border-radius:0 на ВСЁ подряд и десяток собственных стилей раздела. В общем
   документе они протекли бы внутрь текста, и автор видел бы не то, что увидит
   посетитель. Рамка отдельного документа решает это раз и навсегда — заодно
   так делают все взрослые редакторы.

   Своя история отмены, а не встроенная в contenteditable: браузерная знает
   только про набор текста и execCommand, а операции над таблицами и блоками
   выполняются своим кодом и в неё не попадают — «отменить» после вставки
   строки таблицы откатывало бы не то.

   Расширение — через реестры: registerCommand / registerButton / registerPlugin.
   Всё, что умеет редактор сверх набора текста, добавлено через них же, то есть
   свой блок добавляется тем же способом, что и встроенный.
   ========================================================================= */

(function (window, document) {
    'use strict';

    if (window.RuEditor) {
        return;
    }

    /* ── Реестры ─────────────────────────────────────────────────────── */

    var commands = Object.create(null);
    var buttons = Object.create(null);
    var plugins = [];
    var instances = [];

    /* ── Мелкие помощники ────────────────────────────────────────────── */

    function el(tag, attrs, kids) {
        var node = document.createElement(tag);

        if (attrs) {
            Object.keys(attrs).forEach(function (key) {
                var value = attrs[key];

                if (value === null || value === undefined || value === false) {
                    return;
                }
                if (key === 'class') {
                    node.className = value;
                } else if (key === 'text') {
                    node.textContent = value;
                } else if (key === 'html') {
                    node.innerHTML = value;
                } else if (key.slice(0, 2) === 'on' && typeof value === 'function') {
                    node.addEventListener(key.slice(2), value);
                } else if (value === true) {
                    node.setAttribute(key, '');
                } else {
                    node.setAttribute(key, value);
                }
            });
        }

        (kids || []).forEach(function (kid) {
            if (kid) {
                node.appendChild(typeof kid === 'string' ? document.createTextNode(kid) : kid);
            }
        });

        return node;
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function debounce(fn, wait) {
        var timer = null;

        return function () {
            var args = arguments;
            var self = this;

            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                fn.apply(self, args);
            }, wait);
        };
    }

    /**
     * Путь до узла от корня — списком индексов среди детей.
     *
     * Нужен истории отмены: снимок содержимого — это строка HTML, а после
     * восстановления прежние объекты узлов уже не существуют, и хранить
     * ссылку на них бессмысленно. Путь же переживает перестроение документа.
     */
    function pathOf(node, offset, root) {
        var path = [];
        var current = node;

        while (current && current !== root) {
            var parent = current.parentNode;

            if (!parent) {
                return null;
            }

            path.unshift(Array.prototype.indexOf.call(parent.childNodes, current));
            current = parent;
        }

        return current === root ? { path: path, offset: offset } : null;
    }

    function nodeAt(mark, root) {
        var node = root;

        for (var i = 0; i < mark.path.length; i++) {
            if (!node.childNodes || !node.childNodes[mark.path[i]]) {
                return null;
            }
            node = node.childNodes[mark.path[i]];
        }

        return node;
    }

    /* ── Перевод интерфейса ──────────────────────────────────────────── */

    var strings = {};

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(strings, key) ? strings[key] : (fallback || key);
    }

    /* ── Экземпляр редактора ─────────────────────────────────────────── */

    function Editor(textarea, options) {
        this.textarea = textarea;
        this.options = options || {};
        this.id = textarea.id || ('ru-ed-' + instances.length);
        this.plugins = Object.create(null);
        this.listeners = Object.create(null);
        this.buttonNodes = [];
        this.openMenu = null;
        this.sourceMode = false;
        this.fullscreen = false;

        this.history = { stack: [], index: -1, limit: 80 };

        this._build();
        this._boot();

        instances.push(this);
    }

    Editor.prototype = {

        /* ── Сборка разметки ─────────────────────────────────────────── */

        _build: function () {
            var self = this;
            var opts = this.options;

            this.root = el('div', { class: 'ru-ed', 'data-ru-editor': this.id });
            this.toolbar = el('div', { class: 'ru-ed-toolbar', role: 'toolbar', 'aria-label': t('toolbar', 'Панель инструментов') });
            this.shell = el('div', { class: 'ru-ed-shell' });

            this.frame = el('iframe', {
                class: 'ru-ed-frame',
                title: t('area', 'Область редактирования'),
                style: 'height:' + (parseInt(opts.height, 10) || 420) + 'px'
            });

            this.code = el('textarea', { class: 'ru-ed-code', hidden: true, spellcheck: 'false' });

            this.path = el('div', { class: 'ru-ed-path' });
            this.counts = el('div', { class: 'ru-ed-counts' });
            this.status = el('div', { class: 'ru-ed-status' }, [this.path, this.counts]);

            if (opts.statusbar !== false) {
                this.grip = el('span', {
                    class: 'ru-ed-grip',
                    title: t('resize', 'Потяните, чтобы изменить высоту'),
                    html: '<svg viewBox="0 0 14 14" width="14" height="14" fill="currentColor">' +
                          '<path d="M13 8 8 13h5V8zM13 3 3 13h2.5L13 5.5V3z"/></svg>'
                });
                this.counts.appendChild(this.grip);
            }

            this.shell.appendChild(this.frame);
            this.shell.appendChild(this.code);

            this.root.appendChild(this.toolbar);
            this.root.appendChild(this.shell);

            if (opts.statusbar !== false) {
                this.root.appendChild(this.status);
            }

            this.textarea.classList.add('ru-ed-source');
            this.textarea.parentNode.insertBefore(this.root, this.textarea);
            this.root.appendChild(this.textarea);

            this.form = this.textarea.form;

            if (this.form) {
                // Перед отправкой формы содержимое обязано быть в поле: сама
                // рамка — отдельный документ, и браузер про неё ничего не знает.
                this.form.addEventListener('submit', function () {
                    self.save();
                });
            }
        },

        /**
         * Документ внутри рамки. Пишем его напрямую, без srcdoc: srcdoc
         * по-разному ведёт себя со строгой политикой безопасности, а пустой
         * about:blank наследует источник родителя во всех браузерах.
         */
        _boot: function () {
            var self = this;
            var opts = this.options;
            var css = (opts.contentCss || []).map(function (href) {
                return '<link rel="stylesheet" href="' + escapeHtml(href) + '">';
            }).join('');

            var doc = this.frame.contentDocument;

            doc.open();
            doc.write(
                '<!DOCTYPE html><html lang="' + escapeHtml(opts.lang || 'ru') + '"><head>' +
                '<meta charset="utf-8">' +
                '<meta name="viewport" content="width=device-width, initial-scale=1">' +
                css +
                '<style>' + this._frameCss() + '</style>' +
                '</head><body class="' + escapeHtml(opts.bodyClass || '') + '"></body></html>'
            );
            doc.close();

            this.doc = doc;
            this.win = this.frame.contentWindow;
            this.body = doc.body;

            this.body.contentEditable = 'true';
            this.body.spellcheck = opts.spellcheck !== false;
            this.body.setAttribute('role', 'textbox');
            this.body.setAttribute('aria-multiline', 'true');

            if (opts.placeholder) {
                this.body.setAttribute('data-placeholder', opts.placeholder);
            }

            // Enter должен рождать абзац, а не <div>: в Chrome по умолчанию
            // именно div, и материал получался свёрстанным иначе, чем всё
            // остальное на сайте, где типографика написана под <p>.
            try {
                doc.execCommand('defaultParagraphSeparator', false, 'p');
                // styleWithCSS=false — начертания выходят тегами (<b>, <i>), а
                // не встроенным style. Теги переживают чистку разметки и
                // читаются в исходном коде; ниже они приводятся к strong/em.
                doc.execCommand('styleWithCSS', false, false);
            } catch (error) {
                /* Старый движок без этих настроек — не критично. */
            }

            this.setContent(this.textarea.value || '', { silent: true });
            this._renderToolbar();
            this._bind();

            plugins.forEach(function (plugin) {
                if (!plugin.init) {
                    return;
                }
                try {
                    self.plugins[plugin.name] = plugin.init(self) || true;
                } catch (error) {
                    if (window.console) {
                        window.console.error('RuEditor: плагин ' + plugin.name + ' не поднялся', error);
                    }
                }
            });

            this._snapshot(true);
            this._updateState();
            this.emit('ready');
        },

        /**
         * Стили, нужные самой рамке. Оформление содержимого сюда НЕ входит —
         * оно приходит отдельными файлами (content-blocks.css), теми же, что
         * подключены на сайте.
         */
        _frameCss: function () {
            var opts = this.options;

            return [
                'html,body{margin:0;padding:0}',
                // Место под полосу прокрутки резервируется ВСЕГДА.
                //
                // Иначе получается вечный пересчёт вёрстки: содержимое чуть
                // выше рамки — появляется полоса, ширина текста уменьшается на
                // её толщину, строки перевёрстываются, содержимое становится
                // чуть ниже рамки — полоса исчезает, ширина возвращается, и всё
                // сначала. Браузер крутит это бесконечно, перестаёт отдавать
                // кадры (проверено: requestAnimationFrame внутри рамки просто
                // не вызывается), и экран идёт рябью. В обычном режиме рамка
                // низкая, содержимое всегда выше — полоса нужна постоянно, и
                // цикла нет; он проявляется как раз на полном экране, где
                // высота рамки близка к высоте материала.
                'html{overflow-y:scroll}',
                'body{min-height:100%;padding:14px 16px;font-family:' +
                    (opts.fontFamily || '-apple-system,BlinkMacSystemFont,Inter,system-ui,sans-serif') +
                    ';font-size:' + (opts.fontSize || '15px') + ';line-height:1.65;color:#111827;' +
                    'outline:none;word-wrap:break-word;overflow-wrap:break-word}',
                // Подсказка в пустом документе. :empty не годится — там всегда
                // лежит хотя бы один пустой абзац, который создаёт сам браузер.
                'body.is-empty::before{content:attr(data-placeholder);color:#9ca3af;pointer-events:none;display:block}',
                'body :focus{outline:none}',
                'img{max-width:100%;height:auto}',
                'video,audio{max-width:100%}',
                'audio{width:100%}',
                // Ролик с чужой площадки и проигрыватель звука внутри редактора
                // мышь не получают.
                //
                // У ролика нажатие ушло бы в его собственный документ. У звука
                // причина другая и менее очевидная: вся видимая площадь <audio
                // controls> — это его встроенные кнопки, они забирают нажатие
                // себе, и до редактора оно не доходит вовсе. Выделить такую
                // вставку, чтобы потянуть или выровнять, было нечем.
                //
                // Проигрывать внутри редактора при этом нельзя — но это
                // страница правки, а не проигрыватель: возможность выделить
                // вставку здесь важнее. На сайте оба работают как обычно,
                // правило действует только внутри рамки.
                'iframe,audio{pointer-events:none}',

                // Проигрыватель звука показывается таким же, каким его увидит
                // посетитель. Родной тег при этом НЕ прячем, а растягиваем на
                // всю коробку прозрачным: он остаётся тем, за что вставку
                // выделяют мышью и тянут за ручки. Спрячь его совсем — у
                // выделения и растягивания пропала бы цель, и звук снова стал
                // бы единственной вставкой, которая ведёт себя не как все.
                // Селектор повторяет вес общего правила (.page-content …),
                // иначе оно перевешивает и тег снова прячется: замер показывал
                // у него ноль на ноль. Наши стили идут в документе позже —
                // при равном весе побеждают они.
                '.page-content .pc-audio{position:relative}',
                '.page-content .pc-audio.is-ready audio{display:block;position:absolute;' +
                    'inset:0;width:100%;height:100%;opacity:0}',

                // Панель — предпросмотр, а не орган управления: в рамке
                // редактора звук и так не проигрывается (см. правило выше про
                // pointer-events), а перехват мыши мешал бы выделять вставку.
                '.page-content .pc-audio__ui{pointer-events:none}',
                'iframe{max-width:100%}',
                'table{border-collapse:collapse}',
                // Служебная разметка редактора: границы блоков и выбранный узел.
                'body.ru-ed-blocks *{outline:1px dashed rgba(99,102,241,.45)}',
                '.ru-ed-selected{outline:2px solid ' + (opts.accent || '#6366f1') + ';outline-offset:1px}',
                '[data-ru-shortcode]{display:inline-block;padding:2px 8px;font:600 12px ui-monospace,Consolas,monospace;' +
                    'color:#3730a3;background:#e0e7ff;border:1px dashed #a5b4fc;cursor:default;user-select:all}'
            ].join('');
        },

        /* ── Панель инструментов ─────────────────────────────────────── */

        _renderToolbar: function () {
            var self = this;
            var spec = this.options.toolbar || 'undo redo | bold italic | link';

            this.toolbar.innerHTML = '';
            this.buttonNodes = [];

            spec.split('|').forEach(function (group, index) {
                var names = group.trim().split(/\s+/).filter(Boolean);

                if (!names.length) {
                    return;
                }
                if (index > 0) {
                    self.toolbar.appendChild(el('span', { class: 'ru-ed-sep', 'aria-hidden': 'true' }));
                }

                names.forEach(function (name) {
                    var def = buttons[name];

                    if (!def) {
                        return;
                    }
                    var node = def.type === 'menu' ? self._menuButton(name, def) : self._plainButton(name, def);

                    if (node) {
                        self.toolbar.appendChild(node);
                    }
                });
            });
        },

        _plainButton: function (name, def) {
            var self = this;
            var button = el('button', {
                type: 'button',
                class: 'ru-ed-btn',
                'data-ru-btn': name,
                title: def.title ? t('btn.' + name, def.title) : name,
                'aria-label': def.title ? t('btn.' + name, def.title) : name
            }, [
                def.icon ? el('i', { class: def.icon, 'aria-hidden': 'true' }) : null,
                def.label ? el('span', { text: def.label }) : null
            ]);

            button.addEventListener('mousedown', function (event) {
                // Панель не должна забирать курсор из текста: без этого
                // выделение схлопывается ещё до того, как команда выполнится.
                event.preventDefault();
            });

            button.addEventListener('click', function () {
                self.closeMenus();

                if (def.action) {
                    def.action(self);
                } else if (def.command) {
                    self.exec(def.command, def.value);
                }
            });

            this.buttonNodes.push({ name: name, def: def, node: button });

            return button;
        },

        _menuButton: function (name, def) {
            var self = this;
            var label = el('span', { class: 'ru-ed-drop-label', text: def.label || '' });
            var button = el('button', {
                type: 'button',
                class: 'ru-ed-btn',
                'data-ru-btn': name,
                'aria-haspopup': 'true',
                'aria-expanded': 'false',
                title: def.title ? t('btn.' + name, def.title) : name
            }, [
                def.icon ? el('i', { class: def.icon, 'aria-hidden': 'true' }) : null,
                def.label !== undefined ? label : null,
                el('span', { class: 'ru-ed-drop-caret', html: '&#9662;', 'aria-hidden': 'true' })
            ]);

            var menu = el('div', { class: 'ru-ed-menu', hidden: true, role: 'menu' });
            var wrap = el('div', { class: 'ru-ed-drop' }, [button, menu]);

            button.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            button.addEventListener('click', function () {
                var willOpen = menu.hasAttribute('hidden');

                self.closeMenus();

                if (!willOpen) {
                    return;
                }

                self._fillMenu(menu, def);
                menu.removeAttribute('hidden');
                button.setAttribute('aria-expanded', 'true');
                self.openMenu = { menu: menu, button: button };
            });

            this.buttonNodes.push({ name: name, def: def, node: button, menu: menu, label: label });

            return wrap;
        },

        _fillMenu: function (menu, def) {
            var self = this;
            var items = typeof def.items === 'function' ? def.items(this) : (def.items || []);

            menu.innerHTML = '';

            // Список может рисовать себя сам — так сделана палитра цветов:
            // сетка образцов не раскладывается на пункты меню.
            if (def.render) {
                def.render(self, menu);
                return;
            }

            items.forEach(function (item) {
                if (item.head) {
                    menu.appendChild(el('div', { class: 'ru-ed-menu-head', text: item.head }));
                    return;
                }

                var entry = el('button', {
                    type: 'button',
                    class: 'ru-ed-menu-item' + (item.active && item.active(self) ? ' is-active' : ''),
                    role: 'menuitem',
                    // Пункт показывает себя тем оформлением, которое применяет:
                    // «Заголовок 2» в списке выглядит заголовком.
                    style: item.style || null
                }, [
                    item.icon ? el('i', { class: item.icon, 'aria-hidden': 'true' }) : null,
                    el('span', { text: item.label }),
                    item.hint ? el('small', { text: item.hint }) : null
                ]);

                entry.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                });

                entry.addEventListener('click', function () {
                    self.closeMenus();

                    if (item.action) {
                        item.action(self);
                    } else if (item.command) {
                        self.exec(item.command, item.value);
                    }
                });

                menu.appendChild(entry);
            });
        },

        closeMenus: function () {
            if (!this.openMenu) {
                return;
            }

            this.openMenu.menu.setAttribute('hidden', '');
            this.openMenu.button.setAttribute('aria-expanded', 'false');
            this.openMenu = null;
        },

        /* ── События ─────────────────────────────────────────────────── */

        _bind: function () {
            var self = this;
            var doc = this.doc;

            var onChange = debounce(function () {
                self._snapshot();
                self.save();
                self.emit('change');
            }, 400);

            doc.addEventListener('input', function () {
                self._markEmpty();
                self._updateState();
                onChange();
            });

            doc.addEventListener('keydown', function (event) {
                self._onKeyDown(event);
            });

            ['keyup', 'mouseup', 'focus'].forEach(function (type) {
                doc.addEventListener(type, function () {
                    self._updateState();
                });
            });

            doc.addEventListener('focus', function () {
                self.root.classList.add('is-focused');
                // Кто сейчас в работе. Нужно коду СНАРУЖИ редактора: на
                // странице фрагментов есть свои кнопки вставки разметки, и им
                // надо знать, в какой из редакторов формы класть.
                RuEditor.activeEditor = self;
            }, true);

            doc.addEventListener('blur', function () {
                self.root.classList.remove('is-focused');
                self.save();
            }, true);

            doc.addEventListener('mousedown', function () {
                self.closeMenus();
            });

            document.addEventListener('mousedown', function (event) {
                if (self.openMenu && !self.root.contains(event.target)) {
                    self.closeMenus();
                }
            });

            doc.addEventListener('paste', function (event) {
                self._onPaste(event);
            });

            // Ссылки внутри рамки не должны уводить со страницы по клику.
            doc.addEventListener('click', function (event) {
                var link = event.target.closest && event.target.closest('a');

                if (link) {
                    event.preventDefault();
                }
            });

            if (this.grip) {
                this._bindGrip();
            }

            this.code.addEventListener('input', debounce(function () {
                self.textarea.value = self.code.value;
            }, 200));
        },

        _bindGrip: function () {
            var self = this;

            this.grip.addEventListener('mousedown', function (event) {
                event.preventDefault();

                var startY = event.clientY;
                var startHeight = self.frame.offsetHeight;

                function move(moveEvent) {
                    var height = Math.max(160, startHeight + (moveEvent.clientY - startY));
                    self.frame.style.height = height + 'px';
                }

                function stop() {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', stop);
                }

                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', stop);
            });
        },

        _onKeyDown: function (event) {
            var key = event.key.toLowerCase();
            var mod = event.ctrlKey || event.metaKey;

            if (mod && key === 'z' && !event.shiftKey) {
                event.preventDefault();
                this.undo();
                return;
            }

            if (mod && (key === 'y' || (key === 'z' && event.shiftKey))) {
                event.preventDefault();
                this.redo();
                return;
            }

            if (mod && !event.shiftKey && !event.altKey) {
                var map = { b: 'bold', i: 'italic', u: 'underline', k: 'link' };

                if (map[key]) {
                    event.preventDefault();
                    this.exec(map[key]);
                    return;
                }
            }

            if (event.key === 'Escape') {
                if (this.openMenu) {
                    event.preventDefault();
                    this.closeMenus();
                } else if (this.fullscreen) {
                    event.preventDefault();
                    this.exec('fullscreen');
                }
                return;
            }

            // Tab внутри таблицы ходит по ячейкам; в остальном тексте он должен
            // уводить фокус дальше по форме, а не вставлять отступ.
            if (event.key === 'Tab') {
                this.emit('tab', event);
            }
        },

        /**
         * Вставка. Из Word и Google Docs приезжает разметка с их служебными
         * классами, шрифтами и размерами — если положить её как есть, документ
         * перестаёт следовать оформлению сайта. Чистим до осмысленного.
         */
        _onPaste: function (event) {
            var clipboard = event.clipboardData;

            if (!clipboard) {
                return;
            }

            var html = clipboard.getData('text/html');
            var text = clipboard.getData('text/plain');

            // Ctrl+Shift+V — вставить как обычный текст.
            if (this._plainPasteOnce || (!html && text)) {
                event.preventDefault();
                this._plainPasteOnce = false;
                this.insertText(text);
                return;
            }

            if (!html) {
                return;
            }

            event.preventDefault();
            this.insertHtml(RuEditor.cleanPastedHtml(html, this.options.pasteAllow));
        },

        /* ── Состояние ───────────────────────────────────────────────── */

        _updateState: function () {
            var self = this;

            this.buttonNodes.forEach(function (item) {
                var def = item.def;

                if (def.active) {
                    var on = !!def.active(self);

                    item.node.classList.toggle('is-active', on);
                    // Экранный диктор объявляет «нажата»: подсветкой цветом он
                    // не пользуется, и без этого состояние кнопки для него
                    // просто не существует.
                    item.node.setAttribute('aria-pressed', on ? 'true' : 'false');
                }
                if (def.label !== undefined && def.currentLabel && item.label) {
                    item.label.textContent = def.currentLabel(self) || def.label;
                }
                if (def.enabled) {
                    item.node.disabled = !def.enabled(self);
                }
            });

            this._renderPath();
            this._renderCounts();
        },

        _renderPath: function () {
            if (!this.path) {
                return;
            }

            var self = this;
            var node = this.selectedNode();
            var chain = [];

            while (node && node !== this.body) {
                if (node.nodeType === 1) {
                    chain.unshift(node);
                }
                node = node.parentNode;
            }

            this.path.innerHTML = '';

            chain.slice(-6).forEach(function (item, index) {
                if (index > 0) {
                    self.path.appendChild(el('span', { text: '›' }));
                }

                self.path.appendChild(el('button', {
                    type: 'button',
                    text: item.tagName.toLowerCase(),
                    title: t('path.select', 'Выделить этот блок'),
                    onclick: function () {
                        self.selectNode(item);
                    }
                }));
            });
        },

        _renderCounts: function () {
            if (!this.counts) {
                return;
            }

            var text = (this.body.textContent || '').trim();
            // Кириллица: str.split(' ') и \w+ на русском тексте врут — считаем
            // по буквам и цифрам любого алфавита.
            var words = text ? (text.match(/[\p{L}\p{N}]+/gu) || []).length : 0;

            var wordNode = this.counts.querySelector('[data-ru-count="words"]');

            if (!wordNode) {
                wordNode = el('span', { 'data-ru-count': 'words' });
                this.counts.insertBefore(wordNode, this.counts.firstChild);
            }

            wordNode.textContent = t('words', 'Слов') + ': ' + words + ' · ' +
                t('chars', 'Знаков') + ': ' + text.length;
        },

        _markEmpty: function () {
            var empty = !this.body.textContent.trim() && !this.body.querySelector('img,table,iframe,hr,video');

            this.body.classList.toggle('is-empty', empty);
        },

        /* ── История ─────────────────────────────────────────────────── */

        _snapshot: function (force) {
            var html = this.body.innerHTML;
            var history = this.history;
            var last = history.stack[history.index];

            if (!force && last && last.html === html) {
                return;
            }

            history.stack = history.stack.slice(0, history.index + 1);
            history.stack.push({ html: html, mark: this._mark() });

            if (history.stack.length > history.limit) {
                history.stack.shift();
            }

            history.index = history.stack.length - 1;
            this._updateState();
        },

        _mark: function () {
            var range = this.getRange();

            if (!range) {
                return null;
            }

            var start = pathOf(range.startContainer, range.startOffset, this.body);
            var end = pathOf(range.endContainer, range.endOffset, this.body);

            return start && end ? { start: start, end: end } : null;
        },

        _applyMark: function (mark) {
            if (!mark) {
                return;
            }

            var startNode = nodeAt(mark.start, this.body);
            var endNode = nodeAt(mark.end, this.body);

            if (!startNode || !endNode) {
                return;
            }

            try {
                var range = this.doc.createRange();

                range.setStart(startNode, Math.min(mark.start.offset, this._maxOffset(startNode)));
                range.setEnd(endNode, Math.min(mark.end.offset, this._maxOffset(endNode)));
                this.setRange(range);
            } catch (error) {
                /* Разметка изменилась сильнее, чем сохранённый путь — не беда. */
            }
        },

        _maxOffset: function (node) {
            return node.nodeType === 3 ? node.length : node.childNodes.length;
        },

        undo: function () {
            if (this.history.index <= 0) {
                return;
            }

            this.history.index--;
            this._restore();
        },

        redo: function () {
            if (this.history.index >= this.history.stack.length - 1) {
                return;
            }

            this.history.index++;
            this._restore();
        },

        _restore: function () {
            var entry = this.history.stack[this.history.index];

            this.body.innerHTML = entry.html;
            this._applyMark(entry.mark);
            this._markEmpty();
            this.save();
            this._updateState();
            this.emit('change');
        },

        canUndo: function () {
            return this.history.index > 0;
        },

        canRedo: function () {
            return this.history.index < this.history.stack.length - 1;
        },

        /* ── Выделение ───────────────────────────────────────────────── */

        getSelection: function () {
            return this.win.getSelection();
        },

        getRange: function () {
            var selection = this.getSelection();

            if (!selection || !selection.rangeCount) {
                return null;
            }

            var range = selection.getRangeAt(0);

            return this.body.contains(range.commonAncestorContainer) ? range : null;
        },

        setRange: function (range) {
            var selection = this.getSelection();

            selection.removeAllRanges();
            selection.addRange(range);
        },

        /**
         * Запомнить выделение перед открытием диалога и вернуть после.
         * Диалог живёт в документе страницы и забирает фокус — без этого
         * вставка происходила бы в начало текста, а не туда, где стоял курсор.
         */
        saveSelection: function () {
            this._saved = this._mark();
        },

        restoreSelection: function () {
            this.focus();
            this._applyMark(this._saved);
        },

        selectedNode: function () {
            var range = this.getRange();

            if (!range) {
                return null;
            }

            var node = range.startContainer;

            return node.nodeType === 3 ? node.parentNode : node;
        },

        closest: function (selector) {
            var node = this.selectedNode();

            return node && node.closest ? node.closest(selector) : null;
        },

        selectNode: function (node) {
            var range = this.doc.createRange();

            range.selectNode(node);
            this.focus();
            this.setRange(range);
            this._updateState();
        },

        focus: function () {
            this.win.focus();
            this.body.focus();
        },

        /* ── Команды ─────────────────────────────────────────────────── */

        exec: function (name, value) {
            var command = commands[name];

            this.focus();

            if (command) {
                command.call(this, this, value);
            } else {
                this.native(name, value);
            }

            this._snapshot();
            this._markEmpty();
            this.save();
            this._updateState();
            this.emit('change');
        },

        /**
         * Обёртка над document.execCommand.
         *
         * Метод помечен как устаревший, но замены ему нет ни в одном браузере:
         * начертания и списки в contenteditable другого способа не имеют, и на
         * нём же держатся все живые редакторы. Свои операции (таблицы, блоки,
         * медиа) написаны на Range и сюда не заходят.
         */
        native: function (name, value) {
            try {
                this.doc.execCommand(name, false, value === undefined ? null : value);
            } catch (error) {
                if (window.console) {
                    window.console.warn('RuEditor: команда ' + name + ' не выполнена', error);
                }
            }
        },

        queryState: function (name) {
            try {
                return this.doc.queryCommandState(name);
            } catch (error) {
                return false;
            }
        },

        queryValue: function (name) {
            try {
                return this.doc.queryCommandValue(name);
            } catch (error) {
                return '';
            }
        },

        insertHtml: function (html) {
            this.focus();
            this.native('insertHTML', html);
        },

        insertText: function (text) {
            this.focus();
            this.native('insertText', text);
        },

        /**
         * Вставить готовый узел на место курсора и вернуть его.
         * insertHTML вернуть узел не может, а он нужен: только что вставленную
         * картинку надо сразу выделить, чтобы автор увидел её настройки.
         */
        insertNode: function (node) {
            var range = this.getRange();

            if (!range) {
                this.body.appendChild(node);
                return node;
            }

            // Курсор может стоять в узле, который такое содержимое держать не
            // умеет: в конце списка это сам <ul>, и картинка вставала между
            // пунктами как прямой ребёнок списка — разметка невалидная, а на
            // сайте такой узел вылезал за границы блока. Поднимаемся до
            // ближайшего места, где вставка законна.
            var host = range.startContainer;

            host = host.nodeType === 3 ? host.parentNode : host;

            if (/^(UL|OL|TABLE|THEAD|TBODY|TFOOT|TR|DL)$/.test(host.nodeName)) {
                var outer = host;

                while (outer.parentNode && outer.parentNode !== this.body &&
                       /^(UL|OL|TABLE|THEAD|TBODY|TFOOT|TR|DL|LI|TD|TH)$/.test(outer.parentNode.nodeName)) {
                    outer = outer.parentNode;
                }

                outer.parentNode.insertBefore(node, outer.nextSibling);

                var placed = this.doc.createRange();

                placed.setStartAfter(node);
                placed.collapse(true);
                this.setRange(placed);

                return node;
            }

            range.deleteContents();
            range.insertNode(node);

            var after = this.doc.createRange();

            after.setStartAfter(node);
            after.collapse(true);
            this.setRange(after);

            return node;
        },

        /* ── Содержимое ──────────────────────────────────────────────── */

        getContent: function () {
            if (this.sourceMode) {
                return this.code.value;
            }

            return RuEditor.cleanOutput(this.body.innerHTML);
        },

        setContent: function (html, options) {
            this.body.innerHTML = html || '';
            this._chipify();

            // Пустой документ без блока: курсор в голом body ведёт себя
            // непредсказуемо, Enter родит текстовый узел без обёртки.
            if (!this.body.firstChild) {
                this.body.appendChild(this.doc.createElement('p'));
            }

            this._markEmpty();

            if (!options || !options.silent) {
                this._snapshot();
                this.save();
                this._updateState();
                this.emit('change');
            }
        },

        save: function () {
            if (this._ensureFrame()) {
                return;
            }

            this.textarea.value = this.getContent();
        },

        /**
         * Страховка от подменённого документа рамки.
         *
         * Браузер пересоздаёт документ <iframe>, если узел перенесли в другое
         * место дерева. Ядро при этом продолжает читать и писать в ПРЕЖНИЙ,
         * уже отсоединённый документ: на экране пусто, а по всем внутренним
         * проверкам содержимое на месте — ошибку не видно ниоткуда. Ровно так
         * пропадал текст при попытке вынести редактор в body на время полного
         * экрана.
         *
         * Возвращаем true, если пришлось собирать заново. Содержимое берётся
         * из поля формы: оно обновляется при каждом изменении, то есть
         * теряется в худшем случае последняя пара секунд набора.
         */
        _ensureFrame: function () {
            if (this.doc === this.frame.contentDocument) {
                return false;
            }

            if (window.console) {
                window.console.warn('RuEditor: документ рамки подменён, собираю заново');
            }

            // Плагины рисуют свои плашки в оболочке; при повторной сборке они
            // добавятся снова, поэтому старые убираем.
            Array.prototype.forEach.call(this.shell.querySelectorAll('.ru-ed-bubble'), function (node) {
                node.remove();
            });

            this.plugins = Object.create(null);
            this.history = { stack: [], index: -1, limit: this.history.limit };
            this._boot();

            return true;
        },

        /**
         * Превратить шорткоды в тексте в наглядные плашки.
         *
         * В базе лежит обычный текст [captcha preset="x"] — его раскрывает
         * render_shortcodes() при выводе материала. Посреди абзаца такой текст
         * легко принять за опечатку и случайно испортить по букве, поэтому в
         * редакторе он показывается цельной неделимой плашкой. Обратное
         * превращение делает cleanOutput: наружу уходит снова текст.
         */
        _chipify: function () {
            var names = RuEditor.shortcodes;

            if (!names.length) {
                return;
            }

            var pattern = new RegExp('\\[(?:' + names.join('|') + ')\\b[^\\]]*\\]', 'g');
            var walker = this.doc.createTreeWalker(this.body, window.NodeFilter.SHOW_TEXT, null, false);
            var found = [];
            var node;

            while ((node = walker.nextNode())) {
                if (pattern.test(node.nodeValue)) {
                    found.push(node);
                }
                pattern.lastIndex = 0;
            }

            var doc = this.doc;

            found.forEach(function (textNode) {
                var parts = textNode.nodeValue.split(pattern);
                var codes = textNode.nodeValue.match(pattern) || [];
                var fragment = doc.createDocumentFragment();

                parts.forEach(function (part, index) {
                    if (part) {
                        fragment.appendChild(doc.createTextNode(part));
                    }

                    if (codes[index]) {
                        var chip = doc.createElement('span');

                        chip.setAttribute('data-ru-shortcode', codes[index]);
                        chip.setAttribute('contenteditable', 'false');
                        chip.textContent = codes[index];
                        fragment.appendChild(chip);
                    }
                });

                textNode.parentNode.replaceChild(fragment, textNode);
            });
        },

        /* ── Простая шина событий ────────────────────────────────────── */

        on: function (event, handler) {
            (this.listeners[event] = this.listeners[event] || []).push(handler);

            return this;
        },

        emit: function (event, payload) {
            (this.listeners[event] || []).forEach(function (handler) {
                handler(payload);
            });
        },

        destroy: function () {
            this.save();
            this.textarea.classList.remove('ru-ed-source');
            this.root.parentNode.insertBefore(this.textarea, this.root);
            this.root.remove();
            instances = instances.filter(function (item) {
                return item !== this;
            }, this);
        }
    };

    /* ── Очистка разметки ────────────────────────────────────────────── */

    // Что вообще разрешено хранить. Список намеренно узкий: редактор пишет в
    // базу, а оттуда содержимое выводится на сайт через {!! !!} — всё, что
    // сюда попало, окажется в браузере посетителя как разметка.
    var ALLOWED = {
        tags: ('p,br,hr,h1,h2,h3,h4,h5,h6,strong,b,em,i,u,s,strike,sub,sup,mark,small,' +
               'blockquote,pre,code,kbd,ul,ol,li,dl,dt,dd,a,img,figure,figcaption,' +
               'table,thead,tbody,tfoot,tr,th,td,caption,colgroup,col,div,span,' +
               'details,summary,iframe,video,audio,source').split(','),
        attrs: ('class,id,href,src,alt,title,target,rel,width,height,style,colspan,rowspan,' +
                'data-hours,data-ru-shortcode,open,loading,allow,allowfullscreen,' +
                'frameborder,srcset,sizes,type,start,reversed,datetime,aria-hidden,aria-label,' +
                // Настройки проигрывателей. Без них чистка снимала бы всё, что
                // автор выставил в диалоге, и ролик сохранялся бы голым.
                'controls,autoplay,loop,muted,preload,poster,playsinline,download,controlslist').split(',')
    };

    function sanitize(root, allow) {
        var tags = allow && allow.tags ? allow.tags : ALLOWED.tags;
        var attrs = allow && allow.attrs ? allow.attrs : ALLOWED.attrs;

        Array.prototype.slice.call(root.querySelectorAll('*')).forEach(function (node) {
            var name = node.tagName.toLowerCase();

            if (name === 'script' || name === 'style' || name === 'link' || name === 'meta') {
                node.remove();
                return;
            }

            if (tags.indexOf(name) === -1) {
                // Неизвестный тег разворачиваем, а не удаляем: текст внутри
                // писал автор, терять его нельзя.
                while (node.firstChild) {
                    node.parentNode.insertBefore(node.firstChild, node);
                }
                node.remove();
                return;
            }

            Array.prototype.slice.call(node.attributes).forEach(function (attr) {
                var attrName = attr.name.toLowerCase();

                // Любой обработчик события — это чужой код на странице сайта.
                if (attrName.slice(0, 2) === 'on' || attrs.indexOf(attrName) === -1) {
                    node.removeAttribute(attr.name);
                    return;
                }

                if ((attrName === 'href' || attrName === 'src') &&
                    /^\s*javascript:/i.test(attr.value)) {
                    node.removeAttribute(attr.name);
                }
            });
        });

        return root;
    }

    /* ── Публичный API ───────────────────────────────────────────────── */

    var RuEditor = {

        version: '1.0.0',

        instances: instances,

        /**
         * Шорткоды, которые редактор показывает плашкой вместо голого текста.
         * Список тот же, что понимает render_shortcodes() в app/helpers.php —
         * перечислен явно, чтобы обычный текст в квадратных скобках («[см.
         * приложение 2]») случайно не превратился в плашку.
         */
        shortcodes: ['captcha', 'form', 'map', 'sitemap'],

        /** Завести редактор на textarea. */
        create: function (target, options) {
            var textarea = typeof target === 'string' ? document.querySelector(target) : target;

            if (!textarea || textarea.dataset.ruEditorReady) {
                return null;
            }

            textarea.dataset.ruEditorReady = '1';

            return new Editor(textarea, options);
        },

        /** Поднять редактор на всех полях, помеченных селектором. */
        boot: function (selector, options) {
            var made = [];

            Array.prototype.forEach.call(document.querySelectorAll(selector), function (node) {
                var editor = RuEditor.create(node, options);

                if (editor) {
                    made.push(editor);
                }
            });

            return made;
        },

        /** Редактор, в котором последний раз стоял курсор. */
        active: function () {
            return RuEditor.activeEditor || instances[0] || null;
        },

        activeEditor: null,

        get: function (id) {
            for (var i = 0; i < instances.length; i++) {
                if (instances[i].id === id) {
                    return instances[i];
                }
            }

            return null;
        },

        /**
         * Своя команда. Получает экземпляр редактора и значение; снимок для
         * отмены, синхронизацию с полем формы и обновление панели ядро делает
         * само — команде остаётся только изменить документ.
         */
        registerCommand: function (name, fn) {
            commands[name] = fn;

            return RuEditor;
        },

        /** Своя кнопка или выпадающий список для панели инструментов. */
        registerButton: function (name, def) {
            buttons[name] = def;

            return RuEditor;
        },

        /** Плагин: init(editor) вызывается при подъёме каждого редактора. */
        registerPlugin: function (name, def) {
            def.name = name;
            plugins.push(def);

            return RuEditor;
        },

        /** Словарь интерфейса — приходит из Blade, чтобы строки жили в PHP. */
        setStrings: function (map) {
            Object.keys(map || {}).forEach(function (key) {
                strings[key] = map[key];
            });

            return RuEditor;
        },

        t: t,
        el: el,
        escapeHtml: escapeHtml,
        debounce: debounce,

        /** Разметка, уходящая в поле формы, а оттуда в базу. */
        cleanOutput: function (html) {
            var box = document.createElement('div');

            box.innerHTML = html;

            // Плашки шорткодов разворачиваются обратно в текст: в базе должен
            // лежать сам шорткод, а не разметка, которой мы его показывали.
            Array.prototype.forEach.call(box.querySelectorAll('[data-ru-shortcode]'), function (chip) {
                chip.parentNode.replaceChild(
                    document.createTextNode(chip.getAttribute('data-ru-shortcode')),
                    chip
                );
            });

            // Разметка, нарисованная скриптом для показа, в материал не
            // попадает: проигрыватель звука собирается заново на каждой
            // странице, и сохранённая копия его панели дала бы на сайте две
            // панели сразу. Класс готовности снимаем по той же причине — с
            // ним родной проигрыватель спрятан, и не отработай скрипт на
            // сайте, от звука не осталось бы ничего.
            Array.prototype.forEach.call(box.querySelectorAll('[data-ru-transient]'), function (node) {
                node.remove();
            });

            Array.prototype.forEach.call(box.querySelectorAll('.is-ready'), function (node) {
                node.classList.remove('is-ready');

                if (!node.getAttribute('class')) {
                    node.removeAttribute('class');
                }
            });

            // Служебные пометки редактора наружу не уходят.
            Array.prototype.forEach.call(box.querySelectorAll('.ru-ed-selected'), function (node) {
                node.classList.remove('ru-ed-selected');

                if (!node.getAttribute('class')) {
                    node.removeAttribute('class');
                }
            });

            Array.prototype.forEach.call(box.querySelectorAll('[contenteditable]'), function (node) {
                node.removeAttribute('contenteditable');
            });

            // Приводим начертания к одному виду. Браузер по execCommand даёт
            // <b> и <i>, а в уже сохранённых материалах лежат <strong> и <em>:
            // без нормализации в одном документе оказывались оба написания, и
            // правила оформления, привязанные к тегу, срабатывали через раз.
            [['b', 'strong'], ['i', 'em'], ['strike', 's']].forEach(function (pair) {
                Array.prototype.forEach.call(box.querySelectorAll(pair[0]), function (node) {
                    var replacement = document.createElement(pair[1]);

                    Array.prototype.forEach.call(node.attributes, function (attr) {
                        replacement.setAttribute(attr.name, attr.value);
                    });

                    while (node.firstChild) {
                        replacement.appendChild(node.firstChild);
                    }

                    node.parentNode.replaceChild(replacement, node);
                });
            });

            sanitize(box);

            // Пустые style и class остаются после снятия оформления и только
            // мусорят разметку: style="" в исходном коде выглядит как забытый
            // хвост и сбивает при чтении.
            Array.prototype.forEach.call(box.querySelectorAll('[style=""],[class=""]'), function (node) {
                if (node.getAttribute('style') === '') {
                    node.removeAttribute('style');
                }
                if (node.getAttribute('class') === '') {
                    node.removeAttribute('class');
                }
            });

            // Пустой абзац, который браузер держит для курсора, в базе не нужен.
            //
            // «Пустой» = ни текста, ни вложенных узлов. Раньше проверялся
            // короткий список (img, iframe, hr), и материал из одного
            // проигрывателя или одной таблицы сохранялся как ПУСТАЯ строка:
            // всё, что автор вставил, молча пропадало при сохранении.
            var only = box.children.length === 1 ? box.firstElementChild : null;

            if (only && only.tagName === 'P' && !only.textContent.trim() && !only.querySelector(':not(br)')) {
                return '';
            }

            return box.innerHTML;
        },

        /** Разметка, приехавшая из буфера обмена. */
        cleanPastedHtml: function (html, allow) {
            var box = document.createElement('div');

            // Word шлёт свою разметку между условными комментариями, а внутри —
            // списки, собранные из абзацев с маркером-символом.
            box.innerHTML = String(html)
                .replace(/<!--[\s\S]*?-->/g, '')
                .replace(/<\/?(?:o|w|m|v):[^>]*>/gi, '');

            Array.prototype.forEach.call(box.querySelectorAll('*'), function (node) {
                // Классы чужого редактора («MsoNormal» и подобные) тянут за
                // собой оформление, которого на сайте нет.
                var className = node.getAttribute('class') || '';

                if (/mso|docs-internal|Apple-/i.test(className)) {
                    node.removeAttribute('class');
                }

                // Из встроенных стилей оставляем только то, что несёт смысл:
                // размер шрифта и семейство из Word ломают оформление сайта.
                var style = node.getAttribute('style');

                if (style) {
                    var keep = style.split(';').filter(function (rule) {
                        return /^\s*(text-align|color|background-color|width|height)\s*:/i.test(rule);
                    }).join(';');

                    if (keep) {
                        node.setAttribute('style', keep);
                    } else {
                        node.removeAttribute('style');
                    }
                }
            });

            sanitize(box, allow);

            return box.innerHTML;
        },

        sanitize: sanitize
    };

    window.RuEditor = RuEditor;
}(window, document));
