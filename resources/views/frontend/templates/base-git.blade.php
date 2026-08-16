{{--
    Шаблон «Уроки: Git».

    Разметка ОБЩАЯ для всех языков — см. partials/lesson. Здесь задаются
    только подпись, значок и фирменный цвет. Значок берётся из локальной
    сборки Font Awesome (`fa-git-alt` в ней есть — проверено грепом), а не
    из набора темы: у Lucide брендовых глифов нет вовсе.
--}}

@include('frontend.templates.partials.lesson', [
    'lang'     => 'Git',
    'icon'     => 'fa-git-alt',
    'accent'   => '#f05033',
    'ink'      => '#ffffff',
    'shortKey' => 'git',
    'template' => 'base-git',
])
