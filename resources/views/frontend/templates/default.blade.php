{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  📰 ШАБЛОН «НОВОСТИ»                                             ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Основной шаблон ленты: обложка, категории, дата, время чтения    ║
    ║  и краткое содержание. Подходит почти любому материалу.           ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = default          ║
    ║                                                                  ║
    ║  ОБЛОЖКА                                                         ║
    ║    Берётся из первого <img> или <video> в тексте материала.       ║
    ║    Видео проигрывается прямо в карточке. Нет медиа — рисуется     ║
    ║    заглушка, поэтому дыр в сетке не будет.                        ║
    ║                                                                  ║
    ║  ВРЕМЯ ЧТЕНИЯ                                                    ║
    ║    Считается по количеству слов (150 слов в минуту) хелпером      ║
    ║    reading_time(). Отдельно задавать не нужно.                    ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

<section class="nw">
    {{-- ── Заголовок раздела ── --}}
    <div class="nw__head">
        <span class="fx-badge"><i class="fas fa-newspaper"></i></span>
        <div>
            <h2 class="nw__title">{{ $title ?? __('frontend.news.title') }}</h2>
            <p class="nw__sub">{{ __('frontend.news.latest') }}</p>
        </div>
    </div>

    @if ($newsList->count())
        {{-- Сетка, а не flex-wrap: карточки одной ширины и ровными рядами.
             При переносе flex последняя строка «прилипала» к центру, а
             карточки в ней растягивались по-разному. --}}
        <div class="nw-grid">
            @foreach ($newsList as $news)
                @php
                    // ==== утилиты ====
                    $IMG_EXT = ['jpg','jpeg','png','gif','webp','bmp','svg','avif'];
                    $VID_EXT = ['mp4','webm','ogg','ogv','mov','m4v','mkv','avi','3gp','3g2'];

                    $extOf = function (?string $url): string {
                        if (!$url) return '';
                        $path = parse_url($url, PHP_URL_PATH) ?? '';
                        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    };

                    // cover абсолютным URL (для poster)
                    $coverAbs = null;
                    if (!empty($news->cover)) {
                        $raw = (string) $news->cover;
                        $isHttp = (bool) preg_match('~^https?://~i', $raw);
                        $rel    = ltrim(preg_replace('~^storage/~','',$raw),'/');
                        $exists = $isHttp ? true : \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
                        if ($exists) $coverAbs = $isHttp ? $raw : asset('storage/'.$rel);
                    }

                    // достаём видео из контента
                    $videoSrc = null;

                    // <video src="...">
                    if (!$videoSrc && preg_match('~<video[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                        $videoSrc = $m[1];
                    }
                    // <source src="..."> (берём первый видеотип или по расширению)
                    if (!$videoSrc && preg_match_all('~<source[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>~i', $news->content, $mm)) {
                        foreach ($mm[0] as $i => $full) {
                            $src = $mm[1][$i] ?? null;
                            if (!$src) continue;
                            $type = null;
                            if (preg_match('~\btype\s*=\s*[\'"]([^\'">]+)[\'"]~i', $full, $tt)) {
                                $type = strtolower($tt[1] ?? '');
                            }
                            if ($type ? str_starts_with($type, 'video/') : in_array($extOf($src), $VID_EXT, true)) {
                                $videoSrc = $src; break;
                            }
                        }
                    }
                    // прямая ссылка на видео в тексте
                    if (!$videoSrc && preg_match('~https?://[^\s"\']+\.(mp4|webm|ogg|ogv|mov|m4v|mkv|avi|3gp|3g2)(\?.*)?~i', $news->content, $m)) {
                        $videoSrc = $m[0];
                    }
                    // если cover — видео и в контенте не нашли, берём его
                    if (!$videoSrc && $coverAbs && in_array($extOf($coverAbs), $VID_EXT, true)) {
                        $videoSrc = $coverAbs;
                    }

                    // картинка (или заглушка)
                    $imageSrc = null;
                    if ($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) {
                        $imageSrc = $coverAbs;
                    } elseif (preg_match('~<img[^>]+src=[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                        $imageSrc = $m[1];
                    } else {
                        $imageSrc = null; // нет картинки → покажем стеклянную заглушку .fx-noimg
                    }

                    $isVideo = (bool) $videoSrc;

                    // MIME для <source>
                    $mimeMap = [
                        'mp4'=>'video/mp4','m4v'=>'video/mp4',
                        'webm'=>'video/webm',
                        'ogg'=>'video/ogg','ogv'=>'video/ogg',
                        'mov'=>'video/quicktime',
                        'mkv'=>'video/x-matroska',
                        'avi'=>'video/x-msvideo',
                        '3gp'=>'video/3gpp','3g2'=>'video/3gpp2',
                    ];
                    $vExt  = $extOf($videoSrc);
                    $vMime = $mimeMap[$vExt] ?? 'video/mp4';
                @endphp
                <article class="nw-card">
                    {{-- ── Обложка ── --}}
                    <div class="nw-card__media">
                        @if ($isVideo)
                            <video muted autoplay loop playsinline controls
                                   @if($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                {{ __('frontend.news.no_video') }}
                            </video>
                        @elseif ($imageSrc)
                            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" loading="lazy">
                        @else
                            <div class="nw-card__noimg">
                                <i class="fas fa-image"></i>
                                <span>{{ __('frontend.news.no_image') }}</span>
                            </div>
                        @endif

                        @if ($news->categories->count())
                            <div class="nw-card__cats">
                                @foreach ($news->categories->take(2) as $category)
                                    <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                       class="nw-chip">{{ $category->title }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ── Текст ── --}}
                    <div class="nw-card__body">
                        <h3 class="nw-card__title">
                            <a href="{{ route('news.show', $news->slug) }}">{{ $news->title }}</a>
                        </h3>

                        <p class="nw-card__text">{{ content_excerpt($news->content, 180) }}</p>

                        <a href="{{ route('news.show', $news->slug) }}" class="nw-card__more">
                            {{ __('frontend.news.read_more') }} <i class="fas fa-arrow-right"></i>
                        </a>

                        {{-- Дата и время чтения разведены по краям и отделены
                             линией: посреди карточки они спорили с текстом. --}}
                        <p class="nw-card__meta">
                            <span class="nw-meta__date">{{ $news->created_at->format('d.m.Y') }}</span>
                            <span class="nw-meta__time">{{ __('frontend.news.reading_time', ['min' => reading_time($news->content)]) }}</span>
                        </p>

                    </div>
                </article>
            @endforeach
        </div>

        {{-- Шаблон принимают и пагинатором, и обычной коллекцией: на
             странице /news материалы разбиты по шаблонам, а пагинация
             там одна на всю страницу и рисуется отдельно. --}}
        @if (method_exists($newsList, 'hasPages') && $newsList->hasPages())
            <div class="nw-pager">{{ $newsList->links() }}</div>
        @endif
    @else
        <div class="nw-empty">
            <i class="fas fa-newspaper"></i>
            <p>{{ __('frontend.news.empty') }}</p>
        </div>
    @endif
</section>

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни line-clamp,
       ни произвольных значений, ни прозрачности через /NN. Цвета — из
       активной темы, поэтому шаблон читается на любом оформлении. */
    .nw{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .nw__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:var(--surface,#fff); border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .nw__title{ margin:0; font-size:1.5rem; font-weight:700; color:var(--surface-ink,#111827); line-height:1.2 }
    .nw__sub{ margin:.1rem 0 0; font-size:.82rem; color:var(--surface-mute,#6b7280) }

    .nw-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%, 18rem), 1fr)); gap:1rem }

    .nw-card{ display:flex; flex-direction:column; background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        overflow:hidden; transition:border-color .15s, transform .15s }
    .nw-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }

    .nw-card__media{ position:relative; height:11rem; background:var(--surface-2,#f1f5f9) }
    .nw-card__media img, .nw-card__media video{ width:100%; height:100%; object-fit:cover; display:block }
    .nw-card__noimg{ display:flex; flex-direction:column; align-items:center; justify-content:center;
        gap:.4rem; height:100%; color:var(--surface-dim,#94a3b8); font-size:.75rem }
    .nw-card__noimg i{ font-size:1.6rem; opacity:.5 }

    .nw-card__cats{ position:absolute; top:.6rem; left:.6rem; display:flex; flex-wrap:wrap; gap:.3rem }
    .nw-chip{ font-size:.68rem; font-weight:700; padding:.15rem .5rem; color:var(--on-accent,#fff);
        background:var(--color-primary,#6366f1) }

    .nw-card__body{ display:flex; flex-direction:column; gap:.45rem; padding:1rem 1.1rem 1.15rem; flex:1 }

    /* Обрезка строками — настоящим CSS: класса line-clamp в сборке нет,
       и без этого длинный заголовок ломал высоту карточек в ряду. */
    .nw-card__title{ margin:0; font-size:1.05rem; line-height:1.35; font-weight:700 }
    .nw-card__title a{ color:var(--surface-ink,#111827); display:-webkit-box; -webkit-line-clamp:2;
        -webkit-box-orient:vertical; overflow:hidden }
    .nw-card__title a:hover{ color:var(--color-primary,#6366f1) }

    /* Нижняя строка карточки. Раньше она стояла сразу под заголовком, тем
       же цветом, что и анонс, и терялась между заголовком и текстом. Теперь
       это отдельная полоса внизу: линия сверху, лёгкая подложка, края
       разведены. */
    .nw-card__meta{ display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        margin:.65rem -1.1rem -1.15rem; padding:.55rem 1.1rem; font-size:.75rem;
        border-top:1px solid #eef2f7; background:var(--surface-2,#f8fafc) }
    .nw-meta__date{ color:var(--surface-dim,#94a3b8); font-variant-numeric:tabular-nums }
    .nw-meta__date::before{ content:'🗓'; margin-right:.35rem; opacity:.75 }
    .nw-meta__time{ color:var(--surface-ink,#475569); font-weight:600; white-space:nowrap }
    .nw-meta__time::before{ content:'⏱'; margin-right:.3rem; opacity:.75 }

    .nw-card__text{ margin:0; font-size:.85rem; line-height:1.55; color:var(--surface-mute,#64748b); flex:1;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden }

    /* Ссылка прижата к ПРАВОМУ краю карточки: движение вперёд там же, где
       оно у остальных карточек сайта, и на одной вертикали со временем
       чтения в строке ниже. Тело карточки — flex-колонка, поэтому хватает
       align-self; ширина при этом остаётся по содержимому, а не во всю
       строку (иначе нажималось бы пустое место слева от подписи).
       inline-flex — чтобы стрелка стояла на одной линии с текстом. */
    .nw-card__more{ align-self:flex-end; display:inline-flex; align-items:center;
        gap:.35rem; font-size:.85rem; font-weight:700; color:var(--color-primary,#6366f1) }

    /* На телефонах и планшетах ссылке нужна высота зоны нажатия: голая
       строка текста была 20 пикселей. Порог общий для проекта. */
    @media (max-width: 1024px), (max-height: 500px){
        .nw-card__more{ min-height:32px }
    }

    .nw-pager{ margin-top:1.5rem; display:flex; justify-content:center }

    .nw-empty{ padding:3rem 1rem; text-align:center; color:var(--surface-dim,#94a3b8) }
    .nw-empty i{ font-size:2rem; display:block; margin-bottom:.75rem; opacity:.5 }


    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы ниже.
       Значения берутся из общих переменных поверхностей, объявленных
       в макете: один набор на все шаблоны. */
    body.fx-theme-dark .nw__head,
    body.fx-theme-dark .nw-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .nw-card__meta{ background:var(--surface-2); border-color:var(--surface-bd) }
    body.fx-theme-dark .nw__title, body.fx-theme-dark .nw-card__title a{ color:var(--surface-ink) }
    @media (prefers-color-scheme: dark){
        .nw__head, .nw-card{ background:#111827; border-color:#1f2937 }
        .nw-card__meta{ background:#0b1220; border-color:#1f2937 }
        .nw-meta__time{ color:#cbd5e1 }
        .nw__title, .nw-card__title a{ color:#f3f4f6 }
        .nw-card__text{ color:#94a3b8 }
        .nw-card__media{ background:#1f2937 }
    }
</style>
@endpush
