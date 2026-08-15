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

    $excerptOf = function ($item, int $limit = 150) {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags((string) $item->content)));

        return \Illuminate\Support\Str::limit($text, $limit);
    };

    $items = $newsList ?? collect();
@endphp

@if ($items->count())
<section class="clinic">
    <div class="clinic__head">
        <span class="clinic__badge">🏥</span>
        <div>
            <h2 class="clinic__title">{{ $title ?? 'Услуги и направления' }}</h2>
            <p class="clinic__sub">Приём по записи · Консультация перед лечением</p>
        </div>
    </div>

    <div class="clinic-grid">
        @foreach ($items as $item)
            @php $parts = $splitIcon($item->title); @endphp

            <article class="clinic-card">
                <div class="clinic-card__top">
                    <span class="clinic-card__icon">{{ $parts['icon'] }}</span>
                    <span class="clinic-card__date">{{ $item->created_at?->format('d.m.Y') }}</span>
                </div>

                <h3 class="clinic-card__title">
                    <a href="{{ url('/news/' . $item->slug) }}">{{ $parts['text'] }}</a>
                </h3>

                <p class="clinic-card__text">{{ $excerptOf($item) }}</p>

                <div class="clinic-card__foot">
                    @if (!is_null($item->price) && $item->price > 0)
                        <span class="clinic-card__price">
                            от {{ number_format((float) $item->price, 0, ',', ' ') }} ₽
                        </span>
                    @endif

                    <a href="{{ url('/news/' . $item->slug) }}" class="clinic-card__link">Подробнее →</a>
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

    .clinic-card{ display:flex; flex-direction:column; gap:.6rem; padding:1.4rem 1.5rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7); transition:border-color .15s, transform .15s }
    .clinic-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }
    /* Значок и дата в одной строке: низ карточки занят ценой и ссылкой,
       поэтому дата ушла в правый верхний угол. */
    .clinic-card__top{ display:flex; align-items:center; justify-content:space-between; gap:.75rem }
    .clinic-card__icon{ font-size:1.9rem; line-height:1 }
    .clinic-card__date{ font-size:.75rem; color:var(--surface-dim,#94a3b8); font-variant-numeric:tabular-nums;
        white-space:nowrap }
    .clinic-card__date::before{ content:'🗓'; margin-right:.3rem; opacity:.75 }
    /* Шрифт крупнее обычного: сайт читают в том числе пожилые пациенты. */
    .clinic-card__title{ margin:0; font-size:1.15rem; line-height:1.35; font-weight:700 }
    .clinic-card__title a{ color:var(--surface-ink,#111827); text-decoration:none }
    .clinic-card__title a:hover{ color:var(--color-primary,#6366f1) }
    .clinic-card__text{ margin:0; font-size:.95rem; line-height:1.6; color:var(--surface-ink,#475569); flex:1 }
    .clinic-card__foot{ display:flex; align-items:center; justify-content:space-between;
        gap:1rem; flex-wrap:wrap; padding-top:.7rem; border-top:1px solid #f1f5f9 }
    .clinic-card__price{ font-size:1.05rem; font-weight:700; color:var(--surface-ink,#111827) }
    .clinic-card__link{ font-size:.9rem; font-weight:700; color:var(--color-primary,#6366f1) }



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
