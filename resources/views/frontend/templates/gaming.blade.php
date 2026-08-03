{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🎮 ШАБЛОН «ИГРЫ»                                                ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Для игровых сообществ: анонсы, обзоры, патчноуты, турниры.       ║
    ║  Карточки тёмные поверх светлого фона — привычный игровым         ║
    ║  сайтам вид, но при этом шаблон живёт на любой теме проекта.      ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = gaming           ║
    ║                                                                  ║
    ║  КАК ЗАДАТЬ МЕТКУ И ОЦЕНКУ                                       ║
    ║    Метка — первый эмодзи заголовка: «🔥 Патч 1.4» → значок огня.  ║
    ║    Оценка — поле «Цена» в форме материала: число от 1 до 10       ║
    ║    показывается плашкой в углу обложки. Пусто — плашки нет.       ║
    ║                                                                  ║
    ║  ОБЛОЖКА                                                         ║
    ║    Первый тег <img> в тексте. Нет картинки — блок заливается      ║
    ║    градиентом темы, поэтому дыр в сетке не будет.                 ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    $splitIcon = function ($title) {
        $title = trim((string) $title);

        if (preg_match('~^(\X)\s+(.+)$~u', $title, $m) && ! preg_match('~^[\p{L}\p{N}]~u', $m[1])) {
            return ['icon' => $m[1], 'text' => $m[2]];
        }

        return ['icon' => '🎮', 'text' => $title];
    };

    $coverOf = function ($item) {
        return preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', (string) $item->content, $m)
            ? $m[1]
            : null;
    };

    $excerptOf = function ($item, int $limit = 130) {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags((string) $item->content)));

        return \Illuminate\Support\Str::limit($text, $limit);
    };

    $items = $newsList ?? collect();
@endphp

@if ($items->count())
<section class="gm">
    <div class="gm__head">
        <span class="gm__badge">🎮</span>
        <div>
            <h2 class="gm__title">{{ $title ?? 'Игры и обновления' }}</h2>
            <p class="gm__sub">Анонсы, обзоры и патчноуты</p>
        </div>
    </div>

    <div class="gm-grid">
        @foreach ($items as $item)
            @php
                $parts = $splitIcon($item->title);
                $cover = $coverOf($item);
                // Оценка живёт в поле «Цена»: отдельной колонки под неё нет,
                // а заводить миграцию ради одного шаблона незачем.
                $score = (!is_null($item->price) && $item->price > 0 && $item->price <= 10)
                    ? rtrim(rtrim(number_format((float) $item->price, 1, '.', ''), '0'), '.')
                    : null;
            @endphp

            <a href="{{ url('/news/' . $item->slug) }}" class="gm-card">
                <div class="gm-card__media">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="{{ $parts['text'] }}" loading="lazy">
                    @endif

                    <span class="gm-card__tag">{{ $parts['icon'] }}</span>

                    @if ($score)
                        <span class="gm-card__score">{{ $score }}</span>
                    @endif
                </div>

                <div class="gm-card__body">
                    <h3 class="gm-card__title">{{ $parts['text'] }}</h3>
                    <p class="gm-card__text">{{ $excerptOf($item) }}</p>
                    <span class="gm-card__meta">🗓 {{ $item->created_at?->format('d.m.Y') }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Акцент — из активной темы. */
    .gm{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .gm__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:#fff; border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .gm__badge{ display:flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        flex:none; font-size:1.2rem; background:var(--color-primary,#6366f1) }
    .gm__title{ margin:0; font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2 }
    .gm__sub{ margin:.1rem 0 0; font-size:.82rem; color:#6b7280 }

    .gm-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(16rem,1fr)); gap:1rem }

    /* Карточка тёмная независимо от темы: так выглядят игровые витрины.
       Читаемость обеспечена собственным светлым текстом на своём фоне,
       а не наследованием цветов страницы. */
    .gm-card{ display:flex; flex-direction:column; background:#0f172a; border:1px solid #1e293b;
        overflow:hidden; transition:border-color .15s, transform .15s }
    .gm-card:hover{ border-color:var(--color-accent,#8b5cf6); transform:translateY(-3px) }

    .gm-card__media{ position:relative; height:10rem;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .gm-card__media img{ width:100%; height:100%; object-fit:cover; display:block }

    .gm-card__tag{ position:absolute; top:.6rem; left:.6rem; display:flex; align-items:center;
        justify-content:center; width:2rem; height:2rem; font-size:1rem; background:#0f172a }
    .gm-card__score{ position:absolute; bottom:.6rem; right:.6rem; padding:.15rem .5rem;
        font-size:.85rem; font-weight:800; color:#0f172a; background:#facc15 }

    .gm-card__body{ padding:.9rem 1rem 1.05rem; display:flex; flex-direction:column; gap:.35rem; flex:1 }
    .gm-card__title{ margin:0; font-size:1rem; line-height:1.3; font-weight:700; color:#f8fafc }
    .gm-card__text{ margin:0; font-size:.82rem; line-height:1.5; color:#94a3b8; flex:1 }
    .gm-card__meta{ font-size:.74rem; color:#64748b }

    @media (prefers-color-scheme: dark){
        .gm__head{ background:#111827; border-color:#1f2937 }
        .gm__title{ color:#f3f4f6 }
    }
</style>
@endpush
