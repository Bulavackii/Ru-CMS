{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  📖 ШАБЛОН «ЖУРНАЛ»                                              ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Для лонгридов, интервью и обзоров: первый материал показывается ║
    ║  крупно, остальные — сеткой под ним.                             ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = magazine         ║
    ║    Порядок задаётся датой: свежий материал становится ведущим.    ║
    ║                                                                  ║
    ║  ОТКУДА БЕРЁТСЯ КАРТИНКА                                         ║
    ║    Первый тег <img> в тексте материала. Нет картинки — вместо неё ║
    ║    рисуется буквица из заголовка, поэтому пусто не будет.         ║
    ║                                                                  ║
    ║  ЦВЕТА                                                           ║
    ║    Берутся из активной темы (--color-primary/--color-accent),     ║
    ║    поэтому шаблон одинаково читается на любом оформлении.         ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

@php
    // Первый <img> из текста — обложка. Отдельной колонки для неё у
    // материалов нет, поэтому берём из содержимого.
    $coverOf = function ($item) {
        return preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', (string) $item->content, $m)
            ? $m[1]
            : null;
    };

    // Короткая выжимка: теги вырезаны, пробелы схлопнуты.
    $excerptOf = function ($item, int $limit) {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags((string) $item->content)));

        return \Illuminate\Support\Str::limit($text, $limit);
    };

    $items = $newsList ?? collect();
    $lead = $items->first();
    $rest = $items->skip(1);
@endphp

@if ($items->count())
<section class="mag">
    {{-- ── Заголовок раздела ── --}}
    <div class="mag__head">
        <span class="mag__badge">📖</span>
        <div>
            <h2 class="mag__title">{{ $title ?? 'Журнал' }}</h2>
            <p class="mag__sub">Большие материалы: обзоры, интервью, разборы</p>
        </div>
    </div>

    {{-- ── Ведущий материал ── --}}
    @if ($lead)
        @php $leadCover = $coverOf($lead); @endphp

        <a href="{{ url('/news/' . $lead->slug) }}" class="mag-lead">
            <div class="mag-lead__media">
                @if ($leadCover)
                    <img src="{{ $leadCover }}" alt="{{ $lead->title }}" loading="lazy">
                @else
                    {{-- Заглушки нет — рисуем буквицу, чтобы блок не выглядел сломанным --}}
                    <span class="mag-lead__letter">{{ mb_strtoupper(mb_substr($lead->title, 0, 1)) }}</span>
                @endif
            </div>

            <div class="mag-lead__body">
                <span class="mag-chip">🔥 Главное</span>
                <h3 class="mag-lead__title">{{ $lead->title }}</h3>
                <p class="mag-lead__text">{{ $excerptOf($lead, 260) }}</p>

                <span class="mag-lead__meta">
                    🗓 {{ $lead->created_at?->format('d.m.Y') }}
                    <span>⏱ {{ __('frontend.news.reading_time', ['min' => reading_time($lead->content)]) }}</span>
                    <span class="mag-lead__more">Читать целиком →</span>
                </span>
            </div>
        </a>
    @endif

    {{-- ── Остальные материалы ── --}}
    @if ($rest->count())
        <div class="mag-grid">
            @foreach ($rest as $item)
                @php $cover = $coverOf($item); @endphp

                <a href="{{ url('/news/' . $item->slug) }}" class="mag-card">
                    <div class="mag-card__media">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="{{ $item->title }}" loading="lazy">
                        @else
                            <span class="mag-card__letter">{{ mb_strtoupper(mb_substr($item->title, 0, 1)) }}</span>
                        @endif
                    </div>

                    <div class="mag-card__body">
                        <h4 class="mag-card__title">{{ $item->title }}</h4>
                        <p class="mag-card__text">{{ $excerptOf($item, 110) }}</p>
                        <span class="mag-card__meta">🗓 {{ $item->created_at?->format('d.m.Y') }}
                            · ⏱ {{ __('frontend.news.reading_time', ['min' => reading_time($item->content)]) }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endif

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. Цвета — из активной темы. */
    .mag{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .mag__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:#fff; border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .mag__badge{ display:flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        flex:none; font-size:1.2rem; background:var(--color-primary,#6366f1) }
    .mag__title{ margin:0; font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2 }
    .mag__sub{ margin:.1rem 0 0; font-size:.82rem; color:#6b7280 }

    /* ── Ведущий материал ── */
    .mag-lead{ display:grid; grid-template-columns:minmax(0,1.15fr) minmax(0,1fr); gap:0;
        background:#fff; border:1px solid #eef2f7; margin-bottom:1rem; overflow:hidden;
        transition:border-color .15s, transform .15s }
    .mag-lead:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }
    .mag-lead__media{ position:relative; min-height:20rem; display:flex; align-items:center;
        justify-content:center; background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .mag-lead__media img{ width:100%; height:100%; object-fit:cover; display:block }
    .mag-lead__letter{ font-size:7rem; font-weight:800; color:#fff; opacity:.9; line-height:1 }
    .mag-lead__body{ padding:2rem 2.25rem; display:flex; flex-direction:column; justify-content:center }
    .mag-lead__title{ margin:.75rem 0 .6rem; font-size:1.75rem; line-height:1.25; font-weight:700; color:#111827 }
    .mag-lead__text{ margin:0; font-size:1rem; line-height:1.6; color:#475569 }
    .mag-lead__meta{ display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
        margin-top:1.25rem; font-size:.82rem; color:#94a3b8 }
    .mag-lead__more{ font-weight:700; color:var(--color-primary,#6366f1) }

    .mag-chip{ align-self:flex-start; font-size:.7rem; font-weight:700; letter-spacing:.04em;
        padding:.2rem .6rem; color:#fff; background:var(--color-accent,#8b5cf6) }

    /* ── Сетка остальных ── */
    .mag-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(17rem,1fr)); gap:1rem }
    .mag-card{ display:flex; flex-direction:column; background:#fff; border:1px solid #eef2f7;
        overflow:hidden; transition:border-color .15s, transform .15s }
    .mag-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }
    .mag-card__media{ height:9.5rem; display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .mag-card__media img{ width:100%; height:100%; object-fit:cover; display:block }
    .mag-card__letter{ font-size:3rem; font-weight:800; color:#fff; opacity:.9; line-height:1 }
    .mag-card__body{ padding:1rem 1.1rem 1.15rem; display:flex; flex-direction:column; gap:.4rem; flex:1 }
    .mag-card__title{ margin:0; font-size:1rem; line-height:1.35; font-weight:700; color:#111827 }
    .mag-card__text{ margin:0; font-size:.84rem; line-height:1.5; color:#64748b; flex:1 }
    .mag-card__meta{ font-size:.75rem; color:#94a3b8 }

    @media (max-width: 860px){
        .mag-lead{ grid-template-columns:1fr }
        .mag-lead__media{ min-height:13rem }
        .mag-lead__body{ padding:1.35rem }
        .mag-lead__title{ font-size:1.35rem }
    }

    @media (prefers-color-scheme: dark){
        .mag__head, .mag-lead, .mag-card{ background:#111827; border-color:#1f2937 }
        .mag__title, .mag-lead__title, .mag-card__title{ color:#f3f4f6 }
        .mag-lead__text{ color:#cbd5e1 }
        .mag-card__text{ color:#94a3b8 }
    }
</style>
@endpush
