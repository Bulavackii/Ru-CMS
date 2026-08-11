<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('install.title'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- 🎨 Tailwind CSS (локально) --}}
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">

    {{-- ⚡ Alpine.js для интерактивности (показ/скрытие пароля и т.д.) --}}
    <script defer src="{{ local_js('alpine.min.js') }}"></script>

    {{-- 🧭 Lucide — лёгкие line-иконки в духе SF Symbols, без единого
         обращения к CDN (вендорено локально в public/assets/js) --}}
    <script src="{{ local_js('lucide.min.js') }}"></script>

    <style>
        /*
         * Мастер установки — светлый «стеклянный» интерфейс (glassmorphism),
         * который целиком помещается во вьюпорт без вертикальной прокрутки.
         * Основной акцент интерфейса чёрно-белый (кнопки, текст), но на каждом
         * шаге добавлен свой цветной акцент (--accent): им подсвечивается аура
         * фона, полоска и иконка-бейдж карточки, фокус полей и активный шаг.
         * Цвет шага задаётся через @yield('accent') и живёт на .install-backdrop,
         * откуда наследуется вниз ко всем элементам карточки.
         *
         * Шрифтовой стек в духе macOS: на самой macOS/iOS -apple-system
         * резолвится в San Francisco без сети, на остальных платформах —
         * локально захостенный Inter.
         */
        :root {
            --accent: #6366f1;
        }
        :root, body {
            font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, ui-sans-serif, system-ui, sans-serif;
        }
        [x-cloak] { display: none !important; }

        /* ───────────────────────── Широкий каркас ───────────────────────────
           Слева фирменная колонка с лестницей шагов и советом, справа сам
           шаг. Раньше мастер был узкой карточкой по центру пустого экрана:
           на ноутбуке две трети ширины пропадали, а шаги и подсказки
           теснились внутри той же карточки. */
        /* Высота фиксирована по окну, а не min-height: страница целиком не
           прокручивается никогда, длинный шаг скроллится внутри своей
           карточки. */
        .ins-shell{ display:grid; height:100vh; height:100dvh; overflow:hidden }
        @media (min-width:1024px){ .ins-shell{ grid-template-columns:22rem minmax(0,1fr) } }
        @media (min-width:1536px){ .ins-shell{ grid-template-columns:26rem minmax(0,1fr) } }

        .ins-aside{ display:none; position:relative; flex-direction:column; gap:1.25rem;
            padding:2rem 1.75rem; color:#fff; overflow-y:auto; overflow-x:hidden;
            background:linear-gradient(155deg,
                var(--accent) 0%,
                color-mix(in srgb, var(--accent) 72%, #8b5cf6) 52%,
                color-mix(in srgb, var(--accent) 55%, #171033) 100%) }
        @media (min-width:1024px){ .ins-aside{ display:flex } }

        /* Свечение поверх заливки — иначе она выглядит плоской. Рисуется
           CSS: ничего не грузится и не зависит от темы. */
        .ins-aside__glow{ position:absolute; inset:0; pointer-events:none;
            background:
                radial-gradient(620px 460px at 86% -10%, rgba(255,255,255,.22), transparent 64%),
                radial-gradient(460px 400px at -8% 106%, rgba(255,255,255,.14), transparent 62%) }

        .ins-brand{ position:relative; display:flex; align-items:center; gap:.75rem }
        .ins-brand__badge{ display:grid; place-items:center; flex:none; width:2.75rem; height:2.75rem;
            color:#fff; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28) }
        .ins-brand__text{ display:flex; flex-direction:column; min-width:0 }
        .ins-brand__eyebrow{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.58rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase;
            color:rgba(255,255,255,.75) }
        .ins-brand__name{ font-size:1.35rem; font-weight:800; letter-spacing:-.02em; line-height:1.1 }

        /* ── Лестница шагов ── */
        .ins-steps{ position:relative; display:grid; gap:.15rem; margin:0; padding:0; list-style:none; flex:1 }
        .ins-step{ display:flex; align-items:center; gap:.7rem; padding:.5rem .55rem;
            color:rgba(255,255,255,.72); transition:background .15s ease, color .15s ease }
        .ins-step__num{ display:grid; place-items:center; flex:none; width:1.7rem; height:1.7rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.66rem; font-weight:700;
            background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.22) }
        .ins-step__label{ font-size:.85rem; font-weight:600; min-width:0 }
        .ins-step__opt{ margin-left:.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.55rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
            color:rgba(255,255,255,.6) }

        .ins-step.is-done{ color:rgba(255,255,255,.9) }
        .ins-step.is-done .ins-step__num{ background:rgba(255,255,255,.25); border-color:transparent }
        .ins-step.is-now{ color:#fff; background:rgba(255,255,255,.16) }
        .ins-step.is-now .ins-step__num{ color:var(--accent); background:#fff; border-color:#fff }

        /* ── Совет ── */
        .ins-tip{ position:relative; padding:.85rem .9rem; background:rgba(255,255,255,.13);
            border:1px solid rgba(255,255,255,.2) }
        .ins-tip__cap{ display:inline-flex; align-items:center; gap:.35rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.58rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
            color:rgba(255,255,255,.8) }
        .ins-tip__text{ margin:.4rem 0 0; font-size:.8rem; line-height:1.5; color:rgba(255,255,255,.94) }

        .ins-aside__foot{ position:relative; margin:0; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
            color:rgba(255,255,255,.75) }
        .ins-aside__bar{ display:block; height:3px; margin-bottom:.5rem; background:rgba(255,255,255,.22) }
        .ins-aside__bar span{ display:block; height:100%; background:#fff; transition:width .3s ease }

        /* ── Колонка шага ── */
        /* overflow-x:hidden обязателен: фактура позиционируется за правым
           краем (right:-10%), и без обрезки колонка получала
           горизонтальную прокрутку. */
        .ins-main{ display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:.75rem; padding:1.5rem 1rem; min-width:0; min-height:0; overflow:hidden }
        @media (min-width:640px){ .ins-main{ padding:2rem 2.5rem } }

        .ins-main{ position:relative; isolation:isolate }
        .ins-main__pattern{ position:absolute; right:-10%; bottom:-14%; width:52%; aspect-ratio:1;
            z-index:-1; color:var(--accent); opacity:.07; pointer-events:none;
            -webkit-mask-image:radial-gradient(120% 120% at 100% 100%, #000 16%, transparent 68%);
            mask-image:radial-gradient(120% 120% at 100% 100%, #000 16%, transparent 68%) }
        .ins-main__pattern svg{ display:block; width:100%; height:100% }

        /* Ширина карточки шага задаётся здесь, а не в каждой из восьми
           вьюх: у admin/database/smtp/license стояло max-w-xl (36rem), и на
           широком экране форма оставалась узкой полоской. */
        .ins-main > div[class*="max-w-"]{ max-width:min(64rem, 100%); max-height:100% }

        .ins-notice{ display:flex; align-items:flex-start; gap:.5rem; width:100%; max-width:48rem;
            padding:.6rem .8rem; font-size:.82rem; color:#92400e;
            background:#fffbeb; border:1px solid #fde68a }
        .ins-notice i{ margin-top:.15rem; color:#b45309 }

        /* ───────────────────────── Подложка ─────────────────────────────────
           Как на странице входа: спокойная сплошная поверхность из набора
           темы плюс одно неподвижное акцентное свечение сверху. Прежде здесь
           было «живое стекло» — два анимированных цветных пятна под
           полупрозрачной карточкой: на шаге, где вводят реквизиты базы,
           фон не должен шевелиться и тянуть на себя внимание. */
        .install-backdrop {
            position: relative;
            isolation: isolate;
            background: var(--color-bg, #f4f5fb);
        }
        /* Свечение убрано из-под карточки: слева теперь цветная колонка,
           и второе пятно справа делало страницу пёстрой. */

        /* ───────────────────────── Общие части шага ─────────────────────────
           Один набор на все семь шагов: шапка полосой, подписи полей,
           поля, заметки и действия. Раньше каждый шаг держал свою копию. */
        .ins-eyebrow{ margin:0 0 .1rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.6rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase;
            color:var(--accent,#6366f1) }
        .ins-title{ margin:0; font-size:1.5rem; font-weight:800; letter-spacing:-.03em;
            line-height:1.05; color:#111827 }

        .ins-head{ display:flex; align-items:center; gap:.85rem; padding:1.1rem 1.5rem;
            border-bottom:1px solid var(--surface-bd,#e3e6ee) }
        .ins-head__badge{ width:2.6rem; height:2.6rem; flex:none }
        .ins-head__about{ margin:.2rem 0 0; font-size:.78rem; line-height:1.45; color:#4b5563 }
        @media (max-width:640px){ .ins-head{ padding:1rem 1.1rem } }

        /* Значок рядом с названием шага (СУБД, режим и т. п.). */
        .ins-tag{ display:inline-flex; align-items:center; gap:.25rem; vertical-align:middle;
            margin-left:.4rem; padding:.1rem .4rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
            color:#fff; background:var(--accent) }

        /* Мягкая метка — для необязательного шага: заливка акцентом здесь
           кричала бы о том, что как раз можно пропустить. */
        .ins-tag--soft{ color:var(--accent); background:color-mix(in srgb, var(--accent) 12%, transparent);
            box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--accent) 32%, transparent) }

        /* Галочка-переключатель внутри шага. */
        .ins-check{ display:flex; align-items:flex-start; gap:.6rem; padding:.6rem .7rem; cursor:pointer;
            background:var(--surface-2,#f7f8fc); border:1px solid var(--surface-bd,#e3e6ee);
            transition:border-color .15s ease }
        .ins-check:hover{ border-color:color-mix(in srgb, var(--accent) 45%, var(--surface-bd,#e3e6ee)) }
        .ins-check input{ margin-top:.15rem }
        .ins-check__title{ font-size:.85rem; font-weight:700; color:#111827 }
        .ins-check__note{ display:flex; align-items:center; gap:.3rem; margin:.15rem 0 0;
            font-size:.72rem; color:#4b5563 }

        .ins-label{ display:flex; align-items:center; gap:.35rem; margin-bottom:.3rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
            color:#4b5563 }
        .ins-label i{ color:var(--accent) }

        .ins-input{ width:100%; padding:.5rem .75rem; font-size:.875rem; color:#111827;
            background:var(--surface,#fff); border:1px solid var(--surface-bd,#d1d5db);
            transition:border-color .15s ease, box-shadow .15s ease }
        .ins-input:focus{ outline:none; border-color:var(--accent);
            box-shadow:0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent) }

        /* Указание, без которого шаг не пройти. */
        .ins-callout{ display:flex; align-items:flex-start; gap:.5rem; margin:.4rem 0 0;
            padding:.5rem .6rem; font-size:.73rem; line-height:1.45; color:#374151;
            background:color-mix(in srgb, var(--accent) 7%, transparent);
            border-left:3px solid var(--accent) }
        .ins-callout i{ margin-top:.1rem; flex:none; color:var(--accent) }
        .ins-callout code, .ins-callout .font-mono{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.72rem }

        /* Ответ на частую заминку шага. */
        .ins-help{ display:block; padding:.65rem .8rem;
            background:var(--surface-2,#f7f8fc); border:1px solid var(--surface-bd,#e3e6ee) }
        .ins-help__cap{ display:inline-flex; align-items:center; gap:.35rem; margin-bottom:.25rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.58rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
            color:#4b5563 }
        .ins-help__cap i{ color:var(--accent) }
        .ins-help__text{ display:block; font-size:.74rem; line-height:1.5; color:#4b5563 }
        .ins-help__text a{ color:var(--accent); text-decoration:underline }

        .ins-foot{ padding:1rem 1.5rem; border-top:1px solid var(--surface-bd,#e3e6ee) }

        /* ── Финальный шаг ── */
        .ins-countdown{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.45rem;
            padding:.5rem .7rem; font-size:.76rem; color:#4b5563;
            background:var(--surface-2,#f7f8fc); border:1px solid var(--surface-bd,#e3e6ee) }
        .ins-countdown i{ color:var(--accent) }
        .ins-countdown b{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; color:#111827 }
        .ins-countdown__stay{ font-weight:700; color:var(--accent); text-decoration:underline; cursor:pointer }

        /* Факты и рекомендации финального шага: две колонки строками. */
        .ins-facts{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.15rem .75rem }
        @media (max-width:640px){ .ins-facts{ grid-template-columns:1fr } }
        .ins-facts--wide{ gap:.25rem .75rem }
        .ins-facts__row{ display:flex; align-items:center; gap:.4rem; min-width:0;
            font-size:.75rem; color:#4b5563 }
        .ins-facts__row i{ flex:none; color:var(--accent) }
        .ins-facts__row .font-mono{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace }

        .ins-warn-list{ display:grid; gap:.2rem; margin:0; padding-left:1.1rem;
            font-size:.75rem; line-height:1.45; color:#4b5563; list-style:disc }

        /* Строка со значком — обычным потоком, а не flex: внутри есть
           разметка (<span class="font-mono">путь</span>), и во flex-контейнере
           она стала бы отдельным элементом со своим зазором — перед
           закрывающей скобкой появлялся лишний пробел. */
        .ins-locked{ margin:0; text-align:center; font-size:.7rem; line-height:1.5; color:#6b7280 }
        .ins-locked i{ display:inline-block; vertical-align:-2px; margin-right:.3rem; color:var(--accent) }
        .ins-locked .font-mono, .ins-locked code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace }

        /* Действия шага: главное — залито акцентом, остальные тихие. */
        .ins-act{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
            padding:.7rem 1.2rem; font-size:.85rem; font-weight:700; cursor:pointer;
            color:#374151; background:var(--surface-2,#f7f8fc);
            border:1px solid var(--surface-bd,#e3e6ee);
            transition:border-color .15s ease, background .15s ease, color .15s ease }
        .ins-act:hover{ border-color:color-mix(in srgb, var(--accent) 55%, var(--surface-bd,#e3e6ee));
            background:#fff; color:#111827 }
        .ins-act--go{ color:#fff; border-color:transparent;
            background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 62%, #8b5cf6)) }
        .ins-act--go:hover{ color:#fff; filter:brightness(1.08);
            background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 62%, #8b5cf6)) }
        .ins-act--dim{ color:#6b7280 }
        /* Обход шага в режиме разработчика: пунктир говорит, что кнопка
           не из обычного пути установки. */
        .ins-act--dev{ width:100%; border-style:dashed; border-color:#9ca3af; color:#374151;
            background:transparent }
        .ins-act--dev:hover{ border-color:var(--accent); color:#111827; background:var(--surface,#fff) }
        .ins-act:disabled{ opacity:.6; cursor:not-allowed }

        /* Возврат на предыдущий шаг — ссылкой, а не кнопкой: это не
           действие шага, а отмена. */
        /* ── Три факта о мастере ──────────────────────────────────────────
           Карточка со значком на акцентной плитке слева и подписями справа:
           раньше это были три одинаковых серых прямоугольника со значком по
           центру, которые взгляд проскакивал не читая. */
        .ins-feats{ display:grid; gap:.5rem; grid-template-columns:1fr }
        @media (min-width:640px){ .ins-feats{ grid-template-columns:repeat(3, minmax(0,1fr)) } }

        .ins-feat{ display:flex; align-items:center; gap:.6rem; padding:.6rem .7rem; min-width:0;
            background:var(--surface,#fff); border:1px solid var(--surface-bd,#e3e6ee);
            border-left:3px solid color-mix(in srgb, var(--accent) 55%, transparent);
            transition:border-color .15s ease, box-shadow .15s ease }
        .ins-feat:hover{ border-left-color:var(--accent);
            box-shadow:0 6px 18px -12px color-mix(in srgb, var(--accent) 60%, transparent) }

        .ins-feat__ico{ display:grid; place-items:center; flex:none; width:1.9rem; height:1.9rem;
            color:var(--accent); background:color-mix(in srgb, var(--accent) 12%, transparent) }

        .ins-feat__title{ display:block; font-size:.76rem; font-weight:700; color:#111827 }
        .ins-feat__sub{ display:block; margin-top:.05rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
            color:#6b7280 }

        /* Вариант с описанием — для отдельной страницы «Возможности»:
           значок сверху, под ним название и два-три предложения. */
        .ins-feats--cards{ grid-template-columns:repeat(auto-fill, minmax(15rem, 1fr)) }
        .ins-feat--card{ position:relative; flex-direction:column; align-items:flex-start;
            gap:.4rem; padding:.8rem .85rem }
        .ins-feat--card .ins-feat__ico{ width:2.1rem; height:2.1rem }
        .ins-feat--card .ins-feat__title{ font-size:.82rem }
        .ins-feat__text{ margin:0; font-size:.72rem; line-height:1.5; color:#6b7280 }
        /* Ключевая возможность: грань в акценте и звёздочка в углу. */
        .ins-feat--key{ border-left-color:var(--accent) }
        .ins-feat__star{ position:absolute; top:.5rem; right:.55rem; color:var(--accent) }

        /* ── Требования ──────────────────────────────────────────────────
           Названия требований — технические идентификаторы, поэтому
           моноширинным. Состояние читается гранью слева, а не бейджем. */
        .ins-reqs{ display:grid; gap:.4rem; grid-template-columns:1fr }
        @media (min-width:640px){ .ins-reqs{ grid-template-columns:repeat(2, minmax(0,1fr)) } }
        .ins-req{ display:flex; align-items:center; gap:.55rem; padding:.5rem .65rem; min-width:0;
            background:var(--surface,#fff); border:1px solid var(--surface-bd,#e3e6ee);
            border-left:3px solid #9ca3af }
        .ins-req.is-ok{ border-left-color:#16a34a }
        .ins-req.is-bad{ border-left-color:#dc2626; background:#fef2f2 }
        .ins-req i{ flex:none }
        .ins-req.is-ok i{ color:#16a34a }
        .ins-req.is-bad i{ color:#dc2626 }
        .ins-req__name{ flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.72rem; font-weight:600; color:#374151 }
        .ins-req__state{ flex:none; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.56rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase }
        .ins-req.is-ok .ins-req__state{ color:#15803d }
        .ins-req.is-bad .ins-req__state{ color:#b91c1c }

        /* Счёт проверок над списком — как сводка над списками в панели. */
        .ins-score{ display:flex; align-items:center; gap:.5rem; margin-bottom:.5rem;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
            color:#4b5563 }
        .ins-score__bar{ flex:1; height:3px; background:var(--surface-bd,#e3e6ee) }
        .ins-score__bar span{ display:block; height:100%; background:#16a34a }
        .ins-score__bar.is-bad span{ background:#dc2626 }

        .ins-back{ display:inline-flex; align-items:center; gap:.4rem; font-size:.82rem;
            font-weight:600; color:#6b7280; transition:color .15s ease }
        .ins-back:hover{ color:var(--accent) }

        /* ───────────────────────── Стеклянная карточка ─────────────────────── */
        .install-card {
            position: relative;
            background: var(--surface, #ffffff) !important;
            border: 1px solid var(--surface-bd, #e3e6ee) !important;
            box-shadow:
                0 1px 2px rgba(17, 24, 39, .04),
                0 18px 44px rgba(17, 24, 39, .10) !important;
        }
        /* Прямые края — тем же рубильником, что у карточки входа
           (body.au .au-card) и в панели (body.admin-sharp): перечислять
           скругления по вьюхам пришлось бы в каждом шаге мастера. */
        .install-card, .install-card * { border-radius: 0 !important }
        /* Тонкая акцентная полоска по верхней кромке карточки. */
        .install-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-top-left-radius: inherit;
            border-top-right-radius: inherit;
            background: linear-gradient(90deg,
                        var(--accent),
                        color-mix(in srgb, var(--accent) 55%, #ffffff),
                        var(--accent));
            z-index: 2;
        }

        /* ───────────────────────── Акцентный бейдж-иконка ──────────────────── */
        /* Градиент акцента + мягкое свечение вокруг. */
        .accent-badge {
            background: linear-gradient(140deg,
                        color-mix(in srgb, var(--accent) 92%, #fff 8%),
                        color-mix(in srgb, var(--accent) 72%, #000 12%)) !important;
            box-shadow:
                0 10px 22px -8px color-mix(in srgb, var(--accent) 65%, transparent),
                inset 0 1px 0 rgba(255,255,255,.4);
        }

        /* ───────────────────────── Подсказки-«сноски» ──────────────────────── */
        /* Сноска — как на странице входа: вторая поверхность темы, тонкая
           линия, акцент только в значке. Прежде это была стеклянная плашка
           с блюром, свечением и жирной акцентной гранью — три подряд такие
           сноски перетягивали внимание с того, что на шаге надо сделать. */
        .hint {
            position: relative;
            background: var(--surface-2, #f7f8fc);
            border: 1px solid var(--surface-bd, #e3e6ee);
            transition: border-color .2s ease, background .2s ease;
        }
        .hint:hover {
            border-color: color-mix(in srgb, var(--accent) 45%, var(--surface-bd, #e3e6ee));
        }
        /* Иконка-акцент: везде — в цвете шага. */
        .hint-ico { color: var(--accent); }
        /* Внутри плашки-сноски — ещё и в квадратном акцентном бейдже. */
        .hint .hint-ico {
            background: transparent;
            padding: 3px;
            box-sizing: content-box;
        }

        /* ───────────────────────── Кнопки ──────────────────────────────────── */
        /* Единый «острый» вид + микровзаимодействие при наведении. */
        .ui-btn {
            transition: transform .15s cubic-bezier(.16,1,.3,1), box-shadow .22s ease,
                        background-color .2s ease, border-color .2s ease;
        }
        .ui-btn:hover { transform: translateY(-2px); }
        .ui-btn:active { transform: translateY(0); }
        /* Основная тёмная кнопка: подсветка тенью цвета шага при наведении. */
        .ui-btn-primary { box-shadow: 0 12px 24px -12px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.14); }
        .ui-btn-primary:hover { box-shadow: 0 18px 34px -12px color-mix(in srgb, var(--accent) 55%, rgba(0,0,0,.45)); }

        /* ───────────────────────── Ровные углы 90° везде ───────────────────── */
        /* По просьбе — единый «острый» стиль без скруглений: карточки, кнопки,
           поля, чипы шагов, бейджи, плашки. Ловим любой элемент со скругляющей
           утилитой Tailwind (rounded-*) и обнуляем радиус. Пятна-ауры на фоне —
           это ::before/::after подложки, они правилом не затрагиваются и
           остаются круглыми. */
        .install-backdrop [class*="rounded"],
        .install-backdrop input,
        .install-backdrop textarea,
        .install-backdrop select {
            border-radius: 0 !important;
        }
        /* Цветная подсветка фокуса берётся из --accent. */
        .install-backdrop input:focus,
        .install-backdrop textarea:focus,
        .install-backdrop select:focus {
            border-color: var(--accent, #111827) !important;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent, #111827) 22%, transparent) !important;
            outline: none;
        }

        /* ───────────────────────── Кастомные тултипы ───────────────────────── */
        /* Элемент с data-tip="…" показывает стильную стеклянную подсказку. */
        [data-tip] { position: relative; }
        [data-tip]::after,
        [data-tip]::before {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 9px);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(4px);
            transition: opacity .16s ease, transform .16s ease, visibility .16s;
            pointer-events: none;
            z-index: 60;
        }
        [data-tip]::after {
            content: attr(data-tip);
            width: max-content;
            max-width: 15rem;
            padding: .4rem .6rem;
            border-radius: 0;
            background: rgba(17, 20, 32, 0.94);
            color: #f8fafc;
            font-size: 11px;
            line-height: 1.35;
            font-weight: 500;
            text-align: center;
            white-space: normal;
            box-shadow: 0 12px 28px -10px rgba(0,0,0,.55);
            border-top: 2px solid var(--accent);
        }
        [data-tip]::before {
            content: "";
            bottom: calc(100% + 3px);
            border: 6px solid transparent;
            border-top-color: rgba(17, 20, 32, 0.94);
        }
        [data-tip]:hover::after,
        [data-tip]:hover::before,
        [data-tip]:focus-visible::after,
        [data-tip]:focus-visible::before {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        /* Тултип снизу элемента: data-tip-pos="bottom" */
        [data-tip][data-tip-pos="bottom"]::after { bottom: auto; top: calc(100% + 9px); }
        [data-tip][data-tip-pos="bottom"]::before { bottom: auto; top: calc(100% + 3px); border-top-color: transparent; border-bottom-color: rgba(17,20,32,.94); }

        /* ───────────────────────── Прочее ──────────────────────────────────── */
        .animate-fade-in { animation: fadeIn .45s cubic-bezier(.16,1,.3,1); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px) scale(.99); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Компактный скроллбар для внутренних прокручиваемых областей карточек.
           overflow-x: hidden здесь обязателен: у элемента стоит overflow-y-auto,
           а по спецификации CSS, если одна ось не visible, вторая из visible
           превращается в auto. Без этой строки карточка получала ещё и
           горизонтальную полосу прокрутки, стоило тексту не влезть по ширине
           хотя бы на пиксель (ловилось на длинных русских и беларуских
           строках). Прокрутка внутри карточки задумана только вертикальной. */
        .install-scroll { overflow-x: hidden; scrollbar-width: thin; scrollbar-color: #cbd0dc transparent; }
        .install-scroll::-webkit-scrollbar { width: 6px; }
        .install-scroll::-webkit-scrollbar-thumb { background: #cbd0dc; border-radius: 999px; }
        .install-scroll::-webkit-scrollbar-track { background: transparent; }

        @media (prefers-reduced-motion: reduce) {
            .install-backdrop::before,
            .install-backdrop::after,
            .animate-fade-in { animation: none !important; }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full text-gray-900 antialiased">

{{--
    Каркас «всё во вьюпорте»: h-screen + flex-центрирование. Каждая страница
    отдаёт карточку с max-h, а длинный контент скроллится ВНУТРИ карточки
    (класс install-scroll), а не всей страницей. Цвет шага (--accent) задаётся
    страницей через @section('accent', '#hex') и живёт здесь, на подложке.
--}}
@php
    // Текущий шаг берём из имени маршрута — так layout знает его, не требуя
    // от каждой вьюхи объявлять секцию. Шаги перечислены здесь же: у
    // партиала свой список для мобильной полосы, но он рисует чипы, а тут
    // нужна вертикальная лестница.
    $insSteps = [
        'welcome'      => ['label' => __('install.steps.welcome'),      'icon' => 'sparkles'],
        'requirements' => ['label' => __('install.steps.requirements'), 'icon' => 'clipboard-check'],
        'database'     => ['label' => __('install.steps.database'),     'icon' => 'database'],
        'admin'        => ['label' => __('install.steps.admin'),        'icon' => 'user-round'],
        'smtp'         => ['label' => __('install.steps.smtp'),         'icon' => 'mail', 'optional' => true],
        'license'      => ['label' => __('install.steps.license'),      'icon' => 'key-round'],
        'finish'       => ['label' => __('install.steps.finish'),       'icon' => 'check-circle-2'],
    ];

    $insRoute = request()->route()?->getName() ?? '';
    $insCurrent = str_starts_with($insRoute, 'install.') ? substr($insRoute, 8) : 'welcome';
    $insKeys = array_keys($insSteps);
    $insIndex = array_search($insCurrent, $insKeys, true);
    $insIndex = $insIndex === false ? 0 : $insIndex;
    $insProgress = (int) round($insIndex / (count($insKeys) - 1) * 100);
@endphp

<div class="ins-shell" style="--accent: @yield('accent', '#6366f1')">

    {{-- ══ Левая колонка: проект, лестница шагов, совет ══
         Показывается с 1024px. На узких экранах её заменяет полоса чипов
         внутри карточки шага (Install::partials.steps). --}}
    <aside class="ins-aside">
        <span class="ins-aside__glow" aria-hidden="true"></span>

        <div class="ins-brand">
            <span class="ins-brand__badge"><i data-lucide="layers" class="w-6 h-6"></i></span>
            <span class="ins-brand__text">
                <span class="ins-brand__eyebrow">{{ __('install.welcome.suffix') }}</span>
                <span class="ins-brand__name">Ru&nbsp;CMS</span>
            </span>
        </div>

        {{-- Лестница шагов: пройденные с галочкой, текущий подсвечен,
             будущие приглушены. Номера моноширинными — по ним взгляд
             находит место в процессе, не читая подписи. --}}
        <ol class="ins-steps">
            @foreach($insSteps as $key => $step)
                @php
                    $i = array_search($key, $insKeys, true);
                    $state = $i < $insIndex ? 'is-done' : ($i === $insIndex ? 'is-now' : '');
                @endphp
                <li class="ins-step {{ $state }}">
                    <span class="ins-step__num">
                        @if($i < $insIndex)
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        @else
                            {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </span>
                    <span class="ins-step__label">
                        {{ $step['label'] }}
                        @if($step['optional'] ?? false)
                            <span class="ins-step__opt">{{ __('install.steps.optional') }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ol>

        {{-- Совет к текущему шагу: то, что стоит знать ДО того, как
             заполнишь форму и упрёшься в ошибку. --}}
        <div class="ins-tip">
            <span class="ins-tip__cap"><i data-lucide="lightbulb" class="w-3.5 h-3.5"></i> {{ __('install.tips.cap') }}</span>
            <p class="ins-tip__text">{{ __('install.tips.' . $insCurrent) }}</p>
        </div>

        <p class="ins-aside__foot">
            <span class="ins-aside__bar"><span style="width: {{ $insProgress }}%"></span></span>
            {{ __('install.steps.step') }} {{ $insIndex + 1 }} {{ __('install.steps.of') }} {{ count($insKeys) }} · {{ $insProgress }}%
        </p>
    </aside>

    {{-- ══ Правая колонка: сам шаг ══
         Фактура — тот же приём, что в колонке страницы входа: настоящий
         QR-код адреса сайта, увеличенный так, что в кадр попадает только
         угол. Рисуется своим генератором и кешируется, внешних запросов
         нет. --}}
    <main class="ins-main install-backdrop">
        <span class="ins-main__pattern" aria-hidden="true">{!! auth_pattern_svg() !!}</span>

        @if (session('install_notice'))
            <div class="ins-notice">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('install_notice') }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>

@stack('scripts')

</body>
</html>
