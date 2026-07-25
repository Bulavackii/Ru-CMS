@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
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
        $readMins = max(1, (int) ceil(str_word_count(strip_tags((string) $news->content)) / 180));
    @endphp

    <article class="w-full max-w-screen-2xl mx-auto">

        {{-- ===== Шапка новости ===== --}}
        <header class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            {{-- Хлебные крошки --}}
            <nav class="flex items-center flex-wrap gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-5" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors inline-flex items-center gap-1">@themeIcon('home') Главная</a>
                <span class="opacity-50">/</span>
                <span class="text-gray-400 dark:text-gray-500">Новости</span>
                <span class="opacity-50">/</span>
                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[16rem]">{{ $news->title }}</span>
            </nav>

            <div class="flex items-start gap-3 sm:gap-4">
                <span class="fx-badge shrink-0 mt-1"><i class="fas fa-newspaper"></i></span>
                <h1 class="fx-section-title text-2xl sm:text-3xl md:text-4xl leading-tight break-words">
                    {{ $news->title }}
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
                <img src="{{ $coverAbs }}" alt="{{ $news->title }}" class="w-full object-cover" style="max-height:30rem" loading="lazy">
            </div>
        @endif

        {{-- ===== Контент ===== --}}
        <div class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            <div class="prose prose-sm sm:prose lg:prose-lg max-w-none news-content text-gray-800 dark:text-gray-100">
                {!! $news->content !!}
            </div>

            {{-- Блок с ценой, остатком, количеством и кнопкой (товары) --}}
            @if($news->price)
                <div class="mt-8 border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/60 p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 bg-green-100 text-green-900 dark:bg-green-900 dark:text-green-200 px-4 py-2 font-semibold text-sm">
                                <i class="fas fa-tag"></i> {{ number_format($news->price, 2, ',', ' ') }} ₽
                            </div>
                            @if (!is_null($news->stock))
                                <div class="inline-flex items-center gap-2 bg-yellow-100 text-yellow-900 dark:bg-yellow-900 dark:text-yellow-200 px-4 py-2 font-semibold text-sm stock-display" data-id="{{ $news->id }}">
                                    <i class="fas fa-box"></i> Осталось: <span>{{ $news->stock }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3 flex flex-col sm:items-end">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Кол-во:</span>
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 overflow-hidden">
                                    <button type="button" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg decrement" data-id="{{ $news->id }}">−</button>
                                    <input type="text" id="qty-{{ $news->id }}" value="1" readonly class="w-12 text-center border-x border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm qty-input" data-id="{{ $news->id }}">
                                    <button type="button" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg increment" data-id="{{ $news->id }}" data-stock="{{ $news->stock }}">+</button>
                                </div>
                            </div>

                            <button type="button" class="fx-btn add-to-cart px-5 py-2.5 text-sm" data-id="{{ $news->id }}" data-title="{{ $news->title }}" data-price="{{ $news->price }}" data-stock="{{ $news->stock }}">
                                <i class="fas fa-cart-shopping"></i> В корзину
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
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Поделиться:</span>
                <a href="https://vk.com/share.php?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="share-btn" style="--c:#0077FF" title="ВКонтакте" aria-label="Поделиться во ВКонтакте"><i class="fab fa-vk"></i></a>
                <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($news->title) }}" target="_blank" rel="noopener" class="share-btn" style="--c:#26A5E4" title="Telegram" aria-label="Поделиться в Telegram"><i class="fab fa-telegram"></i></a>
                <button type="button" class="share-btn copy-link" data-url="{{ url()->current() }}" style="--c:#6366f1" title="Скопировать ссылку" aria-label="Скопировать ссылку"><i class="fas fa-link"></i></button>
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
    .news-content h2, .news-content h3{ color:#111827; }
    :root.dark .news-content h2, :root.dark .news-content h3{ color:#f3f4f6; }
    @media (max-width:640px){ .news-content{ font-size:.98rem; } }

    /* Кнопки «Поделиться» */
    .share-btn{ display:inline-flex; align-items:center; justify-content:center; width:2.3rem; height:2.3rem;
        border:1px solid rgba(17,24,39,.12); background:rgba(255,255,255,.6); color:#6b7280; font-size:1rem;
        text-decoration:none; cursor:pointer;
        transition:color .15s ease, background .15s ease, border-color .15s ease, transform .15s ease; }
    :root.dark .share-btn{ border-color:rgba(255,255,255,.12); background:rgba(30,41,59,.5); color:#9ca3af; }
    .share-btn:hover{ color:#fff; background:var(--c,#6366f1); border-color:var(--c,#6366f1); transform:translateY(-2px); }
</style>
@endpush

