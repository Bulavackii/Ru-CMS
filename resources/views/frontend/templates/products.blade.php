<div class="my-12 max-w-screen-xl mx-auto px-4">
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
                    $mediaSrc = $news->cover
                        ? asset('storage/' . $news->cover)
                        : (
                            preg_match('/<video[^>]*src="([^"]+)"/i', $news->content, $videoMatch)
                                ? $videoMatch[1]
                                : (
                                    preg_match('/<source[^>]*src="([^"]+)"/i', $news->content, $sourceMatch)
                                        ? $sourceMatch[1]
                                        : (
                                            preg_match('/<img[^>]+src="([^">]+)"/i', $news->content, $imgMatch)
                                                ? $imgMatch[1]
                                                : asset('images/no-image.png')
                                        )
                                )
                        );
                    $isVideo = \Illuminate\Support\Str::endsWith($mediaSrc, ['.mp4', '.webm']);
                    $price = $news->price ?? null;
                    $stock = $news->stock ?? null;
                    $isPromo = $news->is_promo ?? false;
                    $isNew = $news->created_at->gt(now()->subDays(7));
                @endphp

                {{-- Карточка товара --}}
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

                    {{-- Метки акций и новинок --}}
                    @if ($isPromo)
                        <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-red-600 text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                            🔥 STOCK
                        </div>
                    @elseif ($isNew)
                        <div class="absolute -top-3 right-3 z-10 bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse select-none">
                            🆕 Новинка
                        </div>
                    @endif

                    {{-- Обложка или видео --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline controls>
                                <source src="{{ $mediaSrc }}" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $mediaSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Название товара --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-1 leading-tight break-words break-all line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition" title="{{ $news->title }}">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата добавления --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1 select-none">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Краткое описание --}}
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

                    {{-- Количество с кнопками плюс-минус --}}
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

                    {{-- Кнопки "В корзину" и "Подробнее" --}}
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
    // Функция отображения всплывающего уведомления
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

    // Обновление счётчика корзины на сайте
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

    // Локальное обновление остатка после изменения количества
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

    // Получение актуального остатка с сервера
    function updateServerStock(productId) {
        fetch(`/product/${productId}/stock`)
            .then(res => res.json())
            .then(data => {
                const stockSpan = document.querySelector(`.stock-display[data-id='${productId}'] span`);
                if (stockSpan) {
                    stockSpan.textContent = data.stock;
                    // Обновляем атрибут data-stock у кнопки добавления в корзину
                    const btn = document.querySelector(`.add-to-cart[data-id='${productId}']`);
                    if (btn) {
                        btn.dataset.stock = data.stock;
                    }
                }
            });
    }

    // Обработчик кнопки "В корзину"
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
                updateServerStock(id); // обновим остаток с сервера после добавления
            }).catch(async error => {
                const msg = await error.json().then(e => e.message ?? 'Ошибка запроса').catch(() => 'Ошибка');
                showToast(msg, 'error');
            });
        });
    });

    // Кнопки увеличения количества
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

    // Кнопки уменьшения количества
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
