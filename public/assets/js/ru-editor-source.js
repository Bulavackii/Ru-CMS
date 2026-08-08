/* ============================================================================
   RU Editor — правка исходного кода.
   ----------------------------------------------------------------------------
   Подсветка, нумерация строк, форматирование и проверка парности тегов.

   Почему своё, а не готовая библиотека. Сторонние редакторы кода — это либо
   мегабайты со сборщиком, либо загрузка с чужого адреса; в этом проекте нет
   ни того, ни другого: свой редактор писался как раз чтобы уйти от чужой
   сборки, а выход в интернет закрыт наглухо. Здесь нужен не редактор кода
   общего назначения, а подсветка ОДНОГО языка — разметки материала. На это
   хватает пары сотен строк.

   Как устроена подсветка. Под полем ввода лежит слой с той же разметкой, но
   раскрашенной; сам текст в поле прозрачный, виден только курсор. Так
   остаётся родная правка со всем, что к ней прилагается: отмена, выделение,
   перенос слов, ввод с экранной клавиатуры. Переписывать это ради цвета было
   бы плохой сделкой.

   Отсюда главное требование: слой и поле обязаны совпадать до пикселя —
   шрифт, кегль, межстрочное расстояние, поля, перенос слов и ширина
   табуляции. Разойдутся хоть в чём-то — подсветка «поедет» относительно
   текста. Всё это задано в одном месте, классом .ru-ed-src-text.
   ========================================================================= */

(function (window, document) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /** Теги, которые живут внутри строки и не заслуживают своей строки. */
    var INLINE = ('a,abbr,b,bdi,bdo,br,cite,code,data,dfn,em,i,img,kbd,mark,q,' +
                  's,samp,small,span,strong,sub,sup,time,u,var,wbr').split(',');

    /** Теги без закрывающей пары. */
    var VOID = 'area,base,br,col,embed,hr,img,input,link,meta,param,source,track,wbr'.split(',');

    /** Содержимое этих тегов переносить нельзя — в нём значимы пробелы. */
    var VERBATIM = ['pre', 'textarea'];

    /** Порог, за которым подсветка отключается. */
    var LIMIT = 200000;

    function esc(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function wrap(kind, text) {
        return '<span class="rs-' + kind + '">' + esc(text) + '</span>';
    }

    /** Конец тега с учётом кавычек: внутри значения может встретиться «>». */
    function tagEnd(code, from) {
        var quote = '';

        for (var i = from + 1; i < code.length; i++) {
            var ch = code[i];

            if (quote) {
                if (ch === quote) {
                    quote = '';
                }
                continue;
            }

            if (ch === '"' || ch === "'") {
                quote = ch;
            } else if (ch === '>') {
                return i + 1;
            }
        }

        return code.length;
    }

    /** Раскраска одного тега: имя, имена свойств, значения. */
    function paintTag(src) {
        var open = /^<\/?[a-zA-Z][\w:-]*/.exec(src);

        if (!open) {
            return esc(src);
        }

        var out = wrap('tag', open[0]);
        var rest = src.slice(open[0].length);
        var re = /([a-zA-Z_:][\w:.-]*)(\s*=\s*)("[^"]*"|'[^']*'|[^\s"'>`]+)?|(\/?>)|(\s+)|([^\s]+)/g;
        var m;

        while ((m = re.exec(rest))) {
            if (m[1]) {
                out += wrap('attr', m[1]);

                if (m[2]) {
                    out += wrap('punc', m[2]);
                }

                if (m[3]) {
                    out += wrap('val', m[3]);
                }
            } else if (m[4]) {
                out += wrap('tag', m[4]);
            } else if (m[5]) {
                out += esc(m[5]);
            } else if (m[6]) {
                out += esc(m[6]);
            }
        }

        return out;
    }

    /**
     * Разметка → раскрашенная разметка.
     *
     * Разбор посимвольный, а не набором замен по всему тексту: замены путают
     * содержимое с разметкой, и «<» в обычном тексте окрашивался бы как
     * начало тега. Здесь же в каждый момент известно, что читаем.
     */
    function highlight(code) {
        if (code.length > LIMIT) {
            return esc(code);
        }

        var out = '';
        var i = 0;

        while (i < code.length) {
            var ch = code[i];

            if (ch === '<') {
                if (code.substr(i, 4) === '<!--') {
                    var cEnd = code.indexOf('-->', i + 4);

                    cEnd = cEnd === -1 ? code.length : cEnd + 3;
                    out += wrap('cmt', code.slice(i, cEnd));
                    i = cEnd;
                    continue;
                }

                if (code.substr(i, 2) === '<!') {
                    var dEnd = code.indexOf('>', i);

                    dEnd = dEnd === -1 ? code.length : dEnd + 1;
                    out += wrap('doc', code.slice(i, dEnd));
                    i = dEnd;
                    continue;
                }

                if (/^<\/?[a-zA-Z]/.test(code.slice(i, i + 3))) {
                    var end = tagEnd(code, i);

                    out += paintTag(code.slice(i, end));
                    i = end;
                    continue;
                }
            }

            if (ch === '&') {
                var ent = /^&(#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i.exec(code.slice(i));

                if (ent) {
                    out += wrap('ent', ent[0]);
                    i += ent[0].length;
                    continue;
                }
            }

            // Шорткоды подсвечиваем отдельно: в этом проекте они часть
            // содержимого, и отличать их от обычного текста в скобках полезно.
            if (ch === '[') {
                var sc = /^\[\/?[a-z][a-z0-9_-]*(\s[^\]\n]*)?\]/i.exec(code.slice(i));

                if (sc) {
                    out += wrap('sc', sc[0]);
                    i += sc[0].length;
                    continue;
                }
            }

            var next = i + 1;

            while (next < code.length && '<&['.indexOf(code[next]) === -1) {
                next++;
            }

            out += esc(code.slice(i, next));
            i = next;
        }

        return out;
    }

    /** Разбор разметки на части: теги, текст, комментарии. */
    function tokens(code) {
        var list = [];
        var i = 0;

        while (i < code.length) {
            var lt = code.indexOf('<', i);

            if (lt === -1) {
                list.push({ type: 'text', value: code.slice(i) });
                break;
            }

            if (lt > i) {
                list.push({ type: 'text', value: code.slice(i, lt) });
            }

            if (code.substr(lt, 4) === '<!--') {
                var cEnd = code.indexOf('-->', lt + 4);

                cEnd = cEnd === -1 ? code.length : cEnd + 3;
                list.push({ type: 'comment', value: code.slice(lt, cEnd) });
                i = cEnd;
                continue;
            }

            if (!/^<\/?[a-zA-Z!]/.test(code.slice(lt, lt + 3))) {
                list.push({ type: 'text', value: '<' });
                i = lt + 1;
                continue;
            }

            var end = tagEnd(code, lt);
            var raw = code.slice(lt, end);
            var name = (/^<\/?([a-zA-Z][\w:-]*)/.exec(raw) || [])[1] || '';

            list.push({
                type: raw[1] === '/' ? 'close' : 'open',
                name: name.toLowerCase(),
                value: raw,
                self: /\/>$/.test(raw) || VOID.indexOf(name.toLowerCase()) !== -1
            });

            i = end;
        }

        return list;
    }

    /** Индекс парного закрывающего тега для открывающего под номером from. */
    function matchClose(list, from) {
        var depth = 0;

        for (var i = from; i < list.length; i++) {
            var token = list[i];

            if (token.type === 'open' && token.name === list[from].name && !token.self) {
                depth++;
            } else if (token.type === 'close' && token.name === list[from].name) {
                depth--;

                if (!depth) {
                    return i;
                }
            }
        }

        return -1;
    }

    /** Есть ли внутри куска блочные теги. */
    function hasBlockInside(list, from, to) {
        for (var i = from; i < to; i++) {
            if (list[i].type === 'open' && !list[i].self && INLINE.indexOf(list[i].name) === -1) {
                return true;
            }
        }

        return false;
    }

    function pad(depth, step) {
        return new Array(depth + 1).join(step);
    }

    /**
     * Раскладка разметки по строкам с отступами.
     *
     * Главное правило: НИКОГДА не переносить строку внутри абзаца. Перенос
     * между строчными элементами страница читает как пробел, и «красивое»
     * форматирование меняло бы вид материала — между словом и ссылкой
     * появлялся бы лишний пробел, которого автор не ставил. Поэтому блок,
     * внутри которого нет других блоков, целиком остаётся в одной строке,
     * какой бы длинной она ни вышла: включённый перенос строк покажет её
     * без прокрутки.
     *
     * Отступы расставляются только там, где пробелы ничего не значат —
     * между блоками.
     */
    function format(code, indent) {
        var step = indent || '  ';
        var list = tokens(String(code).replace(/^\s+|\s+$/g, ''));
        var out = [];
        var depth = 0;
        var i = 0;

        var slice = function (from, to) {
            var text = '';

            for (var j = from; j <= to; j++) {
                text += list[j].value;
            }

            return text;
        };

        while (i < list.length) {
            var token = list[i];

            if (token.type === 'text') {
                if (/\S/.test(token.value)) {
                    out.push(pad(depth, step) + token.value.replace(/\s+/g, ' ').trim());
                }

                i++;
                continue;
            }

            if (token.type === 'comment') {
                out.push(pad(depth, step) + token.value);
                i++;
                continue;
            }

            if (token.type === 'close') {
                depth = Math.max(0, depth - 1);
                out.push(pad(depth, step) + token.value);
                i++;
                continue;
            }

            // Строчный кусок собираем в одну строку целиком: разрывать его
            // нельзя по той же причине — появится лишний пробел.
            if (token.self || INLINE.indexOf(token.name) !== -1) {
                var run = '';

                while (i < list.length) {
                    var next = list[i];
                    var inlineish = next.type === 'text' ||
                                    (next.type !== 'close' && (next.self || INLINE.indexOf(next.name) !== -1)) ||
                                    (next.type === 'close' && INLINE.indexOf(next.name) !== -1);

                    if (!inlineish) {
                        break;
                    }

                    run += next.value;
                    i++;
                }

                if (run.trim()) {
                    out.push(pad(depth, step) + run.replace(/\s+/g, ' ').trim());
                }

                continue;
            }

            var closeAt = matchClose(list, i);

            // Содержимое pre и textarea переносить нельзя вовсе: там значим
            // каждый пробел, и отступы стали бы частью текста.
            if (closeAt !== -1 && VERBATIM.indexOf(token.name) !== -1) {
                out.push(pad(depth, step) + slice(i, closeAt));
                i = closeAt + 1;
                continue;
            }

            if (closeAt !== -1 && !hasBlockInside(list, i + 1, closeAt)) {
                out.push(pad(depth, step) + slice(i, closeAt).replace(/\s+/g, ' ').replace(/>\s+</g, '><').trim());
                i = closeAt + 1;
                continue;
            }

            out.push(pad(depth, step) + token.value);

            if (!token.self) {
                depth++;
            }

            i++;
        }

        return out.join('\n');
    }


    /**
     * Проверка парности тегов.
     *
     * Не роскошь: незакрытый тег в этом проекте уже приводил к порче ответа
     * целиком — браузер получал битую страницу на двухсотом коде. Заметить
     * это глазами в полотне разметки почти невозможно, а здесь видно сразу.
     */
    function check(code) {
        var stack = [];
        var list = tokens(code);
        var line = 1;

        for (var i = 0; i < list.length; i++) {
            var token = list[i];

            if (token.type === 'open' && !token.self) {
                stack.push({ name: token.name, line: line });
            } else if (token.type === 'close') {
                if (!stack.length) {
                    return { ok: false, line: line, message: t('src.extra', 'Лишний закрывающий тег') + ' </' + token.name + '>' };
                }

                var last = stack.pop();

                if (last.name !== token.name) {
                    return {
                        ok: false,
                        line: line,
                        message: t('src.mismatch', 'Ожидался') + ' </' + last.name + '>, ' +
                                 t('src.but_found', 'а стоит') + ' </' + token.name + '>'
                    };
                }
            }

            line += (token.value.match(/\n/g) || []).length;
        }

        if (stack.length) {
            var open = stack[stack.length - 1];

            return {
                ok: false,
                line: open.line,
                message: t('src.unclosed', 'Не закрыт тег') + ' <' + open.name + '>'
            };
        }

        return { ok: true };
    }

    RuEditor.source = {
        highlight: highlight,
        format: format,
        check: check,
        tokens: tokens
    };

    /* ── Обвязка поля правки ─────────────────────────────────────────── */

    RuEditor.registerPlugin('source-view', {
        init: function (editor) {
            var code = editor.code;
            var gutter = el('div', { class: 'ru-ed-src-gutter ru-ed-src-text' });
            var paint = el('pre', { class: 'ru-ed-src-paint ru-ed-src-text', 'aria-hidden': 'true' });
            var status = el('div', { class: 'ru-ed-src-status' });
            var box = el('div', { class: 'ru-ed-src-box' });
            var wrapper = el('div', { class: 'ru-ed-src', hidden: true });

            code.classList.add('ru-ed-src-text');
            code.parentNode.insertBefore(wrapper, code);

            // Слой и поле кладём в ОДНУ ячейку. Раньше слой отступал от края
            // на жёстко заданную ширину колонки с номерами — на трёхзначных
            // номерах колонка становится шире, и подсветка уезжала бы вбок.
            var area = el('div', { class: 'ru-ed-src-area' });

            area.appendChild(paint);
            area.appendChild(code);
            box.appendChild(gutter);
            box.appendChild(area);
            wrapper.appendChild(box);
            wrapper.appendChild(status);

            editor.sourceBox = wrapper;

            var lines = el('span', { class: 'ru-ed-src-lines' });
            var problem = el('span', { class: 'ru-ed-src-problem' });
            var tools = el('span', { class: 'ru-ed-src-tools' });

            status.appendChild(lines);
            status.appendChild(problem);
            status.appendChild(tools);

            var button = function (label, title, handler) {
                var node = el('button', { type: 'button', class: 'ru-ed-src-btn', text: label, title: title });

                node.addEventListener('mousedown', function (event) { event.preventDefault(); });
                node.addEventListener('click', handler);
                tools.appendChild(node);

                return node;
            };

            button(t('src.format', 'Отформатировать'), t('src.format_hint', 'Расставить отступы по вложенности'), function () {
                code.value = format(code.value);
                render();
                code.focus();
            });

            // Толщина полосы прокрутки у каждой системы своя, а знать её надо:
            // без переноса строк поле получает горизонтальную полосу и
            // становится ниже слоя на её толщину. У самого низа длинного
            // документа подсветка из-за этого отставала бы от текста.
            var probe = el('div', { style: 'position:absolute;visibility:hidden;overflow:scroll;width:60px;height:60px' });

            document.body.appendChild(probe);
            wrapper.style.setProperty('--ru-sbar', (probe.offsetHeight - probe.clientHeight) + 'px');
            probe.remove();

            var wrapButton = button(t('src.wrap', 'Перенос строк'), t('src.wrap_hint', 'Переносить длинные строки'), function () {
                wrapper.classList.toggle('is-nowrap');
                wrapButton.classList.toggle('is-off', wrapper.classList.contains('is-nowrap'));
                render();
            });

            /* ── Отрисовка ── */

            function render() {
                var value = code.value;
                var count = value.split('\n').length;
                var numbers = '';

                for (var n = 1; n <= count; n++) {
                    numbers += n + '\n';
                }

                gutter.textContent = numbers;

                // Последняя строка без завершающего перевода схлопывается,
                // и подсветка последней строки уезжает вверх на строку.
                paint.innerHTML = highlight(value) + '\n';

                var problems = check(value);

                problem.textContent = problems.ok ? '' : t('src.line', 'Строка') + ' ' + problems.line + ': ' + problems.message;
                problem.classList.toggle('is-bad', !problems.ok);
                lines.textContent = t('src.lines', 'Строк') + ': ' + count + ' · ' +
                                    t('src.chars', 'Знаков') + ': ' + value.length;

                sync();
            }

            function sync() {
                paint.scrollTop = code.scrollTop;
                paint.scrollLeft = code.scrollLeft;
                gutter.scrollTop = code.scrollTop;
            }

            code.addEventListener('input', render);
            code.addEventListener('scroll', sync);

            // Табуляция должна ставить отступ, а не уводить фокус с поля:
            // в правке кода это первое, чего от неё ждут.
            code.addEventListener('keydown', function (event) {
                if (event.key !== 'Tab' || event.ctrlKey || event.altKey) {
                    return;
                }

                event.preventDefault();

                var start = code.selectionStart;
                var end = code.selectionEnd;
                var value = code.value;

                if (start === end && !event.shiftKey) {
                    code.value = value.slice(0, start) + '  ' + value.slice(end);
                    code.selectionStart = code.selectionEnd = start + 2;
                    render();
                    return;
                }

                // Выделено несколько строк — сдвигаем их целиком.
                var from = value.lastIndexOf('\n', start - 1) + 1;
                var to = value.indexOf('\n', end);

                to = to === -1 ? value.length : to;

                var block = value.slice(from, to);
                var shifted = event.shiftKey
                    ? block.replace(/^ {1,2}/gm, '')
                    : block.replace(/^/gm, '  ');

                code.value = value.slice(0, from) + shifted + value.slice(to);
                code.selectionStart = from;
                code.selectionEnd = from + shifted.length;
                render();
            });

            editor.on('source-open', function () {
                // Высоту задаёт КОРОБКА, а не поле. Колонка с номерами —
                // такой же элемент строки, и ничем не ограниченная она
                // растягивала коробку до полной высоты содержимого: у слоя
                // подсветки не оставалось чего прокручивать, и он замирал на
                // первой строке, пока поле уезжало вниз.
                box.style.height = code.style.height || '';
                render();
            });
        }
    });
}(window, document));
