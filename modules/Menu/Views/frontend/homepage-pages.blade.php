{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  📄 БЛОК «ПОЛЕЗНОЕ»                                              ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Страницы, вынесенные на главную: о проекте, условия, помощь.     ║
    ║  Показываем карточки с выдержкой, а не полный текст — иначе       ║
    ║  несколько страниц растянули бы главную на пару экранов.          ║
    ║                                                                  ║
    ║  ЧТО СЮДА ПОПАДАЕТ                                               ║
    ║    Панель → Страницы → материал → галочка «Показать на главной».  ║
    ║    Порядок задаётся полем homepage_order.                         ║
    ║                                                                  ║
    ║  ИКОНКА КАРТОЧКИ                                                 ║
    ║    Первый эмодзи в заголовке страницы: «❓ Частые вопросы» → в    ║
    ║    карточке появится значок, а в заголовке останется чистый       ║
    ║    текст. Нет эмодзи — рисуется общий значок документа.           ║
    ║                                                                  ║
    ║  ЦВЕТА                                                           ║
    ║    Из активной темы (--color-primary/--color-accent), поэтому     ║
    ║    блок одинаково читается на любом оформлении.                   ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}
@php use Illuminate\Support\Str; @endphp

@php
    // Иконка — ведущий эмодзи заголовка. Тот же приём, что в «Клинике» и
    // «Играх»: редактор задаёт значок, не трогая вёрстку.
    $splitIcon = function ($title) {
        $title = trim((string) $title);

        if (preg_match('~^(\X)\s+(.+)$~u', $title, $m) && ! preg_match('~^[\p{L}\p{N}]~u', $m[1])) {
            return ['icon' => $m[1], 'text' => $m[2]];
        }

        return ['icon' => null, 'text' => $title];
    };
@endphp

<section class="pg">
    <div class="pg__head">
        <span class="fx-badge"><i class="fas fa-file-lines"></i></span>
        <div>
            <h2 class="pg__title">{{ __('frontend.pages.title') }}</h2>
            <p class="pg__sub">{{ __('frontend.pages.subtitle') }}</p>
        </div>
    </div>

    <div class="pg-grid">
        @foreach ($pages as $page)
            @php
                $parts = $splitIcon($page->t('title'));
                $excerpt = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $page->content))), 165);
                $url = !empty($page->slug) ? route('frontend.pages.show', $page->slug) : null;
            @endphp

            <article class="pg-card">
                <span class="pg-card__ico">
                    @if ($parts['icon'])
                        {{ $parts['icon'] }}
                    @else
                        <i class="fas fa-file-lines"></i>
                    @endif
                </span>

                <h3 class="pg-card__title">
                    @if ($url)
                        <a href="{{ $url }}">{{ $parts['text'] }}</a>
                    @else
                        {{ $parts['text'] }}
                    @endif
                </h3>

                @if ($page->categories->isNotEmpty())
                    <div class="pg-card__cats">
                        {{-- Не больше двух: при трёх и более чипы занимали
                             половину карточки и вытесняли текст. --}}
                        @foreach ($page->categories->take(2) as $category)
                            <a href="{{ url('/?category=' . $category->id) }}" class="pg-chip">
                                {{ $category->t('title') }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($excerpt !== '')
                    <p class="pg-card__text">{{ $excerpt }}</p>
                @endif

                {{-- Нижняя полоса: дата обновления слева, ссылка справа.
                     Раньше ссылка висела в потоке текста и сливалась с
                     анонсом, а даты не было вовсе. --}}
                <span class="pg-card__meta">
                    <span class="pg-meta__date">{{ $page->updated_at?->format('d.m.Y') }}</span>

                    @if ($url)
                        <a href="{{ $url }}" class="pg-card__more">
                            {{ __('frontend.pages.more') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </span>
            </article>
        @endforeach
    </div>
</section>

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни line-clamp,
       ни произвольных значений, ни прозрачности через /NN. */
    .pg{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .pg__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:var(--surface,#fff); border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .pg__title{ margin:0; font-size:1.5rem; font-weight:700; color:var(--surface-ink,#111827); line-height:1.2 }
    .pg__sub{ margin:.1rem 0 0; font-size:.82rem; color:var(--surface-mute,#6b7280) }

    .pg-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(18rem,1fr)); gap:1rem }

    .pg-card{ display:flex; flex-direction:column; gap:.55rem; padding:1.3rem 1.4rem;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7); transition:border-color .15s, transform .15s }
    .pg-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }

    .pg-card__ico{ display:flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem;
        font-size:1.2rem; color:var(--on-accent,#fff); flex:none;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    /* Обрезка строками настоящим CSS: класса line-clamp в сборке нет, и
       без этого длинный заголовок ломал высоту карточек в ряду. */
    .pg-card__title{ margin:0; font-size:1.05rem; line-height:1.35; font-weight:700 }
    .pg-card__title a{ color:var(--surface-ink,#111827); display:-webkit-box; -webkit-line-clamp:2;
        -webkit-box-orient:vertical; overflow:hidden }
    .pg-card__title a:hover{ color:var(--color-primary,#6366f1) }

    .pg-card__cats{ display:flex; flex-wrap:wrap; gap:.3rem }
    .pg-chip{ font-size:.68rem; font-weight:700; padding:.12rem .45rem; color:var(--color-primary, #4f46e5);
        background:color-mix(in srgb, var(--color-primary,#6366f1) 12%, var(--surface,#eef2ff)); border:1px solid color-mix(in srgb, var(--color-primary,#6366f1) 26%, var(--surface,#e0e7ff)) }

    .pg-card__text{ margin:0; font-size:.85rem; line-height:1.55; color:var(--surface-mute,#64748b); flex:1;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }

    /* Нижняя полоса карточки — как в «Новостях», «Журнале» и «Играх»:
       линия сверху, лёгкая подложка, края разведены. */
    .pg-card__meta{ display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        margin:.65rem -1.4rem -1.3rem; padding:.55rem 1.4rem; font-size:.75rem;
        border-top:1px solid #eef2f7; background:var(--surface-2,#f8fafc) }
    .pg-meta__date{ color:var(--surface-dim,#94a3b8); font-variant-numeric:tabular-nums }
    .pg-meta__date::before{ content:'🗓'; margin-right:.35rem; opacity:.75 }
    .pg-card__more{ font-size:.8rem; font-weight:700; color:var(--color-primary,#6366f1);
        white-space:nowrap }


    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы ниже.
       Значения берутся из общих переменных поверхностей, объявленных
       в макете: один набор на все шаблоны. */
    body.fx-theme-dark .pg__head,
    body.fx-theme-dark .pg-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .pg-card__meta{ background:var(--surface-2); border-color:var(--surface-bd) }
    body.fx-theme-dark .pg__title, body.fx-theme-dark .pg-card__title a{ color:var(--surface-ink) }
    @media (prefers-color-scheme: dark){
        .pg__head, .pg-card{ background:#111827; border-color:#1f2937 }
        .pg-card__meta{ background:#0b1220; border-color:#1f2937 }
        .pg__title, .pg-card__title a{ color:#f3f4f6 }
        .pg-chip{ background:#1e1b4b; border-color:#312e81; color:#c7d2fe }
    }
</style>
@endpush
