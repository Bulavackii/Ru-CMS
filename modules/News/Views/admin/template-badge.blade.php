@php
    $labels = [
        'default'    => 'Новости',
        'products'   => 'Товары',
        'contacts'   => 'Контакты',
        'gallery'    => 'Галерея',
        'slideshow'  => 'Слайдшоу',
        'faq'        => 'Вопросы',
        'reviews'    => 'Отзывы',
        'test'       => 'Тест',
        'test2'      => 'Тест 2',
    ];

    $colors = [
        'default'    => 'bg-gray-200 text-gray-800',
        'products'   => 'bg-green-100 text-green-900',
        'contacts'   => 'bg-blue-100 text-blue-900',
        'gallery'    => 'bg-purple-100 text-purple-900',
        'slideshow'  => 'bg-pink-100 text-pink-900',
        'faq'        => 'bg-yellow-100 text-yellow-900',
        'reviews'    => 'bg-emerald-100 text-emerald-900',
        'test'       => 'bg-orange-100 text-orange-900',
        'test2'      => 'bg-indigo-100 text-indigo-900',
    ];

    $icons = [
        'default'    => '📰',
        'products'   => '🛍️',
        'contacts'   => '📇',
        'gallery'    => '🖼️',
        'slideshow'  => '🎞️',
        'faq'        => '❓',
        'reviews'    => '⭐',
        'test'       => '🧪',
        'test2'      => '⚙️',
    ];

    $isKnown = array_key_exists($template, $labels);
    $label = $isKnown ? $labels[$template] : '🧩 Другой';
    $style = $isKnown ? $colors[$template] : 'bg-gray-100 text-gray-700';
    $icon = $icons[$template] ?? '🔖';
@endphp

<span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $style }}"
      title="{{ $isKnown ? $template : 'Нестандартный шаблон: ' . $template }}">
    {{ $icon }} {{ $label }}
</span>
