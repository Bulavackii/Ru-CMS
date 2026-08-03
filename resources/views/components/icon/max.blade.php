{{--
    Глиф мессенджера MAX.

    Свой рисунок по логотипу сервиса: скруглённый квадрат с сине-фиолетовым
    градиентом и белым «пузырём» разговора. В открытых наборах иконок MAX нет
    вовсе, а слаг max у Simple Icons принадлежит HBO Max — другому бренду.

    Градиент получает уникальный id: несколько таких SVG на одной странице с
    одинаковым id ломали бы заливку друг друга.
--}}
@php $gid = 'max-g-' . uniqid(); @endphp

<svg viewBox="0 0 48 48" width="{{ $size ?? 20 }}" height="{{ $size ?? 20 }}"
     fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="{{ $gid }}" x1="4" y1="44" x2="44" y2="4" gradientUnits="userSpaceOnUse">
            <stop stop-color="#22C5F0"/>
            <stop offset=".45" stop-color="#3B4BF5"/>
            <stop offset="1" stop-color="#A22BE0"/>
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="13" fill="url(#{{ $gid }})"/>

    {{-- Пузырь с «хвостом» влево-вниз и круглым вырезом посередине. --}}
    <path fill="#fff" fill-rule="evenodd" clip-rule="evenodd"
          d="M24 8.5c8.56 0 15.5 6.94 15.5 15.5S32.56 39.5 24 39.5c-2.62 0-5.09-.65-7.26-1.8-1.86 1.04-4.02 1.66-5.72 1.66-1.28 0-2.02-.83-1.79-2.05.24-1.28.86-3.02 1.6-4.7A15.42 15.42 0 0 1 8.5 24c0-8.56 6.94-15.5 15.5-15.5Zm0 8.4a7.1 7.1 0 1 0 0 14.2 7.1 7.1 0 0 0 0-14.2Z"/>
</svg>
