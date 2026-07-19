@extends('layouts.admin')

@section('title', 'Управление отзывами')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">📝 Управление отзывами</h1>

    {{-- Фильтры --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-4">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <select name="status" class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <option value="">Все статусы</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ожидающие</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Одобренные</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклоненные</option>
            </select>

            <select name="rating" class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <option value="">Все оценки</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }}⭐</option>
                @endfor
            </select>

            <select name="item_type" class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <option value="">Все типы</option>
                <option value="product" {{ request('item_type') == 'product' ? 'selected' : '' }}>Товары</option>
                <option value="news" {{ request('item_type') == 'news' ? 'selected' : '' }}>Новости</option>
            </select>

            <select name="sort" class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Сначала новые</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Высокий рейтинг</option>
                <option value="rating_low" {{ request('sort') == 'rating_low' ? 'selected' : '' }}>Низкий рейтинг</option>
            </select>

            <input type="text" name="search" placeholder="Поиск..." value="{{ request('search') }}"
                   class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Фильтр</button>
            <a href="{{ route('admin.reviews.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Сброс</a>
            <a href="{{ route('admin.reviews.stats') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">📊 Статистика</a>
        </form>
    </div>

    {{-- Массовые операции --}}
    <form method="POST" action="{{ route('admin.reviews.bulkModerate') }}" id="bulkForm" class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
        @csrf
        <div class="flex items-center gap-3 flex-wrap">
            <select name="action" id="bulkAction" class="border rounded px-3 py-2 bg-white dark:bg-gray-700" required>
                <option value="">Выберите действие...</option>
                <option value="approve">Одобрить</option>
                <option value="reject">Отклонить</option>
                <option value="delete">Удалить</option>
            </select>
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition" disabled id="bulkSubmit">
                Применить к выбранным
            </button>
            <span class="text-sm text-gray-600 dark:text-gray-400" id="selectedCount">Выбрано: 0</span>
        </div>
    </form>

    {{-- Список отзывов --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-left">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-400">
                    </th>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Автор</th>
                    <th class="px-4 py-2 text-left">Объект</th>
                    <th class="px-4 py-2 text-left">Оценка</th>
                    <th class="px-4 py-2 text-left">Текст</th>
                    <th class="px-4 py-2 text-left">Статус</th>
                    <th class="px-4 py-2 text-left">Дата</th>
                    <th class="px-4 py-2 text-center">Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-2">
                        <input type="checkbox" name="review_ids[]" value="{{ $review->id }}" class="review-checkbox rounded border-gray-400">
                    </td>
                    <td class="px-4 py-2">{{ $review->id }}</td>
                    <td class="px-4 py-2">
                        {{ $review->name ?? $review->user?->name ?? 'Гость' }}
                    </td>
                    <td class="px-4 py-2">
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded">{{ $review->item_type }}</span>
                        #{{ $review->item_id }}
                    </td>
                    <td class="px-4 py-2">
                        <span class="font-bold text-yellow-600">{{ $review->rating }}⭐</span>
                    </td>
                    <td class="px-4 py-2 max-w-xs truncate" title="{{ $review->content }}">
                        {{ $review->title ? $review->title . ': ' : '' }}
                        {{ Str::limit($review->content, 50) }}
                    </td>
                    <td class="px-4 py-2">
                        @if($review->status === 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Ожидает</span>
                        @elseif($review->status === 'approved')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Одобрен</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Отклонен</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs">{{ $review->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <a href="{{ route('admin.reviews.show', $review->id) }}"
                               class="text-blue-600 hover:underline text-xs" title="Просмотр">👁️</a>

                            @if($review->status === 'pending')
                            <form method="POST" action="{{ route('admin.reviews.moderate', $review->id) }}" class="inline moderate-form">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="text-green-600 hover:underline text-xs" title="Одобрить">✅</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reviews.moderate', $review->id) }}" class="inline moderate-form">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="text-red-600 hover:underline text-xs" title="Отклонить">❌</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('admin.reviews.moderate', $review->id) }}" class="inline"
                                  onsubmit="return confirm('Удалить отзыв #{{ $review->id }}?')">
                                @csrf
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-red-700 hover:underline text-xs" title="Удалить">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        Отзывов не найдено
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($reviews->hasPages())
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>

    {{-- Скрипты для массовых операций и AJAX модерации --}}
    @push('scripts')
    <script>
        // Выбрать все
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.review-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkSubmit();
        });

        // Обновление кнопки при изменении чекбоксов
        document.querySelectorAll('.review-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkSubmit);
        });

        function updateBulkSubmit() {
            const checked = document.querySelectorAll('.review-checkbox:checked').length;
            const action = document.getElementById('bulkAction').value;
            document.getElementById('bulkSubmit').disabled = !(checked > 0 && action);
            document.getElementById('selectedCount').textContent = `Выбрано: ${checked}`;
        }

        document.getElementById('bulkAction')?.addEventListener('change', updateBulkSubmit);

        // Подтверждение массового удаления
        document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
            const action = document.getElementById('bulkAction').value;
            const checked = document.querySelectorAll('.review-checkbox:checked').length;
            
            if (action === 'delete' && !confirm(`Удалить ${checked} отзывов?`)) {
                e.preventDefault();
            }
        });

        // AJAX модерация
        document.querySelectorAll('.moderate-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '...';

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Ошибка');
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                })
                .catch(() => {
                    alert('Ошибка соединения');
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
    </script>
    @endpush

    {{-- Импорт/Экспорт --}}
    <div class="mt-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <h2 class="text-lg font-bold mb-3">📦 Импорт/Экспорт</h2>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.reviews.import') }}" enctype="multipart/form-data" class="flex gap-2 items-center">
                @csrf
                <input type="file" name="file" accept=".json" required class="border rounded px-2 py-1 text-sm">
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="merge" value="1" checked>
                    Объединять
                </label>
                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">Импорт</button>
            </form>

            <form method="GET" action="{{ route('admin.reviews.export') }}" class="flex gap-2 items-center">
                <input type="number" name="item_id" placeholder="ID (опционально)" class="border rounded px-2 py-1 text-sm w-32">
                <input type="text" name="item_type" placeholder="Тип (опционально)" class="border rounded px-2 py-1 text-sm w-32">
                <select name="format" class="border rounded px-2 py-1 text-sm bg-white dark:bg-gray-700">
                    <option value="json">JSON</option>
                    <option value="csv">CSV</option>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">Экспорт</button>
            </form>
        </div>
    </div>
</div>
@endsection
