📦 Добавление нового шаблона (типа контента) в Ru CMS
Этот гайд поможет тебе добавить новый шаблон (например: Товары, Контакты, Галерея), который будет использоваться при создании новостей и выводиться в отдельном блоке на главной странице сайта.

1. 📁 Добавление нового шаблона
➕ Шаг 1: Укажи шаблон при создании новости
В админке, при создании или редактировании новости, выбери нужный шаблон из выпадающего списка:

<select name="template">
    <option value="default">Новости</option>
    <option value="products">Товары</option>
    <option value="contacts">Контакты</option>
    <option value="gallery">Галерея</option>
    <option value="faq">FAQ</option> <!-- 🆕 Новый шаблон -->
</select>
Можно добавить и другие значения, например partners, events и т.д.

2. 🧠 Логика маршрута
В routes/web.php на главной странице происходит автоматическая группировка:

$templates = [
    'default' => [],
    'products' => [],
    'contacts' => [],
    'gallery' => [],
    'faq' => [], // 🆕 Новый шаблон
];

$allNews = News::with('categories')->where('published', true)->get();
foreach ($allNews as $item) {
    $key = $item->template ?: 'default';
    $templates[$key][] = $item;
}
⚠️ Если не добавить ключ faq в массив $templates, блок не отобразится.

3. 🧾 Отображение на главной
В resources/views/frontend/home.blade.php в блоке @foreach шаблон отрисуется автоматически:

@php
    $titles = [
        'default' => 'Новости',
        'products' => 'Товары',
        'contacts' => 'Контакты',
        'gallery' => 'Галерея',
        'faq' => 'Вопросы и ответы', // 🆕
    ];
@endphp

@foreach ($templates as $key => $newsList)
    <x-frontend.news-grid
        :newsList="$newsList"
        :title="$titles[$key] ?? ucfirst($key)"
    />
@endforeach
4. 🧩 Компонент отображения новостей
Все шаблоны используют один Blade-компонент:
resources/views/components/frontend/news-grid.blade.php

Если ты хочешь кастомизировать отображение шаблона (например, в блоке products показывать цену) — можно:

либо доработать компонент с условием по $title или $key

либо создать новый компонент и добавить условие в home.blade.php

5. ✅ Пример
Допустим, ты хочешь добавить шаблон reviews (Отзывы):

В Blade-дропдауне добавь:

<option value="reviews">Отзывы</option>
В web.php добавь:

'reviews' => [],
В home.blade.php:

'reviews' => 'Отзывы',
🔁 Повторяй при необходимости
Можешь добавлять неограниченное количество шаблонов (типа контента) без правки контроллеров — только Blade-шаблоны и маршруты.
