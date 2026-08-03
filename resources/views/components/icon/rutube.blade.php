{{--
    Глиф Rutube.

    Свой рисунок по логотипу сервиса: тёмно-синий скруглённый квадрат, красный
    сектор в правом верхнем углу и белая литера R. Точной иконки Rutube нет ни
    в Font Awesome, ни в Simple Icons.
--}}
@php $cid = 'rt-c-' . uniqid(); @endphp

<svg viewBox="0 0 48 48" width="{{ $size ?? 20 }}" height="{{ $size ?? 20 }}"
     fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        {{-- Обрезка по скруглённому квадрату: без неё красный круг вылезал бы
             за угол плитки. Уникальный id — на случай нескольких иконок на
             странице. --}}
        <clipPath id="{{ $cid }}">
            <rect width="48" height="48" rx="13"/>
        </clipPath>
    </defs>

    <g clip-path="url(#{{ $cid }})">
        <rect width="48" height="48" fill="#100A44"/>
        <circle cx="36.5" cy="11.5" r="11.5" fill="#EE1B3D"/>

        {{-- Литера R: перекладина с проушиной и косая ножка. --}}
        <path fill="#fff" fill-rule="evenodd" clip-rule="evenodd"
              d="M9.5 12h20.3c4.2 0 6.7 2.5 6.7 6.6v3.6c0 3.6-1.9 6-5.2 6.5L37 36h-7.1l-5.3-7.1h-7.9V36H9.5V12Zm7.2 5.6v5.7h11.1c1.1 0 1.6-.5 1.6-1.6v-2.5c0-1.1-.5-1.6-1.6-1.6H16.7Z"/>
    </g>
</svg>
