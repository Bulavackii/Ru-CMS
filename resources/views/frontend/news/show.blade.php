@extends('layouts.frontend')

@section('title', $news->t('title'))

@section('content')
@includeIf('frontend.templates.skins.' . ($template ?? 'default'))
    @php
        // Обложка → баннер (только если это картинка)
        $IMG = ['jpg','jpeg','png','gif','webp','bmp','svg','avif'];
        $coverAbs = null;
        if (!empty($news->cover)) {
            $raw = (string) $news->cover;
            $isHttp = (bool) preg_match('~^https?://~i', $raw);
            $rel = ltrim(preg_replace('~^storage/~','', $raw), '/');
            if ($isHttp) { $coverAbs = $raw; }
            elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) { $coverAbs = asset('storage/'.$rel); }
            $ext = strtolower(pathinfo(parse_url($coverAbs ?? '', PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($coverAbs && !in_array($ext, $IMG, true)) { $coverAbs = null; }
        }
        // reading_time() считает слова с поддержкой кириллицы (см. app/helpers.php)
        $readMins = reading_time($news->t('content'));
    @endphp

    {{-- Класс шаблона на самой статье. Оформление ПОД шаблон живёт в
         «шкурке» — необязательном партиале frontend/templates/skins/<шаблон>.
         Нет шкурки — страница выглядит ровно как раньше, поэтому добавление
         нового шаблона ничего не ломает. Копировать show.blade.php под каждый
         шаблон нельзя: крошки, плашка покупки и «поделиться» разъехались бы по
         восьми копиям на первой же правке. --}}
    <article class="w-full max-w-screen-2xl mx-auto news--{{ $template ?? 'default' }}">

        {{-- ===== Шапка новости ===== --}}
        <header class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            {{-- Хлебные крошки --}}
            <nav class="flex items-center flex-wrap gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-5" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors inline-flex items-center gap-1">@themeIcon('home') {{ __('frontend.common.home') }}</a>
                <span class="opacity-50">/</span>
                <span class="text-gray-400 dark:text-gray-500">{{ __('frontend.news.section') }}</span>
                <span class="opacity-50">/</span>
                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[16rem]">{{ $news->t('title') }}</span>
            </nav>

            <div class="flex items-start gap-3 sm:gap-4">
                <span class="fx-badge shrink-0 mt-1"><i class="fas fa-newspaper"></i></span>
                <h1 class="fx-section-title text-2xl sm:text-3xl md:text-4xl leading-tight break-words">
                    {{ $news->t('title') }}
                </h1>
            </div>

            {{-- Мета: дата · время чтения · категории --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><i class="far fa-calendar-alt fx-ico"></i> {{ optional($news->created_at)->format('d.m.Y') }}</span>
                <span class="inline-flex items-center gap-1.5"><i class="far fa-clock fx-ico"></i> ~{{ $readMins }} мин чтения</span>
                @if ($news->categories->isNotEmpty())
                    <span class="inline-flex flex-wrap items-center gap-1.5">
                        @foreach ($news->categories as $category)
                            <a href="{{ url('/?category=' . $category->id) }}" class="fx-chip hover:brightness-95">{{ $category->title }}</a>
                        @endforeach
                    </span>
                @endif
            </div>
        </header>

        {{-- ===== Обложка-баннер (если есть картинка) ===== --}}
        @if ($coverAbs)
            <div class="fx-card overflow-hidden mb-6">
                <img src="{{ $coverAbs }}" alt="{{ $news->t('title') }}" class="w-full object-cover" style="max-height:30rem" loading="lazy">
            </div>
        @endif

        {{-- ===== Контент ===== --}}
        <div class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            <div class="prose prose-sm sm:prose lg:prose-lg max-w-none news-content text-gray-800 dark:text-gray-100">
                {{-- render_shortcodes: раскрывает [captcha preset="…"] и другие
                     шорткоды, вставленные в текст через редактор. В самом HTML
                     материала Blade-хелперы не работают — шаблонизатор к этому
                     моменту уже отработал. --}}
                {!! render_shortcodes($news->t('content')) !!}
            </div>

            {{-- Блок с ценой, остатком, количеством и кнопкой (товары) --}}
            @if($news->price)
                {{-- Панель покупки.

                     Была набрана Tailwind-классами с `dark:`-вариантами
                     (bg-gray-50/70 dark:bg-gray-800/60 и т.п.), а в собранном
                     tailwind.min.css этого проекта нет НИ ОДНОГО `dark:`, как
                     нет и модификаторов прозрачности вида `/70`. То есть весь
                     тёмный набор здесь был мёртвым кодом: на тёмной теме сайта
                     панель оставалась светло-серой, а плашки цены и остатка —
                     бледно-пастельными пятнами.

                     Цвета берём из общих переменных поверхностей. Зелёный у
                     цены и янтарный у остатка несут смысл, поэтому оттенок
                     сохраняется, а светлота подмешивается из темы. --}}
                {{-- Плашка покупки — ОДНА строка, а не сетка из двух колонок.
                     Сеткой цена оказывалась у левого края, а количество с
                     кнопкой — у правого, между ними висела пустота во всю
                     ширину карточки, и кнопка уезжала под шаговый
                     переключатель, оставляя внизу пустую полосу. Теперь
                     всё выстроено в ряд и переносится по мере надобности:
                     на телефоне факты идут строкой, а действия — второй
                     строкой во всю ширину. --}}
                <div class="mt-8 buy-panel">
                    <div class="buy-row">
                        <div class="buy-facts">
                            <div class="buy-chip buy-chip--price">
                                <i class="fas fa-tag"></i> {{ number_format($news->price, 2, ',', ' ') }} ₽
                            </div>
                            @if (!is_null($news->stock))
                                <div class="buy-chip buy-chip--stock stock-display" data-id="{{ $news->id }}">
                                    <i class="fas fa-box"></i> {{ __('frontend.news.in_stock') }} <span>{{ $news->stock }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="buy-actions">
                            <div class="buy-qty-wrap">
                                <span class="text-sm font-medium buy-qty__label">{{ __('frontend.news.quantity') }}</span>
                                {{-- ⚠️ Без утилит flex/items-center: они перебивали
                                     align-items:stretch у самого класса, и кнопки
                                     оставались 18 пикселей в рамке высотой 40. --}}
                                <div class="buy-qty">
                                    <button type="button" class="buy-qty__btn decrement" data-id="{{ $news->id }}" aria-label="{{ __('frontend.news.quantity') }} −">−</button>
                                    <input type="text" id="qty-{{ $news->id }}" value="1" readonly class="buy-qty__input qty-input" data-id="{{ $news->id }}">
                                    <button type="button" class="buy-qty__btn increment" data-id="{{ $news->id }}" data-stock="{{ $news->stock }}" aria-label="{{ __('frontend.news.quantity') }} +">+</button>
                                </div>
                            </div>

                            <button type="button" class="fx-btn add-to-cart buy-add" data-id="{{ $news->id }}" data-title="{{ $news->t('title') }}" data-price="{{ $news->price }}" data-stock="{{ $news->stock }}">
                                <i class="fas fa-cart-shopping"></i> {{ __('frontend.products.to_cart') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== Действия ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <a href="{{ url('/') }}" class="fx-btn px-5 py-2.5 text-sm">
                <i class="fas fa-arrow-left"></i> На главную
            </a>

            {{-- Поделиться --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">{{ __('frontend.news.share') }}</span>
                <a href="https://vk.com/share.php?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="share-btn share-btn--plain" style="--c:#0077FF" title="ВКонтакте" aria-label="Поделиться во ВКонтакте"><x-icon.vk :size="16" /></a>
                <a href="https://max.ru/share?url={{ urlencode(url()->current()) }}&text={{ urlencode($news->t('title')) }}" target="_blank" rel="noopener" class="share-btn share-btn--plain" style="--c:#3B4BF5" title="MAX" aria-label="Поделиться в MAX"><x-icon.max :size="16" /></a>
                <button type="button" class="share-btn copy-link" data-url="{{ url()->current() }}" style="--c:var(--color-primary, #6366f1)" title="{{ __('frontend.news.copy_link') }}" aria-label="Скопировать ссылку"><i class="fas fa-link"></i></button>
            </div>
        </div>
    </article>

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2"></div>
@endsection

@push('scripts')
<script>
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

    function updateServerStock(productId) {
        fetch(`/product/${productId}/stock`)
            .then(res => res.json())
            .then(data => {
                const stockSpan = document.querySelector(`.stock-display[data-id='${productId}'] span`);
                if (stockSpan) {
                    stockSpan.textContent = data.stock;
                    const btn = document.querySelector(`.add-to-cart[data-id='${productId}']`);
                    if (btn) {
                        btn.dataset.stock = data.stock;
                    }
                }
            });
    }

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const id = this.dataset.id;
            const input = document.querySelector(`#qty-${id}`);
            const qty = parseInt(input?.value || 1);
            const availableStock = parseInt(this.dataset.stock);

            if (qty > availableStock) {
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

    document.querySelectorAll('.increment').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const stock = parseInt(this.dataset.stock);
            const input = document.querySelector(`#qty-${id}`);
            let current = parseInt(input.value);
            if (current < stock) {
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

    // Копировать ссылку на новость
    document.querySelectorAll('.copy-link').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.url || location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url)
                    .then(() => showToast('Ссылка скопирована'))
                    .catch(() => showToast('Не удалось скопировать', 'error'));
            } else {
                showToast('Копирование недоступно', 'error');
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* ── Панель покупки ──────────────────────────────────────────────
       Оформление литеральным CSS, а не Tailwind-утилитами: в собранном
       tailwind.min.css этого проекта нет ни `dark:`-вариантов, ни
       прозрачности вида `/70`, и прежний набор классов на тёмной теме
       не давал ничего. */
    .buy-panel{ padding:1.15rem 1.25rem; background:var(--surface-2,#f9fafb);
        border:1px solid var(--surface-bd,#e5e7eb) }

    /* Один ряд: слева факты о товаре, справа действия. Перенос разрешён —
       при нехватке ширины действия уезжают на вторую строку целиком, а не
       разваливаются по одному элементу.

       ⚠️ Высота всех четырёх частей ряда задаётся ОДНОЙ переменной. Пока
       каждая считала её сама из своих отступов, на одной строке стояли
       чипы в 37, переключатель в 41 и кнопка в 40 — края не совпадали ни
       сверху, ни снизу. Меняешь размер — меняй здесь, а не по месту. */
    .buy-panel{ --buy-h:40px }
    .buy-row{ display:flex; flex-wrap:wrap; align-items:center;
        justify-content:space-between; gap:.85rem 1.25rem }
    .buy-facts{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem }
    .buy-actions{ display:flex; flex-wrap:wrap; align-items:center; gap:.75rem;
        margin-left:auto }
    .buy-qty-wrap{ display:flex; align-items:center; gap:.6rem }
    /* Отступы у кнопки заданы здесь, а не утилитами в разметке: подпись
       не переносится («В корзину» в две строки ломает ряд), а размер
       обязан совпадать с шаговым переключателем рядом. */
    .buy-add{ white-space:nowrap; padding:0 1.15rem; font-size:.875rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
        height:var(--buy-h,40px) }

    /* Телефоны и планшеты: общий для проекта порог. Действия занимают всю
       строку, кнопка растягивается — на узком экране её край иначе не
       угадать пальцем. Шаговый переключатель поднимается до 44: он был
       38×34, то есть ниже границы зоны нажатия. */
    @media (max-width: 1024px), (max-height: 500px){
        /* Одна переменная поднимает разом чипы, переключатель и кнопку:
           44 — нижняя граница зоны нажатия по Apple HIG. */
        .buy-panel{ --buy-h:44px; padding:1rem }
        .buy-facts, .buy-actions{ width:100%; margin-left:0 }
        .buy-actions{ justify-content:space-between }
        .buy-add{ flex:1 1 10rem }
    }

    /* Узкий экран: переключатель и кнопка не помещались в строку (309 при
       300 доступных), и раскладка расползалась на три ряда — цена, потом
       растянутый на всю ширину переключатель с огромным пустым полем между
       минусом и плюсом, потом кнопка. Вместо растягивания ужимаем сам
       переключатель: поле в 2.5rem вместо 2.75 и никакого flex:1. Теперь
       переключатель занимает 124, кнопка забирает остаток, и всё стоит в
       ОДНУ строку даже на 360. */
    @media (max-width: 640px){
        .buy-qty__input{ flex:none; width:2.5rem }
        .buy-add{ flex:1 1 auto }
    }

    /* Совсем узкий экран: подпись «Кол-во» рядом с переключателем места не
       оставляет — она уходит, роль поля и так очевидна по знакам. */
    @media (max-width: 420px){
        .buy-qty__label{ display:none }
        .buy-actions{ gap:.6rem }
    }

    .buy-chip{ display:inline-flex; align-items:center; gap:.5rem;
        min-height:var(--buy-h,40px); padding:0 1rem; font-size:.875rem; font-weight:600 }

    /* Зелёный у цены и янтарный у остатка несут смысл, поэтому оттенок
       остаётся, а светлота подмешивается из темы: на тёмной теме прежние
       пастельные заливки выглядели выцветшими пятнами. */
    .buy-chip--price{
        color:color-mix(in srgb, #16a34a 55%, var(--surface-ink,#14532d));
        background:color-mix(in srgb, #16a34a 18%, var(--surface,#dcfce7)) }
    .buy-chip--stock{
        color:color-mix(in srgb, #d97706 55%, var(--surface-ink,#713f12));
        background:color-mix(in srgb, #d97706 18%, var(--surface,#fef9c3)) }

    .buy-qty__label{ color:var(--surface-ink,#374151) }
    /* Шаговый переключатель — квадратные кнопки по краям и поле между
       ними, все одной высоты с остальным рядом. Оформление держим таким
       же, как у корзины (.crt-item__qty): это один и тот же орган
       управления на двух страницах, и разъезжаться им незачем. */
    /* ⚠️ box-sizing задаём явно. Без него рамка в 1 пиксель считается
       ПОВЕРХ заданной высоты, и переключатель выходил 44 там, где остальной
       ряд стоял на 40 — единственная часть плашки, выбивавшаяся из линии. */
    .buy-qty, .buy-qty__btn, .buy-qty__input, .buy-add, .buy-chip{ box-sizing:border-box }
    .buy-qty{ display:inline-flex; align-items:stretch; height:var(--buy-h,40px);
        border:1px solid var(--surface-bd,#e2e8f0); background:var(--surface,#fff);
        overflow:hidden }
    .buy-qty__btn{ display:flex; align-items:center; justify-content:center;
        width:var(--buy-h,40px); height:100%; padding:0; line-height:1;
        font-size:1rem; font-weight:700;
        color:var(--surface-ink,#334155); background:var(--surface-2,#f8fafc);
        border:0; cursor:pointer; transition:background .15s, color .15s }
    .buy-qty__btn:hover{ background:color-mix(in srgb, var(--color-primary,#6366f1) 10%, var(--surface,#fff));
        color:var(--color-primary,#6366f1) }
    .buy-qty__input{ width:2.75rem; height:100%; padding:0; text-align:center;
        font-size:.875rem; font-variant-numeric:tabular-nums;
        color:var(--surface-ink,#111827); background:var(--surface,#fff);
        border:0; border-left:1px solid var(--surface-bd,#e2e8f0);
        border-right:1px solid var(--surface-bd,#e2e8f0) }

    /* Контент новости: читаемая ширина (карточка на всю ширину, текст — комфортной мерой) */
    .news-content{ word-break:break-word; overflow-wrap:anywhere; line-height:1.8; font-size:1.06rem;
        max-width:70rem !important; margin-inline:auto; }
    .news-content > *:first-child{ margin-top:0; }
    .news-content *{ max-width:100% !important; box-sizing:border-box; }
    .news-content img, .news-content video, .news-content iframe, .news-content embed, .news-content object{
        max-width:100%; height:auto; display:block; margin:1.75rem auto; box-shadow:0 10px 28px -14px rgba(17,24,39,.4); }
    .news-content pre{ white-space:pre-wrap; word-break:break-word; background:#0f172a; color:#e5e7eb;
        padding:1rem 1.15rem; overflow-x:auto; font-size:.9rem; }
    .news-content a{ color:var(--color-primary,#6366f1); text-decoration:underline; text-underline-offset:2px; }
    .news-content table{ width:100%; display:block; overflow-x:auto; }
    .news-content h2, .news-content h3{ color:var(--surface-ink,#111827); }
    :root.dark .news-content h2, :root.dark .news-content h3{ color:#f3f4f6; }
    @media (max-width:640px){ .news-content{ font-size:.98rem; } }

    /* Кнопки «Поделиться» */
    .share-btn{ display:inline-flex; align-items:center; justify-content:center; width:2.3rem; height:2.3rem;
        border:1px solid rgba(17,24,39,.12); background:rgba(255,255,255,.6); color:var(--surface-mute,#6b7280); font-size:1rem;
        text-decoration:none; cursor:pointer;
        transition:color .15s ease, background .15s ease, border-color .15s ease, transform .15s ease; }
    :root.dark .share-btn{ border-color:rgba(255,255,255,.12); background:rgba(30,41,59,.5); color:#9ca3af; }
    .share-btn:hover{ color:#fff; background:var(--c,#6366f1); border-color:var(--c,#6366f1); transform:translateY(-2px); }
    /* У MAX собственный цветной глиф — фон кнопки при наведении не
       закрашиваем, иначе фирменный знак теряется. */
    .share-btn--plain{ padding:.25rem; background:transparent; }
    .share-btn--plain:hover{ background:transparent; border-color:var(--c,#6366f1); }
    :root.dark .share-btn--plain{ background:transparent; }
</style>
@endpush

