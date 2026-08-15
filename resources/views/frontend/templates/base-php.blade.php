{{--
    Шаблон «Уроки: PHP».

    Разметка ОБЩАЯ для всех четырёх языков — см. partials/lesson. Здесь
    задаются только подпись и фирменный цвет языка. Раньше это были четыре
    полные копии одной вёрстки, и они уже разошлись: подсветка кода
    осталась только у HTML.
--}}

@include('frontend.templates.partials.lesson', [
    'lang'     => 'PHP',
    'accent'   => '#777bb3',
    'ink'      => '#ffffff',
    'shortKey' => 'php',
    'template' => 'base-php',
])
