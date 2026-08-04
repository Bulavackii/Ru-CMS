{{--
    Карта с согласием посетителя.

    Вставляется шорткодом [map q="Город, улица, дом"] прямо из визуального
    редактора. Пока посетитель не нажал кнопку, к картографу не уходит НИ
    ОДНОГО запроса — ни адреса страницы, ни IP. Это и про персональные
    данные, и про скорость: тяжёлый скрипт карты не грузится всем подряд.

    Адрес подставляется в готовую ссылку картографа, ключей и регистрации
    для этого не нужно.
--}}

@php
    $query = trim((string) ($query ?? ''));

    if ($query === '') {
        $query = config('app.name') . ', Россия';
    }

    $src = 'https://yandex.ru/map-widget/v1/?text=' . rawurlencode($query) . '&z=15';
    $link = 'https://yandex.ru/maps/?text=' . rawurlencode($query);
@endphp

<div class="pc-map" data-map-src="{{ $src }}">
    <div class="pc-map__stub">
        <strong>{{ $query }}</strong>

        <p class="pc-map__note">
            Карту показывает сторонний сервис. Она загрузится только после
            вашего согласия — до нажатия никакие данные туда не уходят.
        </p>

        <button type="button" class="pc-map__btn">Показать карту</button>

        <p class="pc-map__note">
            Или <a href="{{ $link }}" target="_blank" rel="noopener">откройте карты
            в новой вкладке</a>.
        </p>
    </div>
</div>
