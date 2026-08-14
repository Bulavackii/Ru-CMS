{{--
    Лейаут экранов входа, регистрации и восстановления пароля.

    Прежний был на четырнадцать строк: серый фон, белая коробка, и всё. Ни
    темы, ни шрифта, ни стеков для стилей и скриптов; язык страницы был прибит
    как «ru» независимо от локали. Смена оформления проекта эти экраны не
    затрагивала вовсе — они оставались синими, когда весь остальной сайт менял
    цвет.

    Здесь: две колонки — слева представление проекта, справа форма. На узком
    экране левая колонка убирается, остаётся форма. Цвета, шрифт и скругления
    берутся из активной темы тем же партиалом, что и на сайте.
--}}
@php
    // Тема сайта: тот же источник, что у layouts.frontend. Композер
    // ThemeServiceProvider отдаёт $activeTheme на каждую вьюху.
    $pageTheme = $siteTheme ?? $activeTheme ?? null;
    $themed = (bool) $pageTheme;
    // Тот же признак, что у layouts.frontend: без класса fx-theme-dark
    // вход и регистрация оставались светлыми при тёмной теме сайта.
    $themeIsDark = theme_is_dark(data_get($pageTheme->tokens ?? [], 'colors.bg'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('frontend.auth.default_title')) — {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">

    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
    @include('layouts.partials.tw-compat')
    {{-- Переменные поверхностей: один набор на сайт и на страницы входа. --}}
    @include('layouts.partials.theme-surfaces')

    {{-- Font Awesome нужен явно, а не «из темы»: партиал темы подключает НАБОР
         ИЗ ЕЁ НАСТРОЙКИ, а по умолчанию это lucide. Разметка этих экранов
         написана на fas-иконках, и без этой строки глазок в поле пароля,
         стрелка «на сайт» и значки в левой колонке не рисовались вовсе —
         на их месте была пустота. Сайт подключает файл ровно так же. --}}
    <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}">

    @include('layouts.partials.theme-head', ['pageTheme' => $pageTheme])

    <style>
        /*
         * Литеральный CSS: в сборке проекта нет opacity-модификаторов,
         * произвольных значений и половины цветов v3 (см. CLAUDE.md), а эти
         * экраны как раз держатся на полупрозрачности и градиенте.
         *
         * Всё цветное завязано на переменные темы — меняется оформление
         * проекта, меняются и эти страницы.
         *
         * Про высоту. Первая версия была в одну колонку с крупным ритмом, и
         * регистрация организации не помещалась в окно 1280×720: страница
         * уезжала на пятьсот пикселей. Здесь ритм плотнее, поля на широком
         * экране идут в две колонки, а прокручивается при нужде ТОЛЬКО правая
         * колонка — левая с представлением проекта остаётся на месте, и общей
         * прокрутки страницы не возникает никогда.
         */
        .au {
            --au-primary: var(--color-primary, #6366f1);
            --au-accent: var(--color-accent, #8b5cf6);
            --au-bg: var(--color-bg, #f4f5fb);
            --au-text: var(--color-text, #111827);
            /* Подложки берутся из общего набора поверхностей (партиал
               layouts.partials.theme-surfaces), а не задаются здесь заново.
               Прежние прибитые значения были светлыми, поэтому при тёмной
               теме сайта карточка входа оставалась белой, а подписи полей на
               ней — светлыми: контраст 1.2 при пороге 4.5. Второй набор тех
               же цветов рядом с общим неминуемо разъезжается — в этом
               проекте так уже случалось не раз. */
            --au-card: var(--surface, #ffffff);
            --au-line: var(--surface-bd, #e3e6ee);
            --au-soft: var(--surface-2, #f7f8fc);
            --au-muted: var(--surface-mute, #6b7280);
            --au-radius: var(--radius-md, 12px);

            margin: 0;
            font-family: var(--font-base, -apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif);
            color: var(--au-text);
            background: var(--au-bg);
        }

        /* Высота фиксирована по окну, поэтому страница целиком не прокручивается. */
        .au-wrap { display: grid; height: 100vh; height: 100dvh; overflow: hidden }
        @media (min-width: 1024px) { .au-wrap { grid-template-columns: 1.02fr 1fr } }

        /* ── Левая колонка: представление проекта ── */
        .au-aside {
            display: none;
            position: relative;
            flex-direction: column;
            justify-content: space-between;
            padding: 34px 40px;
            color: #fff;
            /* Три остановки вместо двух: ровный переход двух ярких тонов
               читался плоско, а уход в тёмный к нижнему краю даёт колонке
               глубину и держит подпись внизу читаемой. Тёмный тон выведен
               из акцента, поэтому следует теме, а не прибит числом. */
            background: linear-gradient(155deg,
                var(--au-primary) 0%,
                var(--au-accent) 52%,
                color-mix(in srgb, var(--au-accent) 58%, #171033) 100%);
            overflow: hidden;
        }
        @media (min-width: 1024px) { .au-aside { display: flex } }

        /* Свечение поверх заливки — иначе она выглядит плоской. Рисуется
           CSS: ничего не грузится и не зависит от темы. */
        .au-aside::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                radial-gradient(620px 460px at 86% -10%, rgba(255,255,255,.22), transparent 64%),
                radial-gradient(460px 400px at -8% 106%, rgba(255,255,255,.14), transparent 62%);
            pointer-events: none;
        }

        /* ── Фактура колонки ───────────────────────────────────────────
           Это настоящий QR-код адреса сайта, нарисованный своим генератором
           и увеличенный так, что в кадр попадает только угол. Сканировать
           его не предлагается — из кадра он обрезан; смысл в том, что
           модульная сетка и есть облик этой CMS: тот же рисунок собирается
           на странице привязки двухфакторной проверки.

           Раньше здесь лежала ровная сетка 42×42 из двух градиентов. Она не
           значила ничего и встречается на каждой второй странице входа. */
        .au-pattern {
            position: absolute; right: -14%; bottom: -20%;
            width: 76%; aspect-ratio: 1;
            color: #fff; opacity: .1;
            /* Растворяем к содержимому, иначе фактура лезет под текст. */
            -webkit-mask-image: radial-gradient(125% 125% at 100% 100%, #000 18%, transparent 70%);
            mask-image: radial-gradient(125% 125% at 100% 100%, #000 18%, transparent 70%);
            pointer-events: none;
        }
        .au-pattern svg { display: block; width: 100%; height: 100% }

        /* Название набрано моноширинным с крупным просветом — та же
           микроподпись, что у надзаголовка формы и в разделах панели. */
        .au-brand { position: relative; display: inline-flex; align-self: flex-start;
                    align-items: center; gap: 11px; color: #fff; text-decoration: none }
        /* Подложки у знака нет: фигура сама по себе читается, а бледный
           квадрат вокруг делал её похожей на заглушку. */
        .au-brand-mark {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; flex: 0 0 auto; color: #fff;
        }
        .au-brand-mark svg { display: block; width: 100%; height: 100% }

        .au-brand-text { display: flex; flex-direction: column; gap: 2px; line-height: 1 }
        /* Название — моноширинным с крупным просветом: та же микроподпись,
           что у надзаголовка формы и в разделах панели. */
        .au-brand-name { font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                         font-size: .78rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase }
        .au-brand-tag { font-size: .68rem; letter-spacing: .01em; color: rgba(255, 255, 255, .72) }

        .au-lead { position: relative; max-width: 27rem }
        .au-lead-rule { display: block; width: 44px; height: 3px; margin-bottom: 15px;
                        background: linear-gradient(90deg, #fff, rgba(255, 255, 255, .25)) }
        .au-lead h2 { margin: 0 0 11px; font-size: clamp(1.9rem, 2.7vw, 2.35rem); font-weight: 800;
                      line-height: 1.06; letter-spacing: -.03em }
        .au-lead p { margin: 0; max-width: 25rem; font-size: .93rem; line-height: 1.6; color: rgba(255, 255, 255, .9) }

        /* Строки разделены волосяными линиями, а плитки значков квадратные —
           тот же приём, что в списках разделов панели. Круглые заливки
           смотрелись мягче остального оформления. */
        .au-points { position: relative; display: grid; gap: 0; margin: 22px 0 0; padding: 0; list-style: none }
        .au-points li { display: flex; align-items: center; gap: 11px; padding: 9px 0;
                        font-size: .85rem; line-height: 1.4;
                        border-top: 1px solid rgba(255, 255, 255, .17) }
        .au-points i {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; font-size: 10px; flex: 0 0 auto;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 0;
        }

        .au-aside-foot { position: relative;
                         font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                         font-size: .68rem; letter-spacing: .04em; color: rgba(255, 255, 255, .78) }
        .au-aside-foot a { color: #fff; text-decoration: underline }

        /* ── Правая колонка: форма ── */
        .au-main {
            display: flex; align-items: center; justify-content: center;
            padding: 24px 20px;
            /* Прокручивается только эта колонка, и только если содержимое всё
               же не поместилось (низкое окно, включённая каптча). */
            overflow-y: auto;
        }
        .au-card { width: 100%; max-width: 26rem; margin: auto }
        /* Регистрация шире: её поля идут в две колонки. */
        .au-card--wide { max-width: 34rem }

        .au-top { margin-bottom: 16px }
        /* Возврат на сайт — не сноска, а отдельное действие: своя рамка,
           прямые края и моноширинная подпись, как у остальных микроподписей
           этих страниц. Стрелка отъезжает влево при наведении — направление
           перехода видно до нажатия. */
        .au-toprow { display: flex; align-items: center; justify-content: space-between;
                     gap: 12px; margin-bottom: 11px }

        .au-back {
            display: inline-flex; align-items: stretch; flex: 0 0 auto;
            color: var(--au-text); text-decoration: none;
            background: var(--au-card); border: 1px solid var(--au-line); border-radius: 0;
            box-shadow: 0 1px 2px rgba(17,24,39,.05);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .au-back__ico {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; font-size: .68rem; color: #fff;
            background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
        }
        .au-back__ico i { transition: transform .15s ease }
        .au-back__t {
            padding: 6px 11px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .64rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase;
        }
        .au-back:hover { border-color: var(--au-primary); box-shadow: 0 3px 10px color-mix(in srgb, var(--color-primary, #6366f1) 22%, transparent) }
        .au-back:hover .au-back__ico i { transform: translateX(-2px) }
        .au-back:focus-visible { outline: 2px solid var(--au-primary); outline-offset: 2px }
        @media (prefers-reduced-motion: reduce) {
            .au-back, .au-back__ico i { transition: none }
            .au-back:hover .au-back__ico i { transform: none }
        }


        .au-head { display: flex; align-items: center; gap: 12px }
        .au-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 42px; height: 42px; font-size: 16px; color: #fff; flex: 0 0 auto;
            background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
            border-radius: var(--au-radius);
        }
        /* Надзаголовок набран моноширинным — второй «шрифт» страницы, за
           который не надо платить загрузкой: тем же набором в проекте
           показываются ключ привязки и коды. Мелкий кегль с крупным
           межбуквенным просветом — та же микроподпись, что в разделах
           «Фрагменты» и «Меню». */
        .au-eyebrow {
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .62rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
            color: var(--au-muted);
        }
        /* Заголовок — единственное громкое место карточки. Плотный трекинг
           на тяжёлом начертании: у Inter это заметно меняет характер, а
           платить загрузкой второго шрифта не приходится. */
        .au-title { margin: 0; font-weight: 800;
                    font-size: clamp(1.75rem, 5.4vw, 2.15rem); line-height: 1.04; letter-spacing: -.035em }

        /* Прямые края по всей карточке — тот же рубильник, что в панели
           (body.admin-sharp). Перечислять классы по одному пришлось бы в
           каждой вьюхе: у привязки свои плитки, у ввода кода — свои. */
        body.au .au-card, body.au .au-card * { border-radius: 0 !important }
        .au-sub { margin: 4px 0 0; font-size: .83rem; line-height: 1.5; color: var(--au-muted) }

        .au-box {
            position: relative;
            padding: 20px;
            background: var(--au-card);
            border: 1px solid var(--au-line);
            border-radius: var(--au-radius);
            box-shadow: 0 1px 2px rgba(17,24,39,.04), 0 14px 34px rgba(17,24,39,.08);
            overflow: hidden;
        }
        /* Тонкая акцентная полоса сверху — тот же приём, что у карточек панели. */
        .au-box::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--au-primary), var(--au-accent), var(--au-primary));
        }

        /* ── Поля ── */
        .au-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 11px; min-width: 0 }
        /* Подписи полей — та же микроподпись, что у надзаголовка и знака. */
        .au-label {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .66rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
            color: color-mix(in srgb, var(--au-text) 74%, var(--au-card));
        }
        .au-req { margin-left: 3px; color: #dc2626 }

        .au-input {
            width: 100%; padding: 9px 12px;
            font: inherit; font-size: .87rem; color: var(--au-text);
            background: var(--au-soft); border: 1px solid var(--au-line);
            border-radius: calc(var(--au-radius) - 5px);
            outline: none; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .au-input:focus { background: var(--surface,#fff); border-color: var(--au-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #6366f1) 15%, transparent) }
        .au-input::placeholder { color:var(--surface-dim,#a8aebb) }
        .au-input.is-bad { border-color: #dc2626 }
        textarea.au-input { resize: vertical }

        .au-with-btn { position: relative }
        .au-with-btn .au-input { padding-right: 38px }
        .au-eye {
            position: absolute; right: 2px; top: 0; bottom: 0;
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; color: var(--au-muted); background: none; border: 0; cursor: pointer;
        }
        .au-eye:hover { color: var(--au-text) }

        .au-hint { font-size: .72rem; line-height: 1.4; color: var(--au-muted) }
        .au-err { font-size: .74rem; line-height: 1.4; color: #dc2626 }

        .au-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
                  gap: 8px; margin-bottom: 13px }
        .au-check { display: inline-flex; align-items: flex-start; gap: 8px; font-size: .8rem; line-height: 1.45; cursor: pointer }
        .au-check input { margin-top: 2px; width: 14px; height: 14px; accent-color: var(--au-primary); flex: 0 0 auto }
        .au-link { color: var(--au-primary); font-size: .8rem; text-decoration: none }
        .au-link:hover { text-decoration: underline }

        .au-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            width: 100%; padding: 11px 20px;
            font: inherit; font-size: .89rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, var(--au-primary), var(--au-accent));
            border: 0; border-radius: calc(var(--au-radius) - 5px);
            cursor: pointer; transition: filter .15s ease, transform .05s ease, box-shadow .15s ease;
            box-shadow: 0 6px 16px color-mix(in srgb, var(--color-primary, #6366f1) 28%, transparent);
        }
        .au-btn:hover { filter: brightness(1.07) }
        .au-btn:active { transform: translateY(1px) }
        .au-btn[disabled] { opacity: .6; cursor: progress }

        .au-btn--ghost {
            color: var(--au-text); background: var(--surface,#fff); border: 1px solid var(--au-line); box-shadow: none;
        }
        .au-btn--ghost:hover { background: var(--au-soft); filter: none }

        .au-foot { margin-top: 13px; font-size: .82rem; text-align: center; color: var(--au-muted) }
        .au-foot a { color: var(--au-primary); font-weight: 600; text-decoration: none }
        .au-foot a:hover { text-decoration: underline }

        /* ── Сообщения ── */
        .au-note { display: flex; gap: 9px; margin-bottom: 12px; padding: 9px 11px;
                   font-size: .81rem; line-height: 1.45; border-radius: calc(var(--au-radius) - 5px) }
        .au-note--ok  { color: #14532d; background: #dcfce7; border: 1px solid #86efac }
        .au-note--bad { color: #7f1d1d; background: #fee2e2; border: 1px solid #fca5a5 }
        .au-note--info { color: #1e3a8a; background: #dbeafe; border: 1px solid #93c5fd }
        .au-note ul { margin: 4px 0 0; padding-left: 17px }

        /* ── Разделитель и сетка полей ── */
        .au-split { display: flex; align-items: center; gap: 10px; margin: 12px 0 10px;
                    color: var(--au-muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em }
        .au-split::before, .au-split::after { content: ''; height: 1px; flex: 1 1 auto; background: var(--au-line) }

        .au-grid { display: grid; gap: 0 12px }
        @media (min-width: 560px) { .au-grid--2 { grid-template-columns: 1fr 1fr } }

        /* ── Сила пароля ── */
        .au-strength { display: flex; gap: 3px; margin-top: 5px }
        .au-strength span { height: 3px; flex: 1 1 auto; background: #e5e7eb; border-radius: 2px; transition: background .2s ease }
        .au-strength.lv1 span:nth-child(1) { background: #dc2626 }
        .au-strength.lv2 span:nth-child(-n+2) { background: #f59e0b }
        .au-strength.lv3 span:nth-child(-n+3) { background: #eab308 }
        .au-strength.lv4 span { background: #16a34a }

        /* ── Тёмное оформление ── */
        @media (prefers-color-scheme: dark) {
            .au { --au-card: #171b24; --au-line: #333a49; --au-soft: #10141c; --au-text: #e6e8ee; --au-muted: #9aa3b2 }
            .au-input { color: #e6e8ee }
            .au-input:focus { background: #10141c }
            .au-btn--ghost { color: #e6e8ee; background: #171b24 }
            .au-btn--ghost:hover { background: #1d222d }
            .au-strength span { background: #333a49 }
        }

        /* Низкое окно: ритм ещё плотнее, чтобы форма помещалась без прокрутки.
           Ужимаем отступы и вспомогательные строки, но не сами поля — по ним
           попадают пальцем и курсором. */
        @media (min-width: 1024px) and (max-height: 800px) {
            .au-main { padding: 14px 20px }
            .au-aside { padding: 24px 32px }
            .au-lead h2 { font-size: 1.8rem; margin-bottom: 8px }
            .au-lead p { font-size: .88rem }
            .au-points { gap: 7px; margin-top: 16px }
            .au-box { padding: 15px }
            .au-field { margin-bottom: 8px }
            .au-top { margin-bottom: 10px }
            /* Отступ держит строка целиком: у кнопки собственный нижний
               отступ сдвигал её вверх относительно надзаголовка, с которым
               она теперь стоит в одну линию. */
            .au-toprow { margin-bottom: 8px }
            .au-badge { width: 36px; height: 36px; font-size: 14px }
            /* Ниже обычного, но не мелким: 1.25rem съедало характер
               заголовка ради нескольких пикселей. */
            .au-title { font-size: 1.6rem }
            .au-split { margin: 9px 0 8px }
            .au-note { margin-bottom: 9px; padding: 8px 10px }
            .au-foot { margin-top: 10px }
        }

        /* ═════════ Телефоны и планшеты ═════════
           Замер на iPhone XR: поля 41 пиксель, кнопка входа 43, галочка
           «запомнить меня» — 14×14. Пальцем в такое не попасть.

           ⚠️ Шрифт поля ровно 16px — это не про читаемость. Safari на iOS
           автоматически увеличивает страницу, когда фокус уходит в поле с
           текстом мельче 16, и после этого вёрстка «уезжает»: пользователь
           видит форму в увеличенном виде и вручную отматывает обратно. */
        @media (max-width: 1023px), (max-height: 500px) {
            .au-input { min-height: 44px; font-size: 16px }
            .au-btn { min-height: 44px }

            /* Галочку растим сами и даём ей строку в 44 пикселя: попасть
               надо в квадратик, а не в подпись рядом. */
            .au-check { min-height: 44px; align-items: center; font-size: .85rem }
            .au-check input { width: 20px; height: 20px; margin-top: 0 }

            /* Ссылки-действия («забыли пароль», «зарегистрироваться») —
               отдельная строка нажатия, а не подчёркнутый текст в 19 пикселей. */
            .au-link { display: inline-flex; align-items: center; min-height: 44px }
            .au-label { font-size: .8rem }

            /* Кнопка «показать пароль» была 34 в ширину при высоте 44 —
               попасть в такую полоску пальцем тяжело. */
            .au-eye { width: 44px }

            /* Поле каптчи живёт в своём модуле и мимо .au-input: было 38 в
               высоту и 13px шрифтом — тот самый случай, когда Safari на iOS
               увеличивает страницу при фокусе. */
            .captcha-input { min-height: 44px; font-size: 16px }

            /* Возврат к предыдущему шагу — тоже цель нажатия. */
            .au-back { display: inline-flex; align-items: center; min-height: 44px }
        }
    </style>

    @stack('styles')

    {{-- Alpine: в этом лейауте @vite нет, поэтому подключаем локальной
         сборкой — как во frontend-install. Двойной загрузки не возникает
         (та мина живёт в layouts/frontend, здесь её нет). --}}
    <script defer src="{{ local_js('alpine.min.js') }}"></script>
</head>
<body class="au {{ $themed ? 'fx-themed' : '' }} {{ $themeIsDark ? 'fx-theme-dark' : '' }}">
    <div class="au-wrap">

        {{-- ── Представление проекта ── --}}
        <aside class="au-aside">
            <div class="au-pattern">{!! auth_pattern_svg() !!}</div>

            {{-- Знак и надпись — один локап. Раньше картинка темы выводилась
                 РЯДОМ с названием, и на месте знака висел второй логотип: у
                 светлых логотипов на этой заливке он читался как пятно, а
                 название дублировалось. Теперь картинка темы занимает место
                 знака, а подпись под названием говорит, что это за продукт. --}}
            <a href="{{ url('/') }}" class="au-brand">
                {{-- Знак — угол QR-кода: та самая фигура, из которой собрана
                     фактура колонки, и та же, что рисует своя реализация кода
                     на странице привязки. Раньше здесь стоял общий глиф
                     «слои» в полупрозрачном квадрате: на этой заливке он
                     читался как пустая рамка и не значил ничего.

                     Пропорции настоящие: рамка 7×7 модулей с ядром 3×3 плюс
                     чередующиеся модули полосы синхронизации — поэтому
                     фигура опознаётся как код, а не как квадрат в квадрате. --}}
                <span class="au-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 9 9" shape-rendering="crispEdges" focusable="false">
                        <rect x=".5" y=".5" width="6" height="6" fill="none" stroke="currentColor" stroke-width="1"/>
                        <rect x="2" y="2" width="3" height="3" fill="currentColor"/>
                        <g fill="currentColor" opacity=".72">
                            <rect x="8" y="0" width="1" height="1"/>
                            <rect x="8" y="2" width="1" height="1"/>
                            <rect x="8" y="4" width="1" height="1"/>
                            <rect x="8" y="6" width="1" height="1"/>
                            <rect x="0" y="8" width="1" height="1"/>
                            <rect x="2" y="8" width="1" height="1"/>
                            <rect x="4" y="8" width="1" height="1"/>
                            <rect x="6" y="8" width="1" height="1"/>
                        </g>
                    </svg>
                </span>

                {{-- Логотип темы здесь намеренно НЕ выводится. Это широкая
                     надпись (200×40), нарисованная под светлую подложку: на
                     цветной заливке колонки её тёмные буквы почти не видны, а
                     в рамке знака картинка сжималась в полоску 30×6. Название
                     набирается текстом — оно читается при любой теме. --}}
                <span class="au-brand-text">
                    <span class="au-brand-name">{{ config('app.name') }}</span>
                    <span class="au-brand-tag">{{ __('frontend.common.cms_tagline') }}</span>
                </span>
            </a>

            <div class="au-lead">
                {{-- Та же акцентная черта, что открывает разделы панели. --}}
                <span class="au-lead-rule" aria-hidden="true"></span>
                <h2>@yield('aside_title', __('frontend.auth.aside_title'))</h2>
                <p>@yield('aside_text', __('frontend.auth.aside_text'))</p>

                <ul class="au-points">
                    <li><i class="fas fa-bolt"></i> <span>{{ __('frontend.auth.point_orders') }}</span></li>
                    <li><i class="fas fa-shield-halved"></i> <span>{{ __('frontend.auth.point_safe') }}</span></li>
                    <li><i class="fas fa-headset"></i> <span>{{ __('frontend.auth.point_support') }}</span></li>
                </ul>
            </div>

            <div class="au-aside-foot">
                &copy; {{ date('Y') }} {{ config('app.name') }} ·
                <a href="{{ url('/privacy') }}">{{ __('frontend.auth.privacy') }}</a>
            </div>
        </aside>

        {{-- ── Форма ──
             Ширину задаёт сама страница секцией card_class: регистрации нужны
             две колонки полей, остальным экранам узкая карточка читается
             лучше. --}}
        <main class="au-main">
            <div class="au-card @yield('card_class')">
                <div class="au-top">
                    {{-- Надзаголовок и возврат на сайт стоят в одной строке.
                         Отдельной строкой кнопка висела над карточкой сама по
                         себе: красивая, но ни к чему не привязанная. Здесь у
                         неё есть пара — слева подпись шага, справа выход.

                         Надзаголовок необязателен: на регистрации и
                         восстановлении пароля его нет, и кнопка тогда просто
                         остаётся слева, как было раньше.

                         Стрелка вынесена в градиентную плитку — тот же приём
                         «плитка + надпись», что у заголовка страницы и у знака
                         в левой колонке. --}}
                    <div class="au-toprow">
                        @hasSection('eyebrow')
                            <span class="au-eyebrow">@yield('eyebrow')</span>
                        @endif

                        <a href="{{ url('/') }}" class="au-back">
                            <span class="au-back__ico" aria-hidden="true"><i class="fas fa-arrow-left"></i></span>
                            <span class="au-back__t">{{ __('frontend.auth.to_site') }}</span>
                        </a>
                    </div>

                    <div class="au-head">
                        <span class="au-badge" aria-hidden="true"><i class="fas @yield('icon', 'fa-right-to-bracket')"></i></span>
                        <div class="min-w-0">
                            <h1 class="au-title">@yield('heading')</h1>
                            @hasSection('lead')
                                <p class="au-sub">@yield('lead')</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="au-box">
                    @yield('content')
                </div>

                @hasSection('under')
                    <div class="au-foot">@yield('under')</div>
                @endif
            </div>
        </main>
    </div>

    <script>
        // Показ и скрытие пароля. Отдельного скрипта в проекте под это нет, а
        // поле пароля есть на четырёх экранах — держим здесь, в одном месте.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.au-eye');

            if (!button) {
                return;
            }

            var input = button.parentElement.querySelector('input');

            if (!input) {
                return;
            }

            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.querySelector('i').className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
            input.focus();
        });
    </script>

    @stack('scripts')
</body>
</html>
