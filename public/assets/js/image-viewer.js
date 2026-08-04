/**
 * Просмотр картинки во весь экран — ОДИН на весь сайт.
 *
 * Раньше это работало только в слайдере: скрипт лежал внутри его шаблона.
 * В остальных шаблонах и на страницах клик по картинке не делал ничего, и
 * его перехватывали просмотрщики картинок из расширений браузера — они
 * открывали снимок в натуральную величину, с прокруткой и обрезкой.
 *
 * Теперь скрипт подключён в общем макете сайта и охватывает всё содержимое:
 * страницы, новости, любые шаблоны, в том числе будущие. Ничего размечать
 * в шаблоне не нужно — картинки подбираются по правилам ниже.
 */
(function () {
    'use strict';

    const T = {
        close: 'Закрыть',
        prev: 'Предыдущее изображение',
        next: 'Следующее изображение',
        zoom: 'Открыть во весь экран',
    };

    // Ниже этого размера картинка почти наверняка значок, аватар или
    // логотип — увеличивать такое незачем.
    const MIN_SIDE = 140;

    let viewer = null, img = null, cap = null, images = [], index = 0, opener = null;

    /** Годится ли картинка для просмотра. */
    const suits = (el) => {
        if (!el || el.tagName !== 'IMG') return false;
        if (el.closest('.ru-viewer')) return false;
        if (el.classList.contains('no-zoom')) return false;

        // Внутри ссылки клик должен вести по ссылке: у карточек новостей и
        // товаров картинка — это переход к материалу, а не просмотр.
        if (el.closest('a')) return false;

        // Внутри шапки, подвала и боковых панелей картинки служебные.
        if (!el.closest('main, .page-content, article, .ru-swiper')) return false;

        const box = el.getBoundingClientRect();
        return box.width >= MIN_SIDE && box.height >= MIN_SIDE;
    };

    /** Соседи для листания — картинки того же блока. */
    const groupOf = (el) =>
        el.closest('.ru-swiper, .page-content, article, main') || document;

    const build = () => {
        viewer = document.createElement('div');
        viewer.className = 'ru-viewer';
        viewer.setAttribute('role', 'dialog');
        viewer.setAttribute('aria-modal', 'true');
        viewer.hidden = true;
        viewer.innerHTML =
            '<img class="ru-viewer__img" alt="">' +
            '<p class="ru-viewer__cap"></p>' +
            '<button type="button" class="ru-viewer__btn ru-viewer__close" aria-label="' + T.close + '">&times;</button>' +
            '<button type="button" class="ru-viewer__btn ru-viewer__prev" aria-label="' + T.prev + '">&#10094;</button>' +
            '<button type="button" class="ru-viewer__btn ru-viewer__next" aria-label="' + T.next + '">&#10095;</button>';

        img = viewer.querySelector('.ru-viewer__img');
        cap = viewer.querySelector('.ru-viewer__cap');

        viewer.querySelector('.ru-viewer__close').addEventListener('click', close);
        viewer.querySelector('.ru-viewer__prev').addEventListener('click', () => step(-1));
        viewer.querySelector('.ru-viewer__next').addEventListener('click', () => step(1));

        // Клик мимо картинки закрывает — привычное поведение просмотрщиков.
        viewer.addEventListener('click', (e) => { if (e.target === viewer) close(); });

        document.body.appendChild(viewer);
    };

    const show = () => {
        const el = images[index];
        if (!el) return;

        img.src = el.currentSrc || el.src;
        img.alt = el.alt || '';
        cap.textContent = el.alt || '';

        const many = images.length > 1;
        viewer.querySelector('.ru-viewer__prev').hidden = !many;
        viewer.querySelector('.ru-viewer__next').hidden = !many;
    };

    const step = (dir) => {
        if (images.length < 2) return;
        index = (index + dir + images.length) % images.length;
        show();
    };

    const onKey = (e) => {
        if (e.key === 'Escape') { close(); return; }
        if (e.key === 'ArrowLeft') { step(-1); return; }
        if (e.key === 'ArrowRight') step(1);
    };

    // Пока просмотрщик открыт, карусели под ним должны замереть: иначе
    // стрелки листают и его, и слайдер (модуль клавиатуры слушает тот же
    // документ), а автопрокрутка меняет слайд за спиной.
    const freezeSliders = (freeze) => {
        document.querySelectorAll('.ru-swiper').forEach((el) => {
            const sw = el.swiper;
            if (!sw) return;
            if (sw.keyboard) freeze ? sw.keyboard.disable() : sw.keyboard.enable();
            if (sw.autoplay && sw.params.autoplay) freeze ? sw.autoplay.stop() : sw.autoplay.start();
        });
    };

    function open(el) {
        if (!viewer) build();

        // Клоны, которые Swiper добавляет при зацикливании, пропускаем —
        // иначе одна и та же картинка встречается в листании дважды.
        images = [...groupOf(el).querySelectorAll('img')]
            .filter((x) => suits(x) && !x.closest('.swiper-slide-duplicate'));

        if (!images.length) images = [el];

        index = Math.max(images.indexOf(el), 0);
        opener = el;

        show();
        viewer.hidden = false;
        freezeSliders(true);
        // Прокрутка страницы под открытым просмотрщиком сбивает с толку.
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKey);
        viewer.querySelector('.ru-viewer__close').focus();
    }

    function close() {
        if (!viewer || viewer.hidden) return;

        viewer.hidden = true;
        img.removeAttribute('src');
        freezeSliders(false);
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKey);

        // Фокус возвращается туда, откуда пришёл: иначе он улетает в начало
        // страницы и человек с клавиатуры теряет место.
        if (opener) { opener.focus(); opener = null; }
    }

    // Перехват на ФАЗЕ ПОГРУЖЕНИЯ и с остановкой всплытия: клик по картинке
    // предназначен только просмотрщику. Иначе его видят и другие
    // обработчики на документе — в том числе просмотрщики картинок из
    // расширений браузера, и поверх нашего открывается второе окно.
    // Событие mousedown намеренно не трогаем: на нём Swiper начинает
    // перетаскивание, и перехват сломал бы листание свайпом.
    document.addEventListener('click', (e) => {
        const el = e.target.closest && e.target.closest('img');
        if (!suits(el)) return;

        e.preventDefault();
        e.stopPropagation();
        open(el);
    }, true);

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;

        const el = e.target.closest && e.target.closest('img');
        if (!suits(el)) return;

        e.preventDefault();
        open(el);
    });

    // Подсказка курсором и доступ с клавиатуры проставляются здесь, а не в
    // шаблонах: так это работает и в тех, что появятся позже.
    const mark = () => {
        document.querySelectorAll('main img, .page-content img, article img, .ru-swiper img')
            .forEach((el) => {
                if (el.dataset.zoomReady) return;
                if (!suits(el)) return;

                el.dataset.zoomReady = '1';
                el.classList.add('ru-zoomable');
                el.setAttribute('tabindex', '0');
                el.setAttribute('role', 'button');
                if (!el.getAttribute('aria-label')) el.setAttribute('aria-label', T.zoom);
            });
    };

    document.addEventListener('DOMContentLoaded', mark);
    window.addEventListener('load', mark);

    // Картинки появляются и позже: отложенная загрузка, слайды Swiper,
    // подгружаемые блоки. Дешевле пересчитать по событию, чем следить
    // за каждым изменением дерева.
    document.addEventListener('lazyloaded', mark);
    window.addEventListener('resize', mark);
})();
