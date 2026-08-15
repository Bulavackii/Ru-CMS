{{--
    Шаблон «Уроки: CSS».

    Разметка ОБЩАЯ для всех четырёх языков — см. partials/lesson. Здесь
    задаются только подпись, значок и фирменный цвет языка. Раньше это были
    четыре полные копии одной вёрстки, и они уже разошлись: подсветка кода
    осталась только у HTML.
--}}

@include('frontend.templates.partials.lesson', [
    'lang'     => 'CSS',
    'icon'     => 'fa-css3-alt',
    'accent'   => '#2965f1',
    'ink'      => '#ffffff',
    'shortKey' => 'css',
    'template' => 'base-css',
])
