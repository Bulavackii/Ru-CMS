/*
 * Формы на сайте.
 *
 * Форма работает и без этого скрипта: отправка обычная, ответ приходит
 * редиректом. Здесь только то, что нельзя сделать разметкой, — защита от
 * второго нажатия и прокрутка к результату.
 */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || !form.classList || !form.classList.contains('rf-form')) {
            return;
        }

        var button = form.querySelector('.rf-btn');

        if (!button) {
            return;
        }

        // Второе нажатие создаёт вторую заявку: пока браузер ждёт ответ,
        // кнопка остаётся живой, и нетерпеливый человек жмёт ещё раз.
        // Блокируем ПОСЛЕ отправки — заблокированная кнопка не отправляет
        // форму вовсе, и обработчик submit до сервера бы не дошёл.
        window.setTimeout(function () {
            button.disabled = true;
            button.dataset.rfLabel = button.textContent;
            button.textContent = button.dataset.rfSending || button.textContent;
        }, 0);
    });

    // После редиректа с якорем браузер прокручивает к форме сам, но если
    // страница длинная и картинки ещё грузятся, позиция уезжает. Повторяем
    // прокрутку, когда всё загрузилось.
    window.addEventListener('load', function () {
        if (!window.location.hash) {
            return;
        }

        var target = document.getElementById(window.location.hash.slice(1));

        if (target && target.classList.contains('rf')) {
            target.scrollIntoView({ block: 'center' });
        }
    });
}());
