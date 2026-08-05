/* ============================================================================
   RU Editor — картинки, видео и медиатека.
   ----------------------------------------------------------------------------
   Главное отличие от того, что было: картинку можно ВЫБРАТЬ из уже
   загруженного. Раньше редактор умел только загружать заново — модуль Файлы и
   редактор материалов были двумя несвязанными хранилищами, и в библиотеке
   копились дубли одного и того же логотипа.

   Настройки приходят от компонента:
     uploadUrl   — куда отправлять новый файл
     browseUrl   — откуда брать список медиатеки
     csrf        — токен формы
   Нет адресов — вкладки загрузки и библиотеки просто не показываются, остаётся
   вставка по адресу. Молча падать редактор не должен.
   ========================================================================= */

(function (window) {
    'use strict';

    var RuEditor = window.RuEditor;

    if (!RuEditor) {
        return;
    }

    var el = RuEditor.el;
    var t = RuEditor.t;

    /* ── Кнопка «Изображение» ────────────────────────────────────────── */

    RuEditor.registerButton('image', {
        icon: 'fas fa-image',
        title: 'Изображение',
        action: function (editor) { editor.exec('image'); }
    });

    RuEditor.registerCommand('image', function (editor) {
        var current = editor.closest('img');

        openMediaDialog(editor, {
            type: 'image',
            title: current ? t('image.edit', 'Свойства изображения') : t('image.title', 'Изображение'),
            current: current ? {
                url: current.getAttribute('src'),
                alt: current.getAttribute('alt') || '',
                caption: captionOf(current)
            } : null,
            onPick: function (file, values) {
                insertImage(editor, current, file, values);
            }
        });
    });

    /* ── Кнопка «Видео» ──────────────────────────────────────────────── */

    RuEditor.registerButton('media', {
        icon: 'fas fa-film',
        title: 'Видео',
        action: function (editor) { editor.exec('media'); }
    });

    /**
     * Видео. Свой файл — тегом video с настройками проигрывателя, ссылка на
     * площадку — встроенным окном её проигрывателя.
     *
     * Раньше здесь был один диалог с адресом и двумя числами: выбрать ролик из
     * медиатеки было нельзя, а настройки проигрывателя отсутствовали вовсе.
     */
    RuEditor.registerCommand('media', function (editor) {
        openMediaDialog(editor, {
            type: 'video',
            title: t('media.title', 'Видео'),
            urlHint: t('media.hint', 'Ссылка на YouTube, RuTube, VK Видео или прямой адрес файла .mp4'),
            extras: function (box, api) {
                playerFields(box, api, {
                    poster: true,
                    // Звук по умолчанию не выключаем: браузеры разрешают
                    // самозапуск только беззвучным, поэтому «сам играет»
                    // включает и «без звука» — но решает это автор.
                    hint: t('media.autoplay_hint', 'Браузеры запускают ролик сами только без звука')
                });
            },
            onPick: function (file, values) {
                editor.restoreSelection();

                var url = (values.url || '').trim();
                var html = isEmbeddable(url)
                    ? buildEmbed(url, values.width)
                    : buildPlayer('video', url, values);

                if (html) {
                    editor.insertHtml(html);
                }
            }
        });
    });

    /* ── Кнопка «Звук» ───────────────────────────────────────────────── */

    RuEditor.registerButton('audio', {
        icon: 'fas fa-volume-high',
        title: 'Звук',
        action: function (editor) { editor.exec('audio'); }
    });

    RuEditor.registerCommand('audio', function (editor) {
        openMediaDialog(editor, {
            type: 'audio',
            title: t('audio.title', 'Звук'),
            urlHint: t('audio.hint', 'Прямой адрес файла .mp3, .ogg или .wav'),
            extras: function (box, api) {
                playerFields(box, api, { poster: false });
            },
            onPick: function (file, values) {
                editor.restoreSelection();

                var html = buildPlayer('audio', (values.url || '').trim(), values);

                if (html) {
                    editor.insertHtml(html);
                }
            }
        });
    });

    /* ── Кнопка «Файл» ───────────────────────────────────────────────── */

    RuEditor.registerButton('file', {
        icon: 'fas fa-paperclip',
        title: 'Файл для скачивания',
        action: function (editor) { editor.exec('file'); }
    });

    /**
     * Любой файл — карточкой со значком, названием и размером.
     *
     * Просто ссылка тоже работает, но посетитель не понимает, что его ждёт:
     * договор на двадцать мегабайт и заметка на две строки выглядят одинаково.
     * Карточка показывает тип и вес до нажатия.
     */
    RuEditor.registerCommand('file', function (editor) {
        openMediaDialog(editor, {
            type: 'file',
            title: t('file.title', 'Файл для скачивания'),
            urlHint: t('file.hint', 'Прямой адрес файла'),
            extras: function (box, api) {
                var label = el('input', { type: 'text', placeholder: t('file.label_hint', 'По умолчанию — имя файла') });

                api.inputs.label = label;

                var card = el('input', { type: 'checkbox' });

                card.checked = true;
                api.inputs.card = card;

                box.appendChild(el('label', { class: 'ru-ed-field' }, [
                    el('span', { text: t('file.label', 'Подпись') }),
                    label
                ]));

                box.appendChild(el('label', { class: 'ru-ed-field ru-ed-check' }, [
                    card,
                    el('span', { text: t('file.as_card', 'Карточкой со значком и размером'), style: 'margin:0;font-weight:500' })
                ]));
            },
            onPick: function (file, values) {
                editor.restoreSelection();

                var url = (values.url || '').trim();

                if (!url) {
                    return;
                }

                var name = (values.label || '').trim() || (file && file.name) || url.split('/').pop();

                if (!values.card) {
                    editor.insertHtml('<a href="' + RuEditor.escapeHtml(url) + '" download>' +
                                      RuEditor.escapeHtml(name) + '</a>');
                    return;
                }

                // Тип берём из АДРЕСА, а не из подписи: подпись пишет человек
                // («Договор оферты»), и расширения в ней обычно нет.
                var ext = (file && file.ext) || '';

                if (!ext) {
                    ext = (url.split(/[?#]/)[0].split('.').pop() || '').toLowerCase();

                    if (ext.length > 5 || ext.indexOf('/') !== -1) {
                        ext = '';
                    }
                }

                var size = file && file.size ? file.size : '';

                editor.insertHtml(
                    '<p class="pc-file"><a href="' + RuEditor.escapeHtml(url) + '" download>' +
                    (ext ? '<span class="pc-file__ext">' + RuEditor.escapeHtml(ext) + '</span>' : '') +
                    '<span class="pc-file__name">' + RuEditor.escapeHtml(name) + '</span>' +
                    (size ? '<span class="pc-file__size">' + RuEditor.escapeHtml(size) + '</span>' : '') +
                    '</a></p><p><br></p>'
                );
            }
        });
    });

    /* ── Настройки проигрывателя ─────────────────────────────────────── */

    function playerFields(box, api, options) {
        var row = el('div', { class: 'ru-ed-row' });

        [
            ['controls', t('player.controls', 'Показывать управление'), true],
            ['autoplay', t('player.autoplay', 'Запускать сразу'), false],
            ['loop', t('player.loop', 'Повторять'), false],
            ['muted', t('player.muted', 'Без звука'), false]
        ].forEach(function (item) {
            var check = el('input', { type: 'checkbox' });

            check.checked = item[2];
            api.inputs[item[0]] = check;

            row.appendChild(el('label', { class: 'ru-ed-field ru-ed-check' }, [
                check,
                el('span', { text: item[1], style: 'margin:0;font-weight:500' })
            ]));
        });

        box.appendChild(row);

        var preload = el('select', {});

        [
            ['metadata', t('player.preload_meta', 'Только сведения о файле')],
            ['none', t('player.preload_none', 'Ничего не грузить заранее')],
            ['auto', t('player.preload_auto', 'Грузить сразу')]
        ].forEach(function (item) {
            preload.appendChild(el('option', { value: item[0], text: item[1] }));
        });

        api.inputs.preload = preload;

        var width = el('input', { type: 'number', min: 10, max: 100, value: 100 });

        api.inputs.width = width;

        var pair = el('div', { class: 'ru-ed-row' }, [
            el('label', { class: 'ru-ed-field' }, [
                el('span', { text: t('player.preload', 'Предзагрузка') }),
                preload,
                el('small', { text: t('player.preload_hint', '«Грузить сразу» тянет весь файл при открытии страницы') })
            ]),
            el('label', { class: 'ru-ed-field' }, [
                el('span', { text: t('player.width', 'Ширина, % от текста') }),
                width
            ])
        ]);

        box.appendChild(pair);

        if (options.poster) {
            var poster = el('input', { type: 'text', placeholder: 'https://…' });

            api.inputs.poster = poster;

            box.appendChild(el('label', { class: 'ru-ed-field' }, [
                el('span', { text: t('player.poster', 'Заставка до запуска') }),
                poster,
                el('small', { text: t('player.poster_hint', 'Адрес картинки. Без неё браузер показывает первый кадр или пустоту') })
            ]));
        }

        if (options.hint) {
            box.appendChild(el('small', { text: options.hint, style: 'display:block;color:#6b7280' }));
        }
    }

    /** Разметка своего проигрывателя. */
    function buildPlayer(tag, url, values) {
        if (!url) {
            return '';
        }

        var attrs = ['src="' + RuEditor.escapeHtml(url) + '"'];

        if (values.controls) { attrs.push('controls'); }
        if (values.loop) { attrs.push('loop'); }
        if (values.muted) { attrs.push('muted'); }
        if (values.autoplay) {
            attrs.push('autoplay');
            // Самозапуск со звуком браузеры блокируют, и ролик просто не
            // играет. Добавляем беззвучность сами, иначе настройка молча
            // ничего не делает.
            if (!values.muted) {
                attrs.push('muted');
            }
        }

        attrs.push('preload="' + RuEditor.escapeHtml(values.preload || 'metadata') + '"');

        if (tag === 'video' && values.poster) {
            attrs.push('poster="' + RuEditor.escapeHtml(String(values.poster).trim()) + '"');
        }

        var width = parseInt(values.width, 10);

        if (width && width > 0 && width < 100) {
            attrs.push('style="width:' + width + '%"');
        }

        return '<p><' + tag + ' ' + attrs.join(' ') + '></' + tag + '></p><p><br></p>';
    }

    /** Адрес ведёт на площадку с роликом, а не на файл. */
    function isEmbeddable(url) {
        return /youtube\.com|youtu\.be|rutube\.ru|vk\.com\/video/i.test(url);
    }

    /**
     * Разбор адреса ролика.
     *
     * Хранить ссылку как есть нельзя: страница ролика — это не проигрыватель,
     * во фрейме она либо не откроется, либо покажет весь сайт целиком. Поэтому
     * известные площадки приводим к их адресу встраивания, а неизвестное
     * считаем прямым файлом и отдаём тегу video.
     */
    function buildEmbed(url, width) {
        if (!url) {
            return '';
        }

        // Ширина в процентах, высота — из пропорции 16:9. Жёсткие пиксели у
        // встроенного окна означают, что на телефоне ролик либо вылезет за
        // край, либо останется крохотным.
        var percent = parseInt(width, 10);

        percent = percent && percent > 0 && percent <= 100 ? percent : 100;

        var style = 'width:' + percent + '%;aspect-ratio:16/9;height:auto';
        var frame = function (src) {
            return '<p><iframe src="' + RuEditor.escapeHtml(src) + '" style="' + style +
                   '" frameborder="0" allowfullscreen loading="lazy"></iframe></p><p><br></p>';
        };

        var youtube = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{6,})/i);

        if (youtube) {
            return frame('https://www.youtube.com/embed/' + youtube[1]);
        }

        var rutube = url.match(/rutube\.ru\/video\/([\w]+)/i);

        if (rutube) {
            return frame('https://rutube.ru/play/embed/' + rutube[1]);
        }

        var vk = url.match(/vk\.com\/video(-?\d+)_(\d+)/i);

        if (vk) {
            return frame('https://vk.com/video_ext.php?oid=' + vk[1] + '&id=' + vk[2]);
        }

        return frame(url);
    }

    /* ── Вставка картинки ────────────────────────────────────────────── */

    function captionOf(img) {
        var figure = img.closest('figure');
        var caption = figure ? figure.querySelector('figcaption') : null;

        return caption ? caption.textContent : '';
    }

    function insertImage(editor, current, file, values) {
        var url = (values.url || (file && file.url) || '').trim();

        if (!url) {
            return;
        }

        var alt = (values.alt || (file && file.alt_text) || '').trim();
        var caption = (values.caption || '').trim();

        // Правка существующей картинки: подменяем на месте, не трогая её
        // окружение — иначе слетело бы выравнивание и подпись.
        if (current) {
            current.setAttribute('src', url);
            current.setAttribute('alt', alt);
            applyCaption(editor, current, caption);
            editor._snapshot();
            editor.save();
            editor._updateState();
            return;
        }

        editor.restoreSelection();

        var img = editor.doc.createElement('img');

        img.setAttribute('src', url);
        img.setAttribute('alt', alt);
        // Отложенная загрузка: материал с десятком картинок иначе тянет их все
        // сразу при открытии страницы.
        img.setAttribute('loading', 'lazy');

        if (caption) {
            var figure = editor.doc.createElement('figure');
            var cap = editor.doc.createElement('figcaption');

            cap.textContent = caption;
            figure.appendChild(img);
            figure.appendChild(cap);
            editor.insertNode(figure);
        } else {
            editor.insertNode(img);
        }

        editor._snapshot();
        editor.save();
        editor._updateState();
    }

    function applyCaption(editor, img, caption) {
        var figure = img.closest('figure');

        if (!caption) {
            if (figure) {
                figure.parentNode.insertBefore(img, figure);
                figure.remove();
            }
            return;
        }

        if (!figure) {
            figure = editor.doc.createElement('figure');
            img.parentNode.insertBefore(figure, img);
            figure.appendChild(img);
        }

        var node = figure.querySelector('figcaption');

        if (!node) {
            node = editor.doc.createElement('figcaption');
            figure.appendChild(node);
        }

        node.textContent = caption;
    }

    /* ── Диалог: загрузка · медиатека · адрес ────────────────────────── */

    function openMediaDialog(editor, spec) {
        var opts = editor.options;
        var panes = [];
        var picked = { file: null };

        editor.saveSelection();

        if (opts.uploadUrl) {
            panes.push({
                key: 'upload',
                label: t('media.tab_upload', 'Загрузить'),
                render: function (pane, api) {
                    renderUpload(editor, pane, api, picked, spec.type);
                }
            });
        }

        if (opts.browseUrl) {
            panes.push({
                key: 'library',
                label: t('media.tab_library', 'Медиатека'),
                render: function (pane, api) {
                    renderLibrary(editor, pane, api, picked, spec.type);
                }
            });
        }

        panes.push({
            key: 'url',
            label: t('media.tab_url', 'По адресу'),
            render: function (pane, api) {
                var input = el('input', { type: 'text', placeholder: 'https://…' });

                input.value = spec.current ? spec.current.url : '';
                api.inputs.url = input;

                pane.appendChild(el('label', { class: 'ru-ed-field' }, [
                    el('span', { text: t('media.url_label', 'Адрес файла') }),
                    input,
                    spec.urlHint ? el('small', { text: spec.urlHint }) : null
                ]));
            }
        });

        // Правку открываем сразу на адресе: картинка уже выбрана, менять её
        // обычно не нужно — нужно поправить подпись.
        if (spec.current) {
            panes.reverse();
        }

        RuEditor.dialog({
            title: spec.title,
            wide: true,
            submitLabel: spec.current ? t('dialog.save', 'Сохранить') : t('dialog.ok', 'Вставить'),
            panes: panes,
            fields: [],
            onOpen: function (api) {
                var extra = el('div', {});

                if (spec.type === 'image') {
                    var alt = el('input', { type: 'text' });

                    alt.value = spec.current ? spec.current.alt : '';
                    api.inputs.alt = alt;

                    var caption = el('input', { type: 'text' });

                    caption.value = spec.current ? spec.current.caption : '';
                    api.inputs.caption = caption;

                    extra.appendChild(el('div', { class: 'ru-ed-row' }, [
                        el('label', { class: 'ru-ed-field' }, [
                            el('span', { text: t('image.alt', 'Описание (alt)') }),
                            alt,
                            el('small', { text: t('image.alt_hint', 'Читают поисковики и экранные дикторы — без него картинка для них пустая') })
                        ]),
                        el('label', { class: 'ru-ed-field' }, [
                            el('span', { text: t('image.caption', 'Подпись под картинкой') }),
                            caption
                        ])
                    ]));
                } else {
                    // У остальных вставок alt и подпись не нужны, зато нужны
                    // свои настройки — их добавляет вызывающая команда.
                    api.inputs.alt = { value: '' };
                    api.inputs.caption = { value: '' };
                }

                if (spec.extras) {
                    spec.extras(extra, api);
                }

                api.body.appendChild(extra);
            },
            onSubmit: function (values, api) {
                var url = values.url || (picked.file ? picked.file.url : '');

                if (!url) {
                    api.note(t('media.need_file', 'Выберите файл или укажите адрес'));
                    api.keepOpen = true;
                    return false;
                }

                api.keepOpen = false;
                // Отдаём ВСЕ значения диалога, а не три отобранных: настройки
                // проигрывателя и подпись файла добавляет вызывающая команда
                // через extras, и до неё они иначе просто не доезжали —
                // вставка выходила без управления, повтора и заставки.
                values.url = url;
                spec.onPick(picked.file, values);
            }
        });
    }

    /* ── Вкладка «Загрузить» ─────────────────────────────────────────── */

    function renderUpload(editor, pane, api, picked, type) {
        var opts = editor.options;
        var input = el('input', { type: 'file', accept: type === 'image' ? 'image/*' : '*/*', style: 'display:none' });
        var preview = el('div', {});

        var zone = el('div', { class: 'ru-ed-drop-zone' }, [
            el('i', { class: 'fas fa-cloud-arrow-up', 'aria-hidden': 'true' }),
            el('span', { text: t('media.drop', 'Перетащите файл сюда или нажмите, чтобы выбрать') })
        ]);

        zone.addEventListener('click', function () { input.click(); });

        ['dragenter', 'dragover'].forEach(function (name) {
            zone.addEventListener(name, function (event) {
                event.preventDefault();
                zone.classList.add('is-over');
            });
        });

        ['dragleave', 'drop'].forEach(function (name) {
            zone.addEventListener(name, function (event) {
                event.preventDefault();
                zone.classList.remove('is-over');
            });
        });

        zone.addEventListener('drop', function (event) {
            if (event.dataTransfer.files.length) {
                send(event.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (input.files.length) {
                send(input.files[0]);
            }
        });

        function send(file) {
            api.note(t('media.uploading', 'Загружаю…'), true);

            RuEditor.upload(editor, file).then(function (uploaded) {
                picked.file = uploaded;
                api.payload.url = uploaded.url;
                api.note('');
                preview.innerHTML = '';

                // Показываем то, что подходит типу. Раньше превью всегда было
                // картинкой, и загруженное видео или документ рисовались
                // значком «битое изображение» — выглядело как неудача, хотя
                // файл уже лежал на сервере.
                var kind = uploaded.kind || (uploaded.is_image ? 'image' : 'file');

                if (kind === 'image') {
                    preview.appendChild(el('img', {
                        src: uploaded.url,
                        alt: '',
                        style: 'max-height:150px;margin-top:12px;border:1px solid #e5e7eb'
                    }));
                } else if (kind === 'video') {
                    preview.appendChild(el('video', {
                        src: uploaded.url,
                        controls: true,
                        style: 'max-height:150px;margin-top:12px;border:1px solid #e5e7eb'
                    }));
                } else if (kind === 'audio') {
                    preview.appendChild(el('audio', {
                        src: uploaded.url,
                        controls: true,
                        style: 'width:100%;margin-top:12px'
                    }));
                } else {
                    preview.appendChild(el('p', {
                        style: 'margin-top:12px;font-size:13px;color:#374151',
                        html: '<i class="fas fa-check" style="color:#16a34a"></i> ' +
                              RuEditor.escapeHtml(uploaded.name || uploaded.url) +
                              (uploaded.size ? ' <span style="color:#6b7280">(' + RuEditor.escapeHtml(uploaded.size) + ')</span>' : '')
                    }));
                }

                if (api.inputs.alt && !api.inputs.alt.value && uploaded.alt_text) {
                    api.inputs.alt.value = uploaded.alt_text;
                }
            }).catch(function (error) {
                api.note(error.message || t('media.upload_failed', 'Не удалось загрузить файл'));
            });
        }

        pane.appendChild(zone);
        pane.appendChild(input);
        pane.appendChild(preview);

        if (opts.uploadHint) {
            pane.appendChild(el('small', { text: opts.uploadHint, style: 'display:block;margin-top:8px;color:#6b7280' }));
        }
    }

    /* ── Вкладка «Медиатека» ─────────────────────────────────────────── */

    function renderLibrary(editor, pane, api, picked, type) {
        var grid = el('div', { class: 'ru-ed-lib' });
        var search = el('input', { type: 'search', placeholder: t('media.search', 'Поиск по названию…') });
        var state = { page: 1, last: 1, busy: false };

        var reload = RuEditor.debounce(function () {
            state.page = 1;
            grid.innerHTML = '';
            load();
        }, 300);

        search.addEventListener('input', reload);

        pane.appendChild(el('div', { class: 'ru-ed-lib-bar' }, [search]));
        pane.appendChild(grid);

        function load() {
            if (state.busy) {
                return;
            }

            state.busy = true;

            var url = editor.options.browseUrl +
                (editor.options.browseUrl.indexOf('?') === -1 ? '?' : '&') +
                'type=' + encodeURIComponent(type || '') +
                '&q=' + encodeURIComponent(search.value) +
                '&page=' + state.page;

            window.fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                state.busy = false;
                state.last = data.last_page || 1;

                var more = grid.querySelector('.ru-ed-lib-more');

                if (more) {
                    more.remove();
                }

                if (!data.files || !data.files.length) {
                    if (state.page === 1) {
                        grid.appendChild(el('p', {
                            class: 'ru-ed-lib-empty',
                            text: t('media.empty', 'В медиатеке пока ничего нет')
                        }));
                    }
                    return;
                }

                data.files.forEach(function (file) {
                    grid.appendChild(tile(file));
                });

                if (state.page < state.last) {
                    var button = el('button', {
                        type: 'button',
                        class: 'ru-ed-lib-more',
                        text: t('media.more', 'Показать ещё')
                    });

                    button.addEventListener('click', function () {
                        state.page++;
                        load();
                    });

                    grid.appendChild(button);
                }
            }).catch(function () {
                state.busy = false;
                api.note(t('media.load_failed', 'Не удалось получить список медиатеки'));
            });
        }

        function tile(file) {
            var node = el('button', { type: 'button', class: 'ru-ed-lib-item', title: file.name }, [
                file.is_image
                    ? el('img', { src: file.url, alt: file.alt_text || file.name, loading: 'lazy' })
                    : el('span', { class: 'is-file', html: '<i class="fas fa-file"></i>' }),
                el('span', { class: 'ru-ed-lib-name', text: file.name })
            ]);

            node.addEventListener('click', function () {
                Array.prototype.forEach.call(grid.querySelectorAll('.ru-ed-lib-item'), function (item) {
                    item.classList.remove('is-active');
                });

                node.classList.add('is-active');
                picked.file = file;
                api.payload.url = file.url;

                if (!api.inputs.alt.value) {
                    api.inputs.alt.value = file.alt_text || '';
                }
            });

            // Двойной щелчок = выбрать и вставить: обычный сценарий не должен
            // требовать ещё одного нажатия внизу диалога.
            node.addEventListener('dblclick', function () {
                var form = node.closest('form');

                if (form) {
                    form.requestSubmit();
                }
            });

            return node;
        }

        load();
    }

    /* ── Загрузка файла на сервер ────────────────────────────────────── */

    /**
     * Общая отправка файла. Понимает два формата ответа, потому что в проекте
     * два эндпоинта загрузки с разной историей: у модуля Файлы ответ вида
     * {success, files:[…]}, у общего загрузчика редактора — {location}.
     */
    RuEditor.upload = function (editor, file) {
        var opts = editor.options;

        if (!opts.uploadUrl) {
            return Promise.reject(new Error(t('media.no_upload', 'Загрузка файлов не настроена')));
        }

        var data = new FormData();

        // Один раз, а не в два поля сразу. Раньше файл клался и в «file», и в
        // «files[]» — «на случай, если сервер ждёт массив». Сервер берёт первое
        // же непустое поле, зато тело запроса выходило вдвое больше файла: ролик
        // на 3 МБ отправлялся шестью, упирался в post_max_size и возвращался
        // ошибкой «слишком большой», хотя до предела было далеко.
        data.append('file', file);

        // Размер проверяем ДО отправки. Иначе браузер честно закачивает
        // мегабайты, сервер обрывает соединение, и человек получает голое
        // «413» — техническую подробность, из которой ничего не следует.
        if (opts.maxUploadBytes && file.size > opts.maxUploadBytes) {
            return Promise.reject(new Error(
                t('media.too_big', 'Файл больше, чем разрешает сервер') +
                ' (' + (opts.uploadLimitLabel || '') + ')'
            ));
        }

        return window.fetch(opts.uploadUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': opts.csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            }
        }).then(function (response) {
            if (response.status === 413) {
                // Сервер оборвал приём: предел задан в php.ini
                // (upload_max_filesize и post_max_size), и его не обойти ни
                // настройками CMS, ни этим кодом.
                throw new Error(t('media.too_big', 'Файл больше, чем разрешает сервер') +
                                ' (' + (opts.uploadLimitLabel || '') + ')');
            }

            if (!response.ok) {
                throw new Error(t('media.upload_failed', 'Не удалось загрузить файл') + ' (' + response.status + ')');
            }

            return response.json();
        }).then(function (data) {
            if (data.location) {
                return { url: data.location, alt_text: '' };
            }

            if (data.files && data.files.length) {
                return data.files[0];
            }

            if (data.file) {
                return data.file;
            }

            throw new Error(data.message || t('media.upload_failed', 'Не удалось загрузить файл'));
        });
    };

    /* ── Плашка над выбранной картинкой ──────────────────────────────── */

    RuEditor.registerPlugin('image-tools', {
        init: function (editor) {
            var bubble = el('div', { class: 'ru-ed-bubble', hidden: true });
            var target = null;

            [
                ['left', 'fas fa-align-left', t('image.left', 'Слева, текст обтекает')],
                ['center', 'fas fa-align-center', t('image.center', 'По центру')],
                ['right', 'fas fa-align-right', t('image.right', 'Справа, текст обтекает')],
                ['none', 'fas fa-align-justify', t('image.none', 'Без обтекания')]
            ].forEach(function (item) {
                var button = el('button', { type: 'button', title: item[2], html: '<i class="' + item[1] + '"></i>' });

                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', function () {
                    align(target, item[0]);
                    editor._snapshot();
                    editor.save();
                    place();
                });

                bubble.appendChild(button);
            });

            // Ширина в процентах, а не в пикселях: материал читают и с
            // телефона, и с широкого монитора, и жёсткие пиксели там ведут
            // себя по-разному. Проценты одинаково работают везде.
            [25, 50, 75, 100].forEach(function (percent) {
                var button = el('button', {
                    type: 'button',
                    title: t('image.width', 'Ширина') + ' ' + percent + '%',
                    text: percent + '%',
                    style: 'width:auto;padding:0 6px;font-size:11px'
                });

                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', function () {
                    var wrap = target.closest('figure');
                    var value = percent === 100 ? '' : percent + '%';

                    if (wrap) {
                        // У картинки с подписью ширину задаёт обёртка, иначе
                        // подпись осталась бы во всю строку под узкой картинкой.
                        wrap.style.width = value;
                        target.style.width = value ? '100%' : '';
                    } else {
                        // Без обёртки ширину получает сама картинка. Раньше здесь
                        // сначала выставлялось нужное значение, а следующей
                        // строкой затиралось на 100% — размер не менялся никогда.
                        target.style.width = value;
                    }

                    // Высоту снимаем обязательно. Если её задали раньше — тянули
                    // за угол или вписали руками, — то ширина менялась, а высота
                    // оставалась прежней, и картинка сплющивалась. Пропорция
                    // должна следовать за шириной сама.
                    target.style.height = '';

                    if (wrap) {
                        wrap.style.height = '';
                    }

                    target.removeAttribute('width');
                    target.removeAttribute('height');
                    editor._snapshot();
                    editor.save();
                    place();
                });

                bubble.appendChild(button);
            });

            [
                ['edit', 'fas fa-pen', t('image.props', 'Свойства'), function () {
                    editor.selectNode(target);
                    editor.exec('image');
                }],
                ['remove', 'fas fa-trash', t('image.remove', 'Удалить'), function () {
                    var figure = target.closest('figure');

                    (figure || target).remove();
                    hide();
                    editor._snapshot();
                    editor.save();
                }]
            ].forEach(function (item) {
                var button = el('button', { type: 'button', title: item[2], html: '<i class="' + item[1] + '"></i>' });

                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', item[3]);
                bubble.appendChild(button);
            });

            editor.shell.appendChild(bubble);

            editor.doc.addEventListener('click', function (event) {
                var img = event.target.tagName === 'IMG' ? event.target : null;

                if (!img) {
                    hide();
                    return;
                }

                if (target) {
                    target.classList.remove('ru-ed-selected');
                }

                target = img;
                img.classList.add('ru-ed-selected');
                place();
            });

            editor.doc.addEventListener('scroll', place, true);
            window.addEventListener('resize', place);

            function place() {
                if (!target || !target.isConnected) {
                    hide();
                    return;
                }

                var box = target.getBoundingClientRect();
                var frame = editor.frame.getBoundingClientRect();
                var shell = editor.shell.getBoundingClientRect();

                bubble.removeAttribute('hidden');
                // Плашка живёт в документе СТРАНИЦЫ, а картинка — внутри рамки:
                // координаты приходится переводить из одной системы в другую,
                // иначе плашка встанет мимо при любой прокрутке.
                bubble.style.top = Math.max(0, (frame.top - shell.top) + box.top - 34) + 'px';
                bubble.style.left = ((frame.left - shell.left) + box.left) + 'px';
            }

            function hide() {
                if (target) {
                    target.classList.remove('ru-ed-selected');
                    target = null;
                }

                bubble.setAttribute('hidden', '');
            }

            function align(img, mode) {
                if (!img) {
                    return;
                }

                var node = img.closest('figure') || img;

                node.style.float = '';
                node.style.margin = '';
                node.style.display = '';

                if (mode === 'left') {
                    node.style.float = 'left';
                    node.style.margin = '0 1rem 1rem 0';
                } else if (mode === 'right') {
                    node.style.float = 'right';
                    node.style.margin = '0 0 1rem 1rem';
                } else if (mode === 'center') {
                    node.style.display = 'block';
                    node.style.margin = '1rem auto';
                }
            }
        }
    });

    /* ── Перетаскивание и вставка картинок прямо в текст ─────────────── */

    RuEditor.registerPlugin('image-paste', {
        init: function (editor) {
            if (!editor.options.uploadUrl) {
                return;
            }

            editor.doc.addEventListener('drop', function (event) {
                var files = event.dataTransfer && event.dataTransfer.files;

                if (!files || !files.length) {
                    return;
                }

                event.preventDefault();
                Array.prototype.forEach.call(files, function (file) {
                    if (/^image\//.test(file.type)) {
                        upload(file);
                    }
                });
            });

            editor.doc.addEventListener('paste', function (event) {
                var items = event.clipboardData && event.clipboardData.items;

                if (!items) {
                    return;
                }

                for (var i = 0; i < items.length; i++) {
                    if (items[i].kind === 'file' && /^image\//.test(items[i].type)) {
                        event.preventDefault();
                        upload(items[i].getAsFile());
                        return;
                    }
                }
            });

            function upload(file) {
                // Пока файл летит на сервер, на его месте стоит заглушка:
                // иначе автор не понимает, случилось ли вообще что-нибудь.
                var placeholder = editor.doc.createElement('span');

                placeholder.textContent = '⏳ ' + t('media.uploading', 'Загружаю…');
                editor.insertNode(placeholder);

                RuEditor.upload(editor, file).then(function (uploaded) {
                    var img = editor.doc.createElement('img');

                    img.setAttribute('src', uploaded.url);
                    img.setAttribute('alt', uploaded.alt_text || '');
                    img.setAttribute('loading', 'lazy');
                    placeholder.parentNode.replaceChild(img, placeholder);
                    editor._snapshot();
                    editor.save();
                }).catch(function (error) {
                    placeholder.textContent = '⚠ ' + (error.message || 'ошибка');
                });
            }
        }
    });
}(window));
