/*
 * Проигрыватель звука в материалах.
 *
 * Зачем свой. Браузерный <audio controls> выглядит по-разному в каждом
 * браузере и никак не связан с оформлением сайта: в Chrome одна серая панель,
 * в Safari другая, в Firefox третья. На странице с текстом это заметнее всего.
 *
 * Видео намеренно оставлено на родных кнопках: они умеют полный экран,
 * картинку в картинке, субтитры и правильно ведут себя на телефоне —
 * переписывать это ради вида не стоит. Видео получает только рамку (CSS).
 *
 * Если скрипт не отработал (ошибка, отключённый JS), у звука остаются родные
 * кнопки: свой вид включается классом is-ready, который ставится только после
 * успешной сборки.
 */
(function () {
    'use strict';

    var ICONS = {
        play: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.5v11l9-5.5z"/></svg>',
        pause: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.5h3.2v11H4zM8.8 2.5H12v11H8.8z"/></svg>',
        sound: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2.5 4.6 5.3H2v5.4h2.6L8 13.5zM10.6 5.4a3.6 3.6 0 0 1 0 5.2l.9.9a4.9 4.9 0 0 0 0-7z"/></svg>',
        mute: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2.5 4.6 5.3H2v5.4h2.6L8 13.5zM10.4 6.2l3.2 3.2M13.6 6.2l-3.2 3.2" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>'
    };

    var RATES = [1, 1.25, 1.5, 2];

    /** Время в виде 1:23 или 1:02:03 — как принято у проигрывателей. */
    function clock(seconds) {
        if (!isFinite(seconds) || seconds < 0) {
            return '0:00';
        }

        var total = Math.floor(seconds);
        var s = total % 60;
        var m = Math.floor(total / 60) % 60;
        var h = Math.floor(total / 3600);

        var mm = h ? (m < 10 ? '0' + m : m) : m;
        var ss = s < 10 ? '0' + s : s;

        return (h ? h + ':' : '') + mm + ':' + ss;
    }

    function el(tag, attrs, html) {
        var node = document.createElement(tag);

        Object.keys(attrs || {}).forEach(function (key) {
            node.setAttribute(key, attrs[key]);
        });

        if (html) {
            node.innerHTML = html;
        }

        return node;
    }

    /** Имя файла из адреса — подпись под дорожкой. */
    function nameFrom(audio) {
        var src = audio.currentSrc || audio.getAttribute('src') || '';

        try {
            src = decodeURIComponent(src.split('?')[0].split('#')[0]);
        } catch (error) {
            /* Адрес с битым кодированием — оставляем как есть. */
        }

        return src.split('/').pop() || '';
    }

    function build(audio) {
        if (audio.dataset.pcPlayer) {
            return;
        }

        audio.dataset.pcPlayer = '1';

        // Обёртка могла прийти из редактора; если её нет (старое содержимое) —
        // создаём на месте, чтобы оформление было одинаковым везде.
        var box = audio.closest('.pc-audio');

        if (!box) {
            box = el('div', { class: 'pc-audio' });
            audio.parentNode.insertBefore(box, audio);
            box.appendChild(audio);
        }

        var play = el('button', {
            type: 'button',
            class: 'pc-audio__play',
            'aria-label': 'Воспроизвести'
        }, ICONS.play);

        var fill = el('span', { class: 'pc-audio__fill' });
        var knob = el('span', { class: 'pc-audio__knob' });
        var rail = el('span', { class: 'pc-audio__rail' });
        rail.appendChild(fill);
        rail.appendChild(knob);

        // Дорожка — настоящий ползунок для клавиатуры и экранного диктора:
        // стрелками перематывать так же естественно, как мышью.
        var track = el('div', {
            class: 'pc-audio__track',
            role: 'slider',
            tabindex: '0',
            'aria-label': 'Перемотка',
            'aria-valuemin': '0',
            'aria-valuemax': '100',
            'aria-valuenow': '0'
        });
        track.appendChild(rail);

        var time = el('span', { class: 'pc-audio__time' }, '0:00 / 0:00');
        var name = el('span', { class: 'pc-audio__name' }, '');
        name.textContent = nameFrom(audio);

        var rate = el('button', { type: 'button', class: 'pc-audio__rate', 'aria-label': 'Скорость' }, '1×');
        var mute = el('button', { type: 'button', class: 'pc-audio__mute', 'aria-label': 'Выключить звук' }, ICONS.sound);

        var meta = el('div', { class: 'pc-audio__meta' });
        meta.appendChild(time);
        meta.appendChild(name);
        meta.appendChild(rate);

        var body = el('div', { class: 'pc-audio__body' });
        body.appendChild(track);
        body.appendChild(meta);

        // Пометка «служебное»: эта панель рисуется скриптом и в материале
        // храниться не должна. В редакторе тот же скрипт строит её прямо
        // внутри редактируемой области, и без пометки она уехала бы в базу —
        // а на сайте поверх неё построилась бы вторая такая же.
        var ui = el('div', {
            class: 'pc-audio__ui',
            'data-ru-transient': '1',
            contenteditable: 'false'
        });
        ui.appendChild(play);
        ui.appendChild(body);
        ui.appendChild(mute);

        box.appendChild(ui);
        box.classList.add('is-ready');

        /* ── Поведение ─────────────────────────────────────────────── */

        var draw = function () {
            var percent = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;

            fill.style.width = percent + '%';
            knob.style.left = percent + '%';
            track.setAttribute('aria-valuenow', Math.round(percent));
            track.setAttribute('aria-valuetext', clock(audio.currentTime));
            time.textContent = clock(audio.currentTime) + ' / ' + clock(audio.duration);
        };

        var toggle = function () {
            if (audio.paused) {
                // Играть должен ОДИН: если на странице несколько записей,
                // одновременное воспроизведение — каша, а не выбор.
                document.querySelectorAll('.pc-audio audio').forEach(function (other) {
                    if (other !== audio && !other.paused) {
                        other.pause();
                    }
                });

                audio.play();
            } else {
                audio.pause();
            }
        };

        play.addEventListener('click', toggle);

        audio.addEventListener('play', function () {
            play.innerHTML = ICONS.pause;
            play.setAttribute('aria-label', 'Пауза');
        });

        audio.addEventListener('pause', function () {
            play.innerHTML = ICONS.play;
            play.setAttribute('aria-label', 'Воспроизвести');
        });

        audio.addEventListener('timeupdate', draw);
        audio.addEventListener('loadedmetadata', draw);
        audio.addEventListener('durationchange', draw);
        audio.addEventListener('ended', function () {
            play.innerHTML = ICONS.play;
            play.setAttribute('aria-label', 'Воспроизвести');
        });

        // Перемотка мышью и пальцем. Тянем — время идёт следом, отпустили —
        // остаётся. Захват указателя нужен, чтобы палец мог уйти за пределы
        // полосы и перемотка не оборвалась.
        var seek = function (event) {
            var rect = rail.getBoundingClientRect();
            var ratio = (event.clientX - rect.left) / rect.width;

            ratio = Math.min(1, Math.max(0, ratio));

            if (audio.duration) {
                audio.currentTime = ratio * audio.duration;
                draw();
            }
        };

        track.addEventListener('pointerdown', function (event) {
            // Перематываем СРАЗУ, и только потом просим захват указателя.
            // Захват нужен, чтобы палец мог уйти за пределы полосы и
            // перемотка не оборвалась, но он может и не получиться — тогда
            // терять сам щелчок нельзя.
            seek(event);

            try {
                track.setPointerCapture(event.pointerId);
            } catch (error) {
                /* Захват недоступен — перемотка мышью всё равно работает. */
            }
        });

        track.addEventListener('pointermove', function (event) {
            var dragging = false;

            try {
                dragging = track.hasPointerCapture(event.pointerId);
            } catch (error) {
                dragging = false;
            }

            if (dragging) {
                seek(event);
            }
        });

        track.addEventListener('keydown', function (event) {
            var step = event.shiftKey ? 30 : 5;

            if (event.key === 'ArrowRight') {
                audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + step);
            } else if (event.key === 'ArrowLeft') {
                audio.currentTime = Math.max(0, audio.currentTime - step);
            } else if (event.key === ' ' || event.key === 'Enter') {
                toggle();
            } else {
                return;
            }

            event.preventDefault();
            draw();
        });

        mute.addEventListener('click', function () {
            audio.muted = !audio.muted;
            mute.innerHTML = audio.muted ? ICONS.mute : ICONS.sound;
            mute.setAttribute('aria-label', audio.muted ? 'Включить звук' : 'Выключить звук');
        });

        // Скорость нужна не для красоты: лекцию или подкаст слушают быстрее,
        // а неразборчивую запись — медленнее.
        rate.addEventListener('click', function () {
            var next = RATES[(RATES.indexOf(audio.playbackRate) + 1) % RATES.length] || 1;

            audio.playbackRate = next;
            rate.textContent = (next + '').replace('.', ',') + '×';
        });

        draw();
    }

    function upgrade(root) {
        (root || document).querySelectorAll('.page-content audio').forEach(build);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { upgrade(); });
    } else {
        upgrade();
    }

    // Содержимое может приехать позже — например, форма или блок, вставленные
    // скриптом. Отдаём точку входа наружу, чтобы не заводить наблюдатель.
    window.ruUpgradePlayers = upgrade;
}());
