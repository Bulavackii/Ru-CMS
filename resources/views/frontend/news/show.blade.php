@extends('layouts.frontend')

@section('title', $news->title)

@section('content')
    <article class="max-w-3xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-lg px-6 py-8 transition-all duration-300 overflow-hidden">
        {{-- Заголовок --}}
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight break-words mb-6 text-center">
            {{ $news->title }}
        </h1>

        {{-- Категории --}}
        @if ($news->categories->isNotEmpty())
            <div class="mb-4 text-sm flex flex-wrap gap-2 justify-center">
                @foreach ($news->categories as $category)
                    <a href="{{ url('/?category=' . $category->id) }}"
                       class="inline-block bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-medium hover:underline transition">
                        {{ $category->title }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Контент --}}
        <div class="prose prose-sm sm:prose lg:prose-lg max-w-none news-content text-gray-800 dark:text-gray-100 mb-8">
            {!! $news->content !!}
        </div>

        {{-- Блок с ценой, остатком, количеством и кнопкой --}}
        @if($news->price)
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-xl mb-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                    <div class="space-y-3">
                        <div class="bg-green-100 text-green-900 px-4 py-2 rounded-full font-semibold shadow-sm text-sm inline-block">
                            💰 {{ number_format($news->price, 2, ',', ' ') }} ₽
                        </div>
                        @if (!is_null($news->stock))
                            <div class="bg-yellow-100 text-yellow-900 px-4 py-2 rounded-full font-semibold shadow-sm text-sm inline-block stock-display" data-id="{{ $news->id }}">
                                📦 Осталось: <span>{{ $news->stock }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3 flex flex-col items-end">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Кол-во:</span>
                            <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded overflow-hidden">
                                <button type="button"
                                        class="px-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg decrement"
                                        data-id="{{ $news->id }}">−</button>
                                <input type="text"
                                       id="qty-{{ $news->id }}"
                                       value="1"
                                       readonly
                                       class="w-12 text-center border-x border-gray-200 dark:border-gray-600 text-sm qty-input"
                                       data-id="{{ $news->id }}">
                                <button type="button"
                                        class="px-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 font-bold text-lg increment"
                                        data-id="{{ $news->id }}"
                                        data-stock="{{ $news->stock }}">+</button>
                            </div>
                        </div>

                        <button type="button"
                                class="add-to-cart bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-2 px-4 rounded-lg text-sm shadow transition"
                                data-id="{{ $news->id }}"
                                data-title="{{ $news->title }}"
                                data-price="{{ $news->price }}"
                                data-stock="{{ $news->stock }}">
                            🛒 В корзину
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Назад --}}
        <div class="text-center">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg font-medium text-sm shadow-md transition">
                ⬅️ На главную
            </a>
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
</script>
@endpush

@push('styles')
<style>
    .news-content {
        word-break: break-word;
        overflow-wrap: anywhere;
        hyphens: auto;
        line-height: 1.75;
        font-size: 1.05rem;
        text-align: justify;
    }

    .news-content * {
        max-width: 100% !important;
        box-sizing: border-box;
    }

    .news-content img,
    .news-content video,
    .news-content iframe,
    .news-content embed,
    .news-content object {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 1.5rem auto;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .news-content pre {
        white-space: pre-wrap;
        word-break: break-word;
        background-color: #f9fafb;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
    }

    .news-content table {
        width: 100%;
        display: block;
        overflow-x: auto;
    }

    @media screen and (max-width: 640px) {
        .news-content {
            font-size: 0.95rem;
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    }
</style>
@endpush

