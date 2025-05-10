@extends('layouts.admin')

@section('title', 'Новости')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Список новостей</h1>
        <a href="{{ route('admin.news.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
            + Добавить новость
        </a>
    </div>

    {{-- 🧭 Фильтр по шаблонам --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        @php
            $icons = [
                'default' => '📰',
                'products' => '🛍️',
                'contacts' => '📇',
                'gallery' => '🖼️',
                'slideshow' => '🎞️',
                'faq' => '❓',
                'reviews' => '⭐',
                'test' => '🧪',
                'test2' => '⚙️',
            ];
            $current = request('template');
        @endphp

        <a href="{{ route('admin.news.index') }}"
            class="px-3 py-1.5 rounded-full text-sm font-medium border transition {{ !$current ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
            📂 Все
        </a>

        @foreach ($templates as $key => $label)
            <a href="{{ route('admin.news.index', ['template' => $key]) }}"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition {{ $current === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                {{ $icons[$key] ?? '🔖' }} {{ $label }}
            </a>
        @endforeach
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.bulk') }}" id="bulk-form">
        @csrf
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <select name="action" class="border rounded px-3 py-2 text-sm">
                <option value="">Выберите действие</option>
                <option value="delete">Удалить выбранные</option>
                <option value="edit">Массовое редактирование</option>
            </select>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Применить</button>
        </div>

        <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
            <thead class="bg-gray-100 border-b text-sm text-gray-600">
                <tr>
                    <th class="px-4 py-3"><input type="checkbox" id="check-all"></th>
                    <th>Заголовок</th>
                    <th>Категории</th>
                    <th>Meta Title</th>
                    <th>Ключевые слова</th>
                    <th>Meta Description</th>
                    <th>Товар</th>
                    <th>Статус</th>
                    <th>Шаблон</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($newsList as $news)
                    <tr>
                        <td><input type="checkbox" name="selected[]" value="{{ $news->id }}" class="row-checkbox"></td>
                        <td>{{ $news->title }}</td>
                        <td>@foreach ($news->categories as $cat)<span>{{ $cat->title }}</span>@endforeach</td>
                        <td>{{ Str::limit($news->meta_title, 60) }}</td>
                        <td>{{ Str::limit($news->meta_keywords, 60) }}</td>
                        <td>{{ Str::limit($news->meta_description, 100) }}</td>
                        <td>{{ $news->template === 'products' ? number_format($news->price, 2, ',', ' ') . ' ₽' : '—' }}</td>
                        <td>{{ $news->published ? '✅' : '🕒' }}</td>
                        <td>{{ $news->template }}</td>
                        <td><a href="{{ route('admin.news.edit', $news->id) }}">✏️</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $newsList->links() }}
    </form>

    <script>
    document.getElementById('check-all')?.addEventListener('change', e =>
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked)
    );

    document.getElementById('bulk-form')?.addEventListener('submit', function(e) {
        const form = this;
        const action = form.querySelector('[name="action"]').value;
        const selected = [...form.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);

        if (!action) {
            e.preventDefault();
            alert('Выберите действие!');
            return;
        }

        if (!selected.length) {
            e.preventDefault();
            alert('Выберите хотя бы одну новость.');
            return;
        }

        if (action === 'edit') {
            e.preventDefault();
            const url = `{{ route('admin.news.bulk.edit') }}?ids=${selected.join(',')}`;
            window.location.href = url;
        }
    });
</script>

@endsection
