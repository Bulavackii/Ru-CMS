<div class="my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    {{-- Заголовок раздела --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2">
        <i class="fas fa-box-open text-blue-600"></i>
        {{ $title ?? 'Товары' }}
    </h2>

    @if ($newsList->count())
        {{-- Контейнер карточек товаров --}}
        <div class="flex flex-wrap justify-center gap-8">
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

                {{-- Карточка товара (UI прежний) --}}
                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-5 flex flex-col relative border border-gray-100 hover:border-gray-200 max-w-xs w-full">

                    {{-- Категории --}}
                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1 select-none">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_products=' . $category->id) }}"
                                   class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Метки --}}
                    @if ($isPromo)
                        <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-red-600 text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                            🔥 STOCK
                        </div>
                    @elseif ($isNew)
                        <div class="absolute -top-3 right-3 z-10 bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                            🆕 Новинка
                        </div>
                    @endif

                    {{-- МЕДИА-БЛОК: точь-в-точь как в «Новостях» --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl"
                                   muted autoplay loop playsinline controls
                                   @if($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Название --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight break-words break-all line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition" title="{{ $news->title }}">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1 select-none">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Описание --}}
                    <div class="text-sm text-gray-600 mb-3 line-clamp-4 break-words break-all">
                        💬 {!! Str::limit(strip_tags($news->content), 160) !!}
                    </div>

                    {{-- Цена и остаток --}}
                    <div class="flex flex-wrap justify-between items-center text-sm text-gray-800 mb-3 select-none">
                        @if ($price)
                            <div class="bg-green-100 text-green-900 px-3 py-1 rounded-full font-medium shadow-sm">
                                💰 {{ number_format($price, 2, ',', ' ') }} ₽
                            </div>
                        @endif
                        @if (!is_null($stock))
                            <div class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full font-medium shadow-sm stock-display" data-id="{{ $news->id }}">
                                📦 Осталось: <span>{{ $stock }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Количество --}}
                    <div class="flex items-center gap-2 mb-3 justify-between select-none">
                        <span class="text-sm text-gray-700">Кол-во:</span>
                        <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                            <button type="button"
                                    class="px-2 bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold text-lg decrement"
                                    data-id="{{ $news->id }}">−</button>
                            <input type="text"
                                   id="qty-{{ $news->id }}"
                                   value="1"
                                   readonly
                                   class="w-10 text-center border-l border-r border-gray-200 text-sm qty-input"
                                   data-id="{{ $news->id }}">
                            <button type="button"
                                    class="px-2 bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold text-lg increment"
                                    data-id="{{ $news->id }}"
                                    data-stock="{{ $stock }}">+</button>
                        </div>
                    </div>

                    {{-- Кнопки --}}
                    <div class="mt-auto flex gap-3">
                        <a href="#"
                           class="w-1/2 text-sm text-center bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-2.5 rounded-lg transition shadow add-to-cart"
                           data-id="{{ $news->id }}"
                           data-title="{{ $news->title }}"
                           data-price="{{ $price }}"
                           data-stock="{{ $stock }}">
                            🛒 В корзину
                        </a>
                        <a href="{{ route('news.show', $news->slug) }}"
                           class="w-1/2 text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow">
                            Подробнее →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500 select-none">Нет товаров.</p>
    @endif
</div>

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
