{{--
    Шаблон «Уроки: HTML».

    Разметка ОБЩАЯ для всех четырёх языков — см. partials/lesson. Здесь
    задаются только подпись, значок и фирменный цвет языка. Раньше это были
    четыре полные копии одной вёрстки, и они уже разошлись: подсветка кода
    осталась только у HTML.
--}}

@include('frontend.templates.partials.lesson', [
    'lang'     => 'HTML',
    'icon'     => 'fa-html5',
    'accent'   => '#e34c26',
    'ink'      => '#ffffff',
    'shortKey' => 'html',
    'template' => 'base-html',
])
