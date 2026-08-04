/**
 * Кнопка «Блоки» в визуальном редакторе.
 *
 * Вставляет готовые заготовки оформления в позицию курсора. Раньше эти же
 * блоки лежали в стандартном списке «Стили», но тот рисуется выпадающим
 * списком с подписью «Абзац» — визуально неотличимым от соседнего списка
 * блоков, и найти там что-либо было невозможно. Отдельная подписанная
 * кнопка решает это.
 *
 * Заготовка вставляется вместе с примерным содержимым: редактору остаётся
 * заменить текст, а не собирать разметку с нуля. Оформление берётся из
 * public/assets/css/content-blocks.css — того же файла, что подключён и на
 * сайте, поэтому в редакторе блок выглядит так же, как у посетителя.
 */
(function () {
    'use strict';

    const snippets = [
        {
            title: 'Вводный абзац',
            html: '<p class="pc-lead">Короткий вводный абзац: о чём эта страница и зачем её читать.</p>',
        },
        {
            title: 'Сетка карточек',
            html:
                '<div class="pc-grid">' +
                    '<div class="pc-card">' +
                        '<span class="pc-ico"><i class="fas fa-star"></i></span>' +
                        '<strong>Заголовок карточки</strong>' +
                        '<p>Короткое пояснение в две-три строки.</p>' +
                    '</div>' +
                    '<div class="pc-card">' +
                        '<span class="pc-ico"><i class="fas fa-bolt"></i></span>' +
                        '<strong>Вторая карточка</strong>' +
                        '<p>Столько карточек, сколько нужно — сетка подстроится.</p>' +
                    '</div>' +
                '</div>',
        },
        {
            title: 'Список с галочками',
            html:
                '<ul class="pc-check">' +
                    '<li>Первый пункт</li>' +
                    '<li>Второй пункт</li>' +
                    '<li>Третий пункт</li>' +
                    '<li>Четвёртый пункт</li>' +
                '</ul>',
        },
        {
            title: 'Строка цифр',
            html:
                '<ul class="pc-stats">' +
                    '<li><b>10</b><span>подпись</span></li>' +
                    '<li><b>5</b><span>подпись</span></li>' +
                    '<li><b>3</b><span>подпись</span></li>' +
                    '<li><b>0</b><span>подпись</span></li>' +
                '</ul>',
        },
        {
            title: 'Нумерованные шаги',
            html:
                '<ol class="pc-steps">' +
                    '<li><strong>Первый шаг</strong>Что нужно сделать.</li>' +
                    '<li><strong>Второй шаг</strong>Что происходит дальше.</li>' +
                    '<li><strong>Третий шаг</strong>Чем всё заканчивается.</li>' +
                '</ol>',
        },
        {
            title: 'Вопросы и ответы',
            html:
                '<div class="pc-faq">' +
                    '<details class="pc-faq__item">' +
                        '<summary>Первый вопрос?</summary>' +
                        '<p>Ответ на первый вопрос.</p>' +
                    '</details>' +
                    '<details class="pc-faq__item">' +
                        '<summary>Второй вопрос?</summary>' +
                        '<p>Ответ на второй вопрос.</p>' +
                    '</details>' +
                '</div>',
        },
        {
            title: 'Врезка-примечание',
            html: '<p class="pc-note">Важное замечание, которое не должно потеряться в тексте.</p>',
        },
        {
            title: 'Текст и картинка',
            html:
                '<div class="pc-split">' +
                    '<div><p>Текст слева. Справа — изображение: замените его своим через кнопку вставки картинки.</p></div>' +
                    '<img src="/images/pages/about-modules.svg" alt="Описание изображения">' +
                '</div>',
        },
        {
            title: 'Чипы технологий',
            html:
                '<ul class="pc-tech">' +
                    '<li>Первое</li><li>Второе</li><li>Третье</li>' +
                '</ul>',
        },
        {
            title: 'Полоса призыва',
            html:
                '<div class="pc-cta">' +
                    '<p><strong>Заголовок призыва</strong><br>Короткое пояснение под ним</p>' +
                    '<a href="/news">Текст кнопки</a>' +
                '</div>',
        },
    ];

    /**
     * Регистрирует кнопку у переданного редактора.
     * Вызывается из настройки каждого редактора (параметр setup).
     */
    window.ruEditorBlocks = function (editor) {
        if (!editor || !editor.ui || !editor.ui.registry) return;

        editor.ui.registry.addMenuButton('ruBlocks', {
            text: 'Блоки',
            tooltip: 'Вставить готовый блок оформления',
            fetch: function (callback) {
                callback(snippets.map(function (item) {
                    return {
                        type: 'menuitem',
                        text: item.title,
                        onAction: function () {
                            // Перевод строки после блока: иначе курсор остаётся
                            // внутри вставленного элемента и следующий абзац
                            // набирается прямо в нём.
                            editor.insertContent(item.html + '<p><br></p>');
                        },
                    };
                }));
            },
        });
    };
})();
