/* ============================================================================
   RU Editor — диалоги и мелкие части интерфейса.
   ----------------------------------------------------------------------------
   Диалог живёт в документе СТРАНИЦЫ, а не внутри рамки редактирования: внутри
   он был бы ограничен её высотой и обрезался бы прокруткой. Поэтому перед
   открытием редактор запоминает выделение (saveSelection) и возвращает его
   после (restoreSelection) — иначе вставка ушла бы в начало текста.
   ========================================================================= */

(function (window, document) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /**
     * Диалог с полями.
     *
     * spec = {
     *   title, wide, fields: [{name,label,type,value,hint,options,required}],
     *   panes: [{key,label,render(pane, api)}],   — вкладки вместо полей
     *   submitLabel, onSubmit(values, api), onOpen(api)
     * }
     */
    RuEditor.dialog = function (spec) {
        var inputs = {};
        var back = el('div', { class: 'ru-ed-dialog-back' });
        var body = el('div', { class: 'ru-ed-dialog-body' });
        var note = el('div', { class: 'ru-ed-note', hidden: true });

        var api = {
            close: close,
            values: collect,
            note: function (text, busy) {
                if (!text) {
                    note.setAttribute('hidden', '');
                    return;
                }
                note.textContent = text;
                note.classList.toggle('is-busy', !!busy);
                note.removeAttribute('hidden');
            },
            body: body,
            inputs: inputs,
            payload: {}
        };

        (spec.fields || []).forEach(function (field) {
            body.appendChild(buildField(field, inputs));
        });

        if (spec.panes) {
            body.appendChild(buildPanes(spec.panes, api));
        }

        body.appendChild(note);

        var submit = el('button', {
            type: 'submit',
            class: 'ru-ed-primary',
            text: spec.submitLabel || t('dialog.ok', 'Вставить')
        });

        var cancel = el('button', {
            type: 'button',
            class: 'ru-ed-ghost',
            text: t('dialog.cancel', 'Отмена'),
            onclick: close
        });

        var form = el('form', { class: 'ru-ed-dialog' + (spec.wide ? ' is-wide' : '') }, [
            el('div', { class: 'ru-ed-dialog-head' }, [
                el('span', { class: 'ru-ed-dialog-title', text: spec.title || '' }),
                el('button', {
                    type: 'button',
                    class: 'ru-ed-dialog-close',
                    html: '&times;',
                    'aria-label': t('dialog.close', 'Закрыть'),
                    onclick: close
                })
            ]),
            body,
            el('div', { class: 'ru-ed-dialog-foot' }, [cancel, submit])
        ]);

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var values = collect();

            if (spec.onSubmit && spec.onSubmit(values, api) === false) {
                return;
            }

            if (!api.keepOpen) {
                close();
            }
        });

        back.appendChild(form);

        back.addEventListener('mousedown', function (event) {
            if (event.target === back) {
                close();
            }
        });

        document.addEventListener('keydown', onKey);
        document.body.appendChild(back);

        var first = body.querySelector('input,select,textarea');

        if (first) {
            first.focus();
            if (first.select) {
                first.select();
            }
        }

        if (spec.onOpen) {
            spec.onOpen(api);
        }

        function onKey(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        }

        function close() {
            document.removeEventListener('keydown', onKey);
            back.remove();

            if (spec.onClose) {
                spec.onClose();
            }
        }

        function collect() {
            var values = {};

            Object.keys(inputs).forEach(function (name) {
                var input = inputs[name];

                values[name] = input.type === 'checkbox' ? input.checked : input.value;
            });

            Object.keys(api.payload).forEach(function (key) {
                values[key] = api.payload[key];
            });

            return values;
        }

        return api;
    };

    function buildField(field, inputs) {
        if (field.type === 'check') {
            var check = el('input', { type: 'checkbox' });

            check.checked = !!field.value;
            inputs[field.name] = check;

            return el('label', { class: 'ru-ed-field ru-ed-check' }, [
                check,
                el('span', { text: field.label, style: 'margin:0;font-weight:500' })
            ]);
        }

        if (field.type === 'row') {
            return el('div', { class: 'ru-ed-row' }, field.fields.map(function (sub) {
                return buildField(sub, inputs);
            }));
        }

        var control;

        if (field.type === 'select') {
            control = el('select', {});
            (field.options || []).forEach(function (option) {
                var node = el('option', { value: option.value, text: option.label });

                if (String(option.value) === String(field.value)) {
                    node.selected = true;
                }
                control.appendChild(node);
            });
        } else if (field.type === 'textarea') {
            control = el('textarea', { rows: field.rows || 4 });
            control.value = field.value || '';
        } else {
            control = el('input', {
                type: field.type || 'text',
                placeholder: field.placeholder || '',
                required: field.required || null,
                min: field.min,
                max: field.max
            });
            control.value = field.value === null || field.value === undefined ? '' : field.value;
        }

        inputs[field.name] = control;

        return el('label', { class: 'ru-ed-field' }, [
            el('span', { text: field.label }),
            control,
            field.hint ? el('small', { text: field.hint }) : null
        ]);
    }

    function buildPanes(panes, api) {
        var tabs = el('div', { class: 'ru-ed-tabs' });
        var wrap = el('div', {});
        var nodes = [];

        panes.forEach(function (pane, index) {
            var content = el('div', { class: 'ru-ed-pane', hidden: index > 0 });
            var tab = el('button', {
                type: 'button',
                class: 'ru-ed-tab' + (index === 0 ? ' is-active' : ''),
                text: pane.label
            });

            tab.addEventListener('click', function () {
                nodes.forEach(function (item, i) {
                    item.tab.classList.toggle('is-active', i === index);

                    if (i === index) {
                        item.content.removeAttribute('hidden');
                    } else {
                        item.content.setAttribute('hidden', '');
                    }
                });

                api.activePane = pane.key;

                if (pane.onShow) {
                    pane.onShow(content, api);
                }
            });

            nodes.push({ tab: tab, content: content });
            tabs.appendChild(tab);
            wrap.appendChild(content);

            if (pane.render) {
                pane.render(content, api);
            }
        });

        api.activePane = panes[0] ? panes[0].key : null;

        var box = el('div', {});

        box.appendChild(tabs);
        box.appendChild(wrap);

        return box;
    }

    /** Подтверждение — там, где нативный confirm выглядит чужеродно. */
    RuEditor.confirm = function (message, onYes) {
        RuEditor.dialog({
            title: t('dialog.confirm', 'Подтверждение'),
            fields: [],
            submitLabel: t('dialog.yes', 'Да'),
            onOpen: function (api) {
                api.body.insertBefore(el('p', { text: message, style: 'margin:0 0 4px;font-size:13px' }), api.body.firstChild);
            },
            onSubmit: function () {
                onYes();
            }
        });
    };
}(window, document));
