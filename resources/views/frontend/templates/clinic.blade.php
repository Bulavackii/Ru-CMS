{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🏥 ШАБЛОН «КЛИНИКА»                                             ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Для медицинских сайтов: услуги, направления, специалисты.        ║
    ║  Карточка крупная, текст крупнее обычного — аудитория таких       ║
    ║  сайтов часто старше, и мелкий шрифт ей мешает.                   ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = clinic           ║
    ║                                                                  ║
    ║  КАК ЗАДАТЬ ЦЕНУ И ИКОНКУ                                        ║
    ║    Цена — поле «Цена» в форме материала. Пусто — блок цены        ║
    ║    просто не показывается.                                        ║
    ║    Иконка — первый эмодзи в заголовке материала. Например:        ║
    ║    «🦷 Лечение кариеса» → в карточке появится значок зуба.        ║
    ║                                                                  ║
    ║  ДОСТУПНОСТЬ                                                     ║
    ║    Включите модуль «Спецвозможности» в панели: посетитель сможет  ║
    ║    увеличить шрифт и включить контрастную тему.                   ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Иконка — ведущий эмодзи заголовка. Так редактор задаёт значок, не
    // трогая вёрстку: просто ставит символ в начало названия услуги.
    $splitIcon = function ($title) {
        $title = trim((string) $title);

        if (preg_match('~^(\X)\s+(.+)$~u', $title, $m) && ! preg_match('~^[\p{L}\p{N}]~u', $m[1])) {
            return ['icon' => $m[1], 'text' => $m[2]];
        }

        return ['icon' => '🩺', 'text' => $title];
    };

    // Через content_excerpt: strip_tags склеивал конец абзаца с началом
    // следующего (см. хелпер).
    $excerptOf = fn ($item, int $limit = 150) => content_excerpt($item->content, $limit);

    $items = $newsList ?? collect();
@endphp

@if ($items->count())
<section class="clinic">
    <div class="clinic__head">
        <span class="clinic__badge">🏥</span>
        <div>
            {{-- ⚠️ Подписи были ЗАШИТЫ по-русски и шли мимо словаря: на
                 английской локали раздел оставался русским. --}}
            <h2 class="clinic__title">{{ $title ?? __('frontend.clinic.title') }}</h2>
            <p class="clinic__sub">{{ __('frontend.clinic.subtitle') }}</p>
        </div>
    </div>

    <div class="clinic-grid">
        @foreach ($items as $item)
            @php $parts = $splitIcon($item->title); @endphp

            <article class="clinic-card">
                {{-- ⚠️ Даты публикации здесь больше нет. Услуга — не новость:
                     когда её описание завели в панель, пациенту всё равно, а
                     стояла она на самом видном месте, рядом со значком. Место
                     отдано названию услуги. --}}
                <div class="clinic-card__top">
                    <span class="clinic-card__icon" aria-hidden="true">{{ $parts['icon'] }}</span>
                </div>

                <h3 class="clinic-card__title">
                    <a href="{{ url('/news/' . $item->slug) }}">{{ $parts['text'] }}</a>
                </h3>

                <p class="clinic-card__text">{{ $excerptOf($item) }}</p>

                {{-- Цена и действие. «Подробнее» заменено на «Записаться»:
                     раздел существует ради записи на приём, а текстовая ссылка
                     не читается как действие. Ведёт на страницу услуги — там
                     полное описание и плашка записи. --}}
                <div class="clinic-card__foot">
                    @if (!is_null($item->price) && $item->price > 0)
                        <span class="clinic-card__price">
                            {{-- Пробел настоящий, а не только отступ в стилях: иначе цена
                                 копируется и читается диктором как «от2 000 ₽». --}}
                            <span class="clinic-card__from">{{ __('frontend.clinic.from') }}</span> {{ number_format((float) $item->price, 0, ',', ' ') }} ₽
                        </span>
                    @endif

                    <a href="{{ url('/news/' . $item->slug) }}" class="clinic-card__book">
                        {{ __('frontend.clinic.book') }}
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    {{-- ⚠️ Полосы «Записаться на приём» здесь больше нет — удалена по
         просьбе владельца 15.08.2026.

         Она была ЗАШИТА в шаблон: заголовок, подпись и адрес ссылки лежали
         литералами прямо здесь, в админке не правились и даже не были
         переведены (единственные русские строки в файле мимо словаря).
         Владелец такой блок держать не хочет: либо содержимое правится в
         редакторе, либо его нет.

         Понадобится снова — заводить не литералом, а блоком содержимого
         (`pc-cta` из content-blocks.css): он вставляется в материал кнопкой
         «Блоки» и правится там же, где остальной текст. --}}
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Цвета — из активной темы. */
    .clinic{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .clinic__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:var(--surface,#fff); border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .clinic__badge{ display:flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        flex:none; font-size:1.2rem; background:var(--color-primary,#6366f1) }
    .clinic__title{ margin:0; font-size:1.5rem; font-weight:700; color:var(--surface-ink,#111827); line-height:1.2 }
    .clinic__sub{ margin:.1rem 0 0; font-size:.85rem; color:var(--surface-mute,#6b7280) }

    .clinic-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(19rem,1fr)); gap:1rem }

    /* Полоса цвета темы сверху: набор карточек читается как перечень услуг,
       а не как лента заметок. Одна деталь, которая отличает медицинский
       раздел от новостного при том же строении карточки. */
    .clinic-card{ position:relative; display:flex; flex-direction:column; gap:.6rem;
        padding:1.4rem 1.5rem; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#eef2f7);
        border-top:3px solid color-mix(in srgb, var(--color-primary,#6366f1) 70%, var(--surface-bd,#eef2f7));
        transition:border-color .18s ease, transform .18s ease, box-shadow .18s ease }
    .clinic-card:hover{ border-top-color:var(--color-primary,#6366f1);
        transform:translateY(-3px);
        box-shadow:0 18px 40px -26px color-mix(in srgb, var(--color-primary,#6366f1) 60%, rgba(15,23,42,.5)) }
    .clinic-card :focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    /* Значок услуги — плашкой в цвете темы: эмодзи «в воздухе» выглядел
       случайным, а рядом с ним стояла дата, которой здесь не место. */
    .clinic-card__top{ display:flex; align-items:center; gap:.75rem }
    .clinic-card__icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2.6rem; height:2.6rem; flex:none; font-size:1.4rem; line-height:1;
        background:color-mix(in srgb, var(--color-primary,#6366f1) 12%, var(--surface,#eef2ff));
        border:1px solid color-mix(in srgb, var(--color-primary,#6366f1) 22%, var(--surface-bd,#e0e7ff)) }
    /* Шрифт крупнее обычного: сайт читают в том числе пожилые пациенты. */
    .clinic-card__title{ margin:0; font-size:1.15rem; line-height:1.35; font-weight:700 }
    .clinic-card__title a{ color:var(--surface-ink,#111827); text-decoration:none }
    .clinic-card__title a:hover{ color:var(--color-primary,#6366f1) }
    .clinic-card__text{ margin:0; font-size:.95rem; line-height:1.6; color:var(--surface-ink,#475569); flex:1 }
    .clinic-card__foot{ display:flex; align-items:center; justify-content:space-between;
        gap:1rem; flex-wrap:wrap; padding-top:.7rem; border-top:1px solid #f1f5f9 }
    /* Цена: «от» тихой приставкой, число крупным и табличными цифрами —
       в перечне услуг колонка цен читается сверху вниз. */
    .clinic-card__price{ font-size:1.2rem; font-weight:800; letter-spacing:-.015em;
        font-variant-numeric:tabular-nums; color:var(--surface-ink,#111827); white-space:nowrap }
    .clinic-card__from{ margin-right:.3rem; font-size:.75rem; font-weight:600;
        color:var(--surface-mute,#64748b) }

    /* «Записаться» — настоящая кнопка, а не текстовая ссылка: раздел
       существует ради записи, а ссылка «Подробнее →» не читалась как
       действие и терялась рядом с ценой. */
    .clinic-card__book{ display:inline-flex; align-items:center; justify-content:center;
        box-sizing:border-box; min-height:38px; padding:0 1.1rem;
        font-size:.88rem; font-weight:700; white-space:nowrap;
        color:var(--on-accent,#fff); text-decoration:none;
        background:linear-gradient(135deg, var(--color-primary,#6366f1), var(--color-accent,#8b5cf6));
        transition:filter .15s ease }
    .clinic-card__book:hover{ filter:brightness(1.08); color:#fff }

    /* Телефоны и планшеты: 44 — нижняя граница зоны нажатия. */
    @media (max-width: 1024px), (max-height: 500px){
        .clinic-grid{ grid-template-columns:repeat(auto-fill,minmax(min(100%,17rem),1fr)); gap:.75rem }
        .clinic-card{ padding:1.1rem 1.15rem }
        .clinic-card__book{ min-height:44px; flex:1 1 auto }
        .clinic-card__foot{ gap:.6rem }
    }



    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы ниже.
       Значения берутся из общих переменных поверхностей, объявленных
       в макете: один набор на все шаблоны. */
    body.fx-theme-dark .clinic__head,
    body.fx-theme-dark .clinic-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .clinic-card__meta{ background:var(--surface-2); border-color:var(--surface-bd) }
    body.fx-theme-dark .clinic__title, body.fx-theme-dark .clinic-card__title a, body.fx-theme-dark .clinic-card__price{ color:var(--surface-ink) }
    @media (prefers-color-scheme: dark){
        .clinic__head, .clinic-card{ background:#111827; border-color:#1f2937 }
        .clinic__title, .clinic-card__title a, .clinic-card__price{ color:#f3f4f6 }
        .clinic-card__text{ color:#cbd5e1 }
        .clinic-card__foot{ border-color:#1f2937 }
    }
</style>
@endpush
