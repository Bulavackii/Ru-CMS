{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  🛍️ ШАБЛОН «ТОВАРЫ»                                              ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Витрина каталога: обложка, цена, остаток и кнопка в корзину.     ║
    ║                                                                  ║
    ║  ГДЕ ПРАВИТЬ СОДЕРЖИМОЕ                                          ║
    ║    Панель → Новости → материал → поле «Шаблон» = products         ║
    ║    Товар в этой CMS — обычный материал с ценой: корзина работает  ║
    ║    именно с ними. Таблица products в схеме есть, но НИ К ЧЕМУ     ║
    ║    не подключена — заполнять её не нужно.                         ║
    ║                                                                  ║
    ║  ЦЕНА, ОСТАТОК, АКЦИЯ                                            ║
    ║    Поля «Цена», «Остаток» и галочка «Акция» в форме материала.    ║
    ║    Остаток пуст — товар считается всегда доступным.               ║
    ║    Остаток 0 — кнопка гаснет, вместо неё «Нет в наличии».         ║
    ║                                                                  ║
    ║  ОБЛОЖКА                                                         ║
    ║    Первый <img> или <video> в тексте. Нет медиа — заглушка.       ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

<section class="pr">
    <div class="pr__head">
        <span class="fx-badge"><i class="fas fa-bag-shopping"></i></span>
        <div>
            <h2 class="pr__title">{{ $title ?? __('frontend.products.title') }}</h2>
            <p class="pr__sub">{{ __('frontend.products.subtitle') }}</p>
        </div>
    </div>

    @if ($newsList->count())
        <div class="pr-grid">
            @foreach ($newsList as $news)
                @php
                    // ==== утилиты (как в новостях) ====
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

                    // достаём видео из контента (как в новостях)
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
                        $imageSrc = asset('images/no-image.png');
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

                    // доп. поля товара
                    $price  = $news->price ?? null;
                    $stock  = $news->stock ?? null;
                    $isPromo = $news->is_promo ?? false;
                    $isNew   = $news->created_at->gt(now()->subDays(7));
                @endphp
                @php
                    // Остаток: null — товар всегда доступен, 0 — распродан.
                    $outOfStock = !is_null($stock) && $stock <= 0;
                @endphp

                <article class="pr-card {{ $outOfStock ? 'is-out' : '' }}">
                    {{-- ── Обложка ── --}}
                    <div class="pr-card__media">
                        @if ($isVideo)
                            <video muted autoplay loop playsinline controls
                                   @if($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                {{ __('frontend.news.no_video') }}
                            </video>
                        @elseif ($imageSrc)
                            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" loading="lazy">
                        @else
                            <div class="pr-card__noimg">
                                <i class="fas fa-image"></i>
                                <span>{{ __('frontend.news.no_image') }}</span>
                            </div>
                        @endif

                        @if ($isPromo)
                            <span class="pr-badge pr-badge--sale">{{ __('frontend.products.sale') }}</span>
                        @elseif ($isNew)
                            <span class="pr-badge pr-badge--new">{{ __('frontend.products.new') }}</span>
                        @endif

                        @if ($outOfStock)
                            <span class="pr-card__out">{{ __('frontend.products.out_of_stock') }}</span>
                        @endif
                    </div>

                    {{-- ── Текст ── --}}
                    <div class="pr-card__body">
                        @if ($news->categories->count())
                            <div class="pr-card__cats">
                                @foreach ($news->categories->take(2) as $category)
                                    <a href="{{ url('/?category_products=' . $category->id) }}"
                                       class="pr-chip">{{ $category->title }}</a>
                                @endforeach
                            </div>
                        @endif

                        <h3 class="pr-card__title">
                            <a href="{{ route('news.show', $news->slug) }}">{{ $news->title }}</a>
                        </h3>

                        <p class="pr-card__text">{{ \Illuminate\Support\Str::limit(trim(preg_replace('~\s+~u', ' ', strip_tags($news->content))), 110) }}</p>

                        {{-- ── Цена и остаток ── --}}
                        <div class="pr-card__price-row">
                            @if ($price)
                                <span class="pr-card__price">{{ number_format($price, 0, ',', ' ') }} ₽</span>
                            @endif

                            @if (!is_null($stock))
                                <span class="pr-card__stock stock-display" data-id="{{ $news->id }}">
                                    {{ __('frontend.products.left') }} <span>{{ $stock }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- ── Количество и корзина ── --}}
                        @unless ($outOfStock)
                            <div class="pr-card__buy">
                                <div class="pr-qty">
                                    <button type="button" class="pr-qty__btn decrement" data-id="{{ $news->id }}"
                                            aria-label="{{ __('frontend.products.less') }}">−</button>
                                    {{-- id обязателен: обработчики +/- и корзины
                                         ищут поле как #qty-{id}. --}}
                                    <input type="number" min="1" value="1" id="qty-{{ $news->id }}"
                                           class="pr-qty__input qty-input" data-id="{{ $news->id }}"
                                           aria-label="{{ __('frontend.products.qty') }}">
                                    <button type="button" class="pr-qty__btn increment" data-id="{{ $news->id }}"
                                            data-stock="{{ $stock }}"
                                            aria-label="{{ __('frontend.products.more') }}">+</button>
                                </div>

                                <a href="#" class="pr-card__cart add-to-cart"
                                   data-id="{{ $news->id }}"
                                   data-title="{{ $news->title }}"
                                   data-price="{{ $price }}"
                                   data-stock="{{ $stock }}">
                                    <i class="fas fa-cart-shopping"></i> {{ __('frontend.products.to_cart') }}
                                </a>
                            </div>
                        @endunless

                        {{-- Нижняя полоса — как в остальных шаблонах --}}
                        <div class="pr-card__meta">
                            <span class="pr-meta__date">{{ $news->created_at?->format('d.m.Y') }}</span>
                            <a href="{{ route('news.show', $news->slug) }}" class="pr-meta__link">
                                {{ __('frontend.products.details') }} →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="pr-empty">
            <i class="fas fa-bag-shopping"></i>
            <p>{{ __('frontend.products.empty') }}</p>
        </div>
    @endif
</section>

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни line-clamp,
       ни произвольных значений, ни прозрачности через /NN. Цвета — из
       активной темы, поэтому витрина живёт на любом оформлении. */
    .pr{ max-width:80rem; margin:2.5rem auto; padding:0 1rem }

    .pr__head{ display:inline-flex; align-items:center; gap:.75rem; padding:.7rem 1.15rem;
        background:#fff; border:1px solid rgba(17,24,39,.08); box-shadow:0 2px 10px rgba(15,23,42,.06);
        margin-bottom:1.5rem }
    .pr__title{ margin:0; font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2 }
    .pr__sub{ margin:.1rem 0 0; font-size:.82rem; color:#6b7280 }

    .pr-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(18rem,1fr)); gap:1rem }

    .pr-card{ display:flex; flex-direction:column; background:var(--surface,#fff); border:1px solid var(--surface-bd,#eef2f7);
        overflow:hidden; transition:border-color .15s, transform .15s }
    .pr-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }
    .pr-card.is-out{ opacity:.72 }

    .pr-card__media{ position:relative; height:12.5rem; background:var(--surface-2,#f1f5f9) }
    .pr-card__media img, .pr-card__media video{ width:100%; height:100%; object-fit:cover; display:block }
    .pr-card__noimg{ display:flex; flex-direction:column; align-items:center; justify-content:center;
        gap:.4rem; height:100%; color:#94a3b8; font-size:.75rem }
    .pr-card__noimg i{ font-size:1.6rem; opacity:.5 }

    .pr-badge{ position:absolute; top:.7rem; right:.7rem; padding:.2rem .6rem; font-size:.68rem;
        font-weight:800; letter-spacing:.03em; color:#fff; box-shadow:0 4px 14px rgba(15,23,42,.3) }
    .pr-badge--sale{ background:#dc2626 }
    .pr-badge--new{ background:var(--color-accent,#8b5cf6) }

    /* Распроданный товар: карточка гаснет, поверх обложки — метка. */
    .pr-card__out{ position:absolute; inset:auto 0 0 0; padding:.4rem; text-align:center;
        font-size:.75rem; font-weight:700; color:#fff; background:rgba(15,23,42,.75) }

    .pr-card__body{ display:flex; flex-direction:column; gap:.45rem; padding:1rem 1.1rem 0; flex:1 }

    .pr-card__cats{ display:flex; flex-wrap:wrap; gap:.3rem }
    .pr-chip{ font-size:.68rem; font-weight:700; padding:.12rem .45rem; color:#4f46e5;
        background:#eef2ff; border:1px solid #e0e7ff }

    .pr-card__title{ margin:0; font-size:1rem; line-height:1.35; font-weight:700 }
    .pr-card__title a{ color:#111827; display:-webkit-box; -webkit-line-clamp:2;
        -webkit-box-orient:vertical; overflow:hidden }
    .pr-card__title a:hover{ color:var(--color-primary,#6366f1) }

    .pr-card__text{ margin:0; font-size:.82rem; line-height:1.5; color:#64748b; flex:1;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

    .pr-card__price-row{ display:flex; align-items:baseline; justify-content:space-between;
        gap:.75rem; margin-top:.2rem }
    .pr-card__price{ font-size:1.3rem; font-weight:800; color:#111827; white-space:nowrap }
    .pr-card__stock{ font-size:.74rem; color:#64748b; white-space:nowrap }

    .pr-card__buy{ display:flex; align-items:stretch; gap:.5rem; margin-top:.55rem }

    .pr-qty{ display:flex; align-items:stretch; border:1px solid #e2e8f0; flex:none }
    .pr-qty__btn{ width:2rem; background:#f8fafc; color:#334155; font-size:1rem; font-weight:700;
        border:0; cursor:pointer; line-height:1 }
    .pr-qty__btn:hover{ background:#eef2ff; color:var(--color-primary,#6366f1) }
    /* Стрелки у number-поля крадут ширину и выглядят чужеродно. */
    .pr-qty__input{ width:2.6rem; text-align:center; border:0; border-left:1px solid #e2e8f0;
        border-right:1px solid #e2e8f0; font-size:.85rem; color:#111827; background:#fff;
        -moz-appearance:textfield }
    .pr-qty__input::-webkit-outer-spin-button,
    .pr-qty__input::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0 }

    .pr-card__cart{ flex:1; display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
        padding:.55rem .75rem; font-size:.85rem; font-weight:700; color:#fff; white-space:nowrap;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6));
        transition:filter .15s }
    .pr-card__cart:hover{ filter:brightness(1.08); color:#fff }

    .pr-card__meta{ display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        margin:.8rem -1.1rem 0; padding:.55rem 1.1rem; font-size:.75rem;
        border-top:1px solid #eef2f7; background:#f8fafc }
    .pr-meta__date{ color:#94a3b8; font-variant-numeric:tabular-nums }
    .pr-meta__date::before{ content:'🗓'; margin-right:.35rem; opacity:.75 }
    .pr-meta__link{ font-weight:700; color:var(--color-primary,#6366f1); white-space:nowrap }

    .pr-empty{ padding:3rem 1rem; text-align:center; color:#94a3b8 }
    .pr-empty i{ font-size:2rem; display:block; margin-bottom:.75rem; opacity:.5 }


    /* Тёмная ТЕМА сайта — не то же, что тёмный режим системы ниже.
       Значения берутся из общих переменных поверхностей, объявленных
       в макете: один набор на все шаблоны. */
    body.fx-theme-dark .pr__head,
    body.fx-theme-dark .pr-card{ background:var(--surface); border-color:var(--surface-bd) }
    body.fx-theme-dark .pr-card__meta{ background:var(--surface-2); border-color:var(--surface-bd) }
    body.fx-theme-dark .pr__title, body.fx-theme-dark .pr-card__title a, body.fx-theme-dark .pr-card__price{ color:var(--surface-ink) }
    @media (prefers-color-scheme: dark){
        .pr__head, .pr-card{ background:#111827; border-color:#1f2937 }
        .pr__title, .pr-card__title a, .pr-card__price{ color:#f3f4f6 }
        .pr-card__media, .pr-qty__btn{ background:#1f2937; color:#cbd5e1 }
        .pr-qty__input{ background:#111827; color:#f3f4f6; border-color:#1f2937 }
        .pr-card__meta{ background:#0b1220; border-color:#1f2937 }
        .pr-chip{ background:#1e1b4b; border-color:#312e81; color:#c7d2fe }
    }
</style>
@endpush

{{-- Контейнер для всплывающих уведомлений --}}
<div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2"></div>

@push('scripts')
<script>
    // ===== уведомления
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `px-4 py-3 rounded-lg shadow-md text-sm font-medium flex items-center gap-2 animate-slide-in
            ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
        toast.innerHTML = `${type === 'success' ? '✅' : '❌'} <span>${message}</span>`;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => toast.remove(), 400);
        }, 2500);
    }

    // ===== счётчик корзины
    function updateCartCount() {
        fetch("{{ route('cart.count') }}")
            .then(res => res.json())
            .then(data => {
                const counter = document.getElementById('cart-count');
                if (counter) {
                    counter.textContent = data.count;
                    counter.classList.toggle('hidden', data.count === 0);
                }
            });
    }

    // ===== локальное обновление остатка
    function updateLocalStock(productId) {
        const input = document.querySelector(`#qty-${productId}`);
        const qty = parseInt(input.value);
        const originalStock = parseInt(document.querySelector(`.add-to-cart[data-id='${productId}']`).dataset.stock);
        const stockSpan = document.querySelector(`.stock-display[data-id='${productId}'] span`);
        if (stockSpan) {
            const remaining = originalStock - qty;
            stockSpan.textContent = remaining < 0 ? 0 : remaining;
        }
    }

    // ===== серверный остаток
    function updateServerStock(productId) {
        fetch(`/product/${productId}/stock`)
            .then(res => res.json())
            .then(data => {
                const stockSpan = document.querySelector(`.stock-display[data-id='${productId}'] span`);
                if (stockSpan) {
                    stockSpan.textContent = data.stock;
                    const btn = document.querySelector(`.add-to-cart[data-id='${productId}']`);
                    if (btn) btn.dataset.stock = data.stock;
                }
            });
    }

    // ===== Добавление в корзину
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            const input = document.querySelector(`#qty-${id}`);
            const qty = parseInt(input?.value || 1);
            const availableStock = parseInt(this.dataset.stock);

            if (!isNaN(availableStock) && qty > availableStock) {
                showToast(`⚠️ На складе доступно всего ${availableStock} шт.`, 'error');
                return;
            }

            fetch("{{ route('cart.add') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: this.dataset.id,
                    title: this.dataset.title,
                    price: this.dataset.price,
                    qty: qty
                })
            }).then(res => {
                if (!res.ok) throw res;
                return res.json();
            }).then(data => {
                showToast(data.message || 'Добавлено в корзину!', 'success');
                updateCartCount();
                updateServerStock(id);
            }).catch(async error => {
                const msg = await error.json().then(e => e.message ?? 'Ошибка запроса').catch(() => 'Ошибка');
                showToast(msg, 'error');
            });
        });
    });

    // ===== +/- количество
    document.querySelectorAll('.increment').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const stock = parseInt(this.dataset.stock);
            const input = document.querySelector(`#qty-${id}`);
            let current = parseInt(input.value);
            if (isNaN(stock) || current < stock) {
                input.value = current + 1;
                updateLocalStock(id);
            }
        });
    });
    document.querySelectorAll('.decrement').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const input = document.querySelector(`#qty-${id}`);
            let current = parseInt(input.value);
            if (current > 1) {
                input.value = current - 1;
                updateLocalStock(id);
            }
        });
    });
</script>
@endpush
