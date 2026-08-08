/* ============================================================================
   RU Editor — объекты в тексте: выбор, выравнивание, обтекание, удаление.
   ----------------------------------------------------------------------------
   Объект — это всё, что стоит в тексте как единое целое: картинка, ролик,
   звук, встроенный проигрыватель, таблица, плашка шорткода (форма, каптча).

   Зачем отдельный слой. Раньше каждая операция знала про объекты по-своему:
   растягивание искало обёртку как closest('figure'), выравнивание — как
   closest('figure, .pc-player'), панель у картинки ловила только тег IMG, а
   удаление не умело ничего. Наборы разъезжались, и «работает у картинки, но
   не у видео» было нормой. Теперь набор один и лежит здесь.

   ГЛАВНОЕ ПРАВИЛО ВЫРАВНИВАНИЯ: выравнивает всегда text-align у блока,
   который держит объект, а сам объект делается строчным, чтобы этому
   подчиняться. Никаких авто-полей, подгонки ширины и обтекания «заодно» —
   каждый из тех приёмов работал у одних вставок и молча не работал у других.

   Обтекание — ОТДЕЛЬНАЯ вещь и отдельные кнопки. «По центру» и «текст
   обтекает справа» отвечают на разные вопросы, и смешивать их в четырёх
   кнопках было ошибкой.
   ========================================================================= */

(function (window, document) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    /** Что считается объектом. */
    var OBJECTS = 'img,video,audio,iframe,table,[data-ru-shortcode]';

    /** Обёртки, которые двигаются вместо самого объекта. */
    var BOXES = 'figure,.pc-player';

    /** Блоки, которым можно задать выравнивание. */
    var BLOCKS = 'p,h1,h2,h3,h4,h5,h6,blockquote,li,td,th,div';

    /**
     * Коробка объекта — то, что двигаем и растягиваем.
     *
     * У картинки с подписью это figure: иначе подпись осталась бы на прежнем
     * месте и прежней ширины. У ролика и звука — обёртка проигрывателя:
     * сдвинув сам тег, мы сдвинули бы его внутри его же рамки.
     */
    function boxOf(node) {
        return (node && node.closest && node.closest(BOXES)) || node;
    }

    /**
     * Объект под выделением.
     *
     * Три способа, и все три нужны:
     *   • вверх по дереву — курсор стоит внутри объекта или его обёртки;
     *   • по самому диапазону — объект выделен целиком (щелчок по плашке
     *     шорткода делает именно так, а closest его не находит: он лежит
     *     ВНУТРИ выделения, а не над ним);
     *   • единственный объект в блоке — курсор рядом с ним в том же абзаце.
     */
    function objectAt(editor) {
        var node = editor.closest(OBJECTS);

        if (node) {
            return node;
        }

        var range = editor.getRange();

        if (!range) {
            return null;
        }

        if (range.startContainer === range.endContainer && range.endOffset - range.startOffset === 1) {
            var single = range.startContainer.childNodes && range.startContainer.childNodes[range.startOffset];

            if (single && single.nodeType === 1 && single.matches && single.matches(OBJECTS)) {
                return single;
            }
        }

        var block = editor.closest(BLOCKS);

        if (block && block !== editor.body && !textOutsideObjects(block)) {
            var inside = block.querySelectorAll(OBJECTS);

            if (inside.length === 1) {
                return inside[0];
            }
        }

        return null;
    }

    /**
     * Сделать коробку строчной, чтобы её двигало выравнивание.
     *
     * display подбирается по виду вставки, а не один на всех: у проигрывателя
     * звука внутри флекс-раскладка, и inline-block сломал бы её — нужен
     * inline-flex. У таблицы — inline-table.
     */
    function makeInline(box) {
        if (box.classList.contains('pc-audio')) {
            box.style.display = 'inline-flex';

            // У полосы звука НЕТ собственного размера: картинка, ролик и
            // таблица знают свою ширину сами, а проигрыватель — нет. Сжатие
            // по содержимому давало ему ровно ноль, от полосы оставались одни
            // поля в тридцать пикселей. Пока автор не задал ширину сам
            // (ручки по краям), полоса занимает строку целиком — и тогда
            // двигать её действительно нечем, кнопки честно ничего не меняют.
            if (!box.style.width) {
                // Вместе с border-box: у полосы есть поля и рамка, и без него
                // ширина в сто процентов вылезала бы за край текста на их
                // толщину — тридцать пикселей вправо.
                box.style.boxSizing = 'border-box';
                box.style.width = '100%';
            }

            return;
        }

        if (box.tagName === 'TABLE') {
            box.style.display = 'inline-table';
            return;
        }

        // Картинка и плашка шорткода строчные сами по себе — трогать их
        // display незачем, лишний inline-block только мешал бы подписи.
        if (box.tagName === 'IMG' || (box.matches && box.matches('[data-ru-shortcode]'))) {
            return;
        }

        box.style.display = 'inline-block';

        // Строчный элемент шире строки её не переполняет, но и не сжимается.
        // Без этого широкая вставка вылезала бы за край текста.
        if (!box.style.maxWidth) {
            box.style.maxWidth = '100%';
        }
    }

    /**
     * Блок, которому задаём выравнивание.
     *
     * Если коробка лежит прямо в теле документа, блока над ней нет —
     * заворачиваем. Выравнивать тело нельзя: text-align у него достался бы
     * ВСЕМУ содержимому материала разом.
     */
    function holderOf(editor, box) {
        var parent = box.parentElement;

        if (parent && parent !== editor.body) {
            return parent;
        }

        var holder = editor.doc.createElement('div');

        box.parentNode.insertBefore(holder, box);
        holder.appendChild(box);

        return holder;
    }

    /** Блоки, которых касается выделение. */
    function blocksInSelection(editor) {
        var range = editor.getRange();
        var one = editor.closest(BLOCKS);

        if (!range || range.collapsed) {
            return one && one !== editor.body ? [one] : [];
        }

        var found = [];

        Array.prototype.forEach.call(editor.body.querySelectorAll(BLOCKS), function (block) {
            // Берём только самые вложенные блоки: иначе выравнивание достанется
            // и обёртке, и её содержимому, а это разные вещи.
            if (!block.querySelector(BLOCKS) && range.intersectsNode(block)) {
                found.push(block);
            }
        });

        if (!found.length && one && one !== editor.body) {
            found.push(one);
        }

        return found;
    }

    /**
     * Выравнивание чего угодно.
     *
     * Своё, вместо браузерной команды. execCommand делает то же самое, но
     * только для текста: у выделенной плашки шорткода он молчит (узел помечен
     * contenteditable="false"), в пустом абзаце ему нечего выравнивать, а
     * блочную вставку он не двигает вовсе. Одна своя реализация предсказуемее
     * трёх обходных путей вокруг него.
     */
    function align(editor, mode) {
        var value = mode || 'justify';
        var node = objectAt(editor);

        if (node) {
            var box = boxOf(node);

            makeInline(box);

            // Обтекание — отдельная настройка, и выравнивание её снимает:
            // у плавающего элемента положение в строке не имеет смысла.
            box.style.float = '';

            // Боковые поля обнуляем явно, а не «сбрасываем в умолчание»:
            // у figure браузер по умолчанию ставит 40px с каждой стороны, и
            // прижатая к краю картинка висела бы в сорока пикселях от текста.
            box.style.marginLeft = '0';
            box.style.marginRight = '0';

            holderOf(editor, box).style.textAlign = value;

            // Возвращаем выделение на объект. Заворачивание коробки в блок
            // переносит узел, а перенос узла рвёт выделение: курсор уезжал в
            // тело документа, и следующее нажатие кнопки било уже мимо —
            // подсветка гасла, будто выравнивание не применилось.
            editor.selectNode(node);
            editor.selectedMedia = node;
        } else {
            var blocks = blocksInSelection(editor);

            if (!blocks.length) {
                return false;
            }

            blocks.forEach(function (block) {
                block.style.textAlign = value;

                // В пустом блоке курсору не за что зацепиться, и он остаётся
                // у левого края, хотя выравнивание уже задано. Перенос строки
                // даёт ему место — тогда набор начинается там, где показано.
                if (!block.firstChild) {
                    block.appendChild(editor.doc.createElement('br'));
                }
            });
        }

        editor._snapshot();
        editor.save();
        editor._updateState();

        return true;
    }

    /**
     * Какое выравнивание сейчас в силе — по РАЗМЕТКЕ.
     *
     * Спрашивать браузер (queryCommandState) нельзя: он знает только про
     * выравнивание текста. У плашки шорткода не горела ни одна кнопка, а у
     * отцентрованной картинки горела «по левому краю» — заведомо не та.
     */
    function alignState(editor) {
        var node = objectAt(editor);
        var block;

        if (node) {
            var box = boxOf(node);

            if (box.style.float === 'left') {
                return 'left';
            }

            if (box.style.float === 'right') {
                return 'right';
            }

            block = box.parentElement;
        } else {
            block = editor.closest(BLOCKS);
        }

        if (!block || block === editor.body || !block.isConnected) {
            return 'left';
        }

        var value = editor.doc.defaultView.getComputedStyle(block).textAlign;

        // start/end — «как в языке»; для письма слева направо начало слева.
        if (!value || value === 'start') {
            return 'left';
        }

        if (value === 'end') {
            return 'right';
        }

        return value === 'justify-all' ? 'justify' : value;
    }

    /**
     * Обтекание: текст идёт рядом с объектом, а не под ним.
     *
     * Отдельно от выравнивания намеренно. Плавающий объект вынут из потока —
     * «по центру» для него не существует в принципе, и объединять их в один
     * набор кнопок значит обещать сочетания, которых не бывает.
     */
    function wrap(editor, side) {
        var node = objectAt(editor);

        if (!node) {
            return false;
        }

        var box = boxOf(node);

        box.style.float = '';
        box.style.marginLeft = '';
        box.style.marginRight = '';

        if (side === 'left' || side === 'right') {
            // Плавающий элемент и так сжимается по содержимому, поэтому
            // подгонять ширину не нужно — в отличие от блока в потоке.
            box.style.display = box.classList.contains('pc-audio') ? 'flex' : '';
            box.style.float = side;

            // Зазор только со стороны текста: с внешней стороны отступ
            // прижал бы объект к середине, хотя просили к краю. У figure
            // браузер по умолчанию ставит 40px с обеих сторон — снимаем.
            box.style.marginLeft = side === 'left' ? '0' : '1rem';
            box.style.marginRight = side === 'left' ? '1rem' : '0';
        } else {
            makeInline(box);
        }

        editor.selectNode(node);
        editor.selectedMedia = node;

        editor._snapshot();
        editor.save();
        editor._updateState();

        return true;
    }

    /** Обтекание, которое сейчас задано. */
    function wrapState(editor) {
        var node = objectAt(editor);

        return node ? (boxOf(node).style.float || 'none') : null;
    }

    /**
     * Удаление объекта вместе с его обёрткой и опустевшим блоком.
     *
     * Убрать один тег мало: от ролика осталась бы пустая рамка обёртки, а от
     * картинки с подписью — подпись без картинки.
     */
    function removeObject(editor, node) {
        var box = boxOf(node);
        var holder = box.parentElement;
        var after = holder && holder.nextSibling;

        box.remove();

        // Блок, в котором кроме объекта ничего не было, тоже уходит: иначе в
        // материале копятся пустые строки, которых не видно в редакторе, но
        // которые занимают место на сайте.
        if (holder && holder !== editor.body && !textOutsideObjects(holder) && !holder.querySelector(OBJECTS)) {
            after = holder.nextSibling;
            holder.remove();
        }

        // Документ не оставляем совсем пустым: без блока курсору негде стоять,
        // и следующий же набор текста уходит прямо в тело документа.
        if (!editor.body.firstChild) {
            var empty = editor.doc.createElement('p');

            empty.appendChild(editor.doc.createElement('br'));
            editor.body.appendChild(empty);
            after = empty;
        }

        var caret = editor.doc.createRange();

        caret.setStart(after && after.parentNode ? after : editor.body.lastChild, 0);
        caret.collapse(true);
        editor.setRange(caret);

        editor._snapshot();
        editor.save();
        editor._updateState();
    }

    /** Сосед-объект вплотную к схлопнутому курсору. */
    function neighbourObject(editor, forward) {
        var range = editor.getRange();

        if (!range || !range.collapsed) {
            return null;
        }

        var block = editor.closest(BLOCKS);
        var text = block ? block.textContent : '';

        // Только на самом краю блока: в середине текста клавиша должна
        // удалять букву, а не утаскивать соседнюю картинку.
        if (text.trim() && !atEdge(editor, range, block, forward)) {
            return null;
        }

        var from = block && block !== editor.body ? block : range.startContainer;
        var sibling = forward ? from.nextElementSibling : from.previousElementSibling;

        if (!sibling) {
            return null;
        }

        if (sibling.matches(OBJECTS) || sibling.matches(BOXES)) {
            return sibling;
        }

        var inside = sibling.querySelectorAll(OBJECTS);

        return inside.length === 1 && !textOutsideObjects(sibling) ? inside[0] : null;
    }

    /**
     * Текст блока БЕЗ учёта самих объектов.
     *
     * У плашки шорткода есть своя подпись («Форма», «Каптча»), и обычная
     * проверка на пустоту блока считала её текстом — абзац с одной плашкой
     * выглядел «непустым», и клавиша удаления его не трогала.
     */
    function textOutsideObjects(block) {
        var copy = block.cloneNode(true);

        Array.prototype.forEach.call(copy.querySelectorAll(OBJECTS), function (node) {
            node.remove();
        });

        return copy.textContent.trim();
    }

    /** Курсор у самого края блока. */
    function atEdge(editor, range, block, forward) {
        if (!block) {
            return false;
        }

        var probe = editor.doc.createRange();

        probe.selectNodeContents(block);

        if (forward) {
            probe.setStart(range.endContainer, range.endOffset);
        } else {
            probe.setEnd(range.startContainer, range.startOffset);
        }

        return !probe.toString().trim();
    }

    RuEditor.objects = {
        OBJECTS: OBJECTS,
        BOXES: BOXES,
        boxOf: boxOf,
        objectAt: objectAt,
        align: align,
        alignState: alignState,
        wrap: wrap,
        wrapState: wrapState,
        remove: removeObject
    };

    /* ── Выбор объекта и удаление с клавиатуры ───────────────────────── */

    RuEditor.registerPlugin('objects', {
        init: function (editor) {
            editor.doc.addEventListener('click', function (event) {
                var node = event.target.closest && event.target.closest(OBJECTS);

                if (!node || !editor.body.contains(node)) {
                    return;
                }

                // Плашка шорткода помечена contenteditable="false", и щелчок
                // по ней НЕ ставит курсор: выделение остаётся где было. Без
                // явного выделения кнопкам панели нечего выравнивать.
                editor.selectNode(node);
                editor.selectedMedia = node;
                editor._updateState();
            });

            editor.doc.addEventListener('keydown', function (event) {
                if (event.key !== 'Delete' && event.key !== 'Backspace') {
                    return;
                }

                var selected = objectAt(editor);

                if (selected) {
                    event.preventDefault();
                    removeObject(editor, selected);
                    return;
                }

                var neighbour = neighbourObject(editor, event.key === 'Delete');

                if (neighbour) {
                    event.preventDefault();
                    removeObject(editor, neighbour);
                }
            });
        }
    });
}(window, document));
