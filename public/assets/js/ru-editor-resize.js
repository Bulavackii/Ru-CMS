/* ============================================================================
   RU Editor — растягивание вставок мышью.
   ----------------------------------------------------------------------------
   Как в привычных редакторах: щёлкнул по картинке, видео, звуку или ролику —
   вокруг появилась рамка с восемью ручками, тянешь за любую грань или угол.

   Ручки живут в документе СТРАНИЦЫ, а содержимое — внутри рамки редактора
   (отдельный документ). Поэтому координаты переводятся из одной системы в
   другую, а слушатели мыши вешаются на ОБА документа: указатель во время
   перетаскивания оказывается то над страницей, то над рамкой, и события идут
   то туда, то сюда.
   ========================================================================= */

(function (window, document) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    // Что вообще можно тянуть. figure не входит: у неё тянут вложенную
    // картинку, а размер получает обёртка — так подпись остаётся по ширине
    // картинки, а не растягивается на всю строку.
    var TARGETS = 'img,video,iframe,audio,table';

    // Стороны и углы. Первая буква — вертикаль, вторая — горизонталь.
    var HANDLES = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];

    RuEditor.registerPlugin('resize', {
        init: function (editor) {
            var frame = el('div', { class: 'ru-ed-resize', hidden: true });
            var target = null;
            var drag = null;

            HANDLES.forEach(function (side) {
                var handle = el('span', { class: 'ru-ed-handle is-' + side, 'data-side': side });

                handle.addEventListener('mousedown', function (event) {
                    start(event, side);
                });

                frame.appendChild(handle);
            });

            // Подпись с размером — видно, что получится, не отпуская кнопку.
            var badge = el('span', { class: 'ru-ed-resize-size' });

            frame.appendChild(badge);
            editor.shell.appendChild(frame);

            editor.doc.addEventListener('mousedown', function (event) {
                var node = (event.target.closest && event.target.closest(TARGETS)) ||
                           embedAt(editor, event.clientX, event.clientY);

                if (!node) {
                    hide();
                    return;
                }

                target = node;
                // Выбранную вставку видит и остальной редактор: кнопки
                // выравнивания на панели должны двигать именно её, а не абзац,
                // в котором она лежит.
                editor.selectedMedia = node;
                place();
            });

            editor.doc.addEventListener('scroll', place, true);
            window.addEventListener('resize', place);
            editor.on('change', function () {
                if (target && !target.isConnected) {
                    hide();
                }
            });

            /* ── Собственно перетаскивание ───────────────────────────── */

            function start(event, side) {
                if (!target) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                var box = target.getBoundingClientRect();

                drag = {
                    side: side,
                    x: event.clientX,
                    y: event.clientY,
                    width: box.width,
                    height: box.height,
                    // Пропорция берётся с ЭКРАНА, а не из атрибутов: у уже
                    // растянутой вставки атрибуты хранят исходный размер, и
                    // угол начинал бы тянуть с чужой пропорции.
                    ratio: box.height ? box.width / box.height : 0,
                    // Пропорцию углом держат только вставки с собственным
                    // соотношением сторон. У проигрывателя звука и таблицы его
                    // нет: там обе стороны тянутся независимо.
                    keepRatio: /^(IMG|VIDEO|IFRAME)$/.test(target.tagName)
                };

                document.body.classList.add('ru-ed-resizing');
                bind(true);
            }

            function move(event) {
                if (!drag) {
                    return;
                }

                var dx = event.clientX - drag.x;
                var dy = event.clientY - drag.y;

                if (drag.side.indexOf('w') !== -1) {
                    dx = -dx;
                }
                if (drag.side.indexOf('n') !== -1) {
                    dy = -dy;
                }

                var width = drag.width;
                var height = drag.height;
                var horizontal = /[ew]/.test(drag.side);
                var vertical = /[ns]/.test(drag.side);

                if (horizontal) {
                    width = Math.max(40, drag.width + dx);
                }
                if (vertical) {
                    height = Math.max(24, drag.height + dy);
                }

                // Угол держит пропорцию, если не зажат Shift: перекошенная
                // картинка — почти всегда промах, а не замысел.
                var locked = horizontal && vertical && drag.ratio && drag.keepRatio && !event.shiftKey;

                if (locked) {
                    height = width / drag.ratio;
                }

                var limit = editor.body.clientWidth;

                if (width > limit) {
                    width = limit;

                    if (locked) {
                        height = width / drag.ratio;
                    }
                }

                apply(Math.round(width), Math.round(height), horizontal, vertical);
                place();
            }

            function stop() {
                if (!drag) {
                    return;
                }

                drag = null;
                document.body.classList.remove('ru-ed-resizing');
                bind(false);

                editor._snapshot();
                editor.save();
            }

            function bind(on) {
                var method = on ? 'addEventListener' : 'removeEventListener';

                // Оба документа: пока тянут, указатель ходит и по странице, и
                // по рамке, и события достаются то одному, то другому.
                [document, editor.doc].forEach(function (doc) {
                    doc[method]('mousemove', move);
                    doc[method]('mouseup', stop);
                });
            }

            function apply(width, height, horizontal, vertical) {
                // Та же коробка, что у выравнивания и удаления. Раньше здесь
                // искалась только figure, и ширина ролика уходила на сам тег
                // мимо его обёртки — размер и положение спорили друг с другом.
                var node = target.closest(RuEditor.objects.BOXES) || target;

                if (horizontal) {
                    // Проценты, а не пиксели: материал читают и с телефона.
                    // Ширину считаем от видимой ширины текста.
                    var percent = Math.round((width / editor.body.clientWidth) * 1000) / 10;

                    node.style.width = Math.min(100, percent) + '%';

                    if (node !== target) {
                        target.style.width = '100%';
                    }

                    target.removeAttribute('width');
                }

                if (vertical) {
                    target.style.height = height + 'px';
                    target.removeAttribute('height');
                } else if (horizontal && drag.keepRatio) {
                    // Тянут за бок вставку с собственной пропорцией — высота
                    // идёт следом, иначе картинка сплющивается по мере сужения.
                    // У проигрывателя звука и таблицы высоту, наоборот, не
                    // трогаем: заданную вручную сбрасывать нельзя.
                    target.style.height = 'auto';
                }

                badge.textContent = width + ' x ' + height;
            }

            /* ── Положение рамки с ручками ───────────────────────────── */

            function place() {
                if (!target || !target.isConnected) {
                    hide();
                    return;
                }

                var box = target.getBoundingClientRect();
                var frameBox = editor.frame.getBoundingClientRect();
                var shellBox = editor.shell.getBoundingClientRect();
                var top = (frameBox.top - shellBox.top) + box.top;
                var left = (frameBox.left - shellBox.left) + box.left;

                // Не вылезаем за пределы видимой части рамки: иначе ручки
                // висят поверх панели инструментов и соседних полей формы.
                if (box.bottom < 0 || box.top > frameBox.height) {
                    frame.setAttribute('hidden', '');
                    return;
                }

                frame.removeAttribute('hidden');
                frame.style.top = top + 'px';
                frame.style.left = left + 'px';
                frame.style.width = box.width + 'px';
                frame.style.height = box.height + 'px';

                if (!drag) {
                    badge.textContent = Math.round(box.width) + ' x ' + Math.round(box.height);
                }
            }

            function hide() {
                target = null;
                editor.selectedMedia = null;
                frame.setAttribute('hidden', '');
            }

            editor.on('resize-target', function (node) {
                target = node;
                place();
            });
        }
    });

    /**
     * Встроенный ролик под курсором.
     *
     * <iframe> с чужой площадки забирает мышь себе: нажатие уходит в документ
     * ролика, и выделить его обычным способом невозможно. Внутри редактора
     * таким вставкам отключён приём мыши (правило в стилях рамки), поэтому
     * нажатие достаётся контейнеру — а какой именно ролик под курсором,
     * выясняем по координатам.
     *
     * Служебной разметки при этом не появляется: класть поверх прозрачные
     * накладки значило бы потом вычищать их перед каждым сохранением и
     * однажды забыть.
     */
    function embedAt(editor, x, y) {
        var found = null;

        Array.prototype.forEach.call(editor.body.querySelectorAll('iframe,video,audio'), function (node) {
            var box = node.getBoundingClientRect();

            if (x >= box.left && x <= box.right && y >= box.top && y <= box.bottom) {
                found = node;
            }
        });

        return found;
    }
}(window, document));
