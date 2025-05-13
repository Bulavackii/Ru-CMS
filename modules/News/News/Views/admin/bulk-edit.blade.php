@extends('layouts.admin')

@section('title', 'Массовое редактирование')

@section('content')
    <h1 class="text-2xl font-bold mb-6">🛠️ Массовое редактирование новостей</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 mb-6 rounded shadow">
            <strong>Ошибка:</strong> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.bulk.update') }}" class="space-y-10">
        @csrf

        @foreach ($news as $item)
            <div class="border border-gray-200 rounded-lg p-4 shadow-sm bg-white space-y-4">
                <input type="hidden" name="fields[{{ $item->id }}][id]" value="{{ $item->id }}">

                <h2 class="font-semibold text-lg mb-2">🆔 Новость #{{ $item->id }} — {{ $item->title }}</h2>

                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">📝 Заголовок</label>
                        <input type="text" name="fields[{{ $item->id }}][title]" value="{{ $item->title }}"
                               class="w-full border border-gray-300 rounded px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">🔖 Meta Title</label>
                        <input type="text" name="fields[{{ $item->id }}][meta_title]" value="{{ $item->meta_title }}"
                               class="w-full border border-gray-300 rounded px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">🔑 Ключевые слова</label>
                        <input type="text" name="fields[{{ $item->id }}][meta_keywords]" value="{{ $item->meta_keywords }}"
                               class="w-full border border-gray-300 rounded px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">📝 Meta Description</label>
                        <textarea name="fields[{{ $item->id }}][meta_description]" rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2">{{ $item->meta_description }}</textarea>
                    </div>

                    @if ($item->template === 'products')
                        <div>
                            <label class="block font-semibold mb-1">💰 Цена</label>
                            <input type="number" step="0.01" name="fields[{{ $item->id }}][price]" value="{{ $item->price }}"
                                   class="w-full border border-gray-300 rounded px-4 py-2">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">📦 Остаток</label>
                            <input type="number" name="fields[{{ $item->id }}][stock]" value="{{ $item->stock }}"
                                   class="w-full border border-gray-300 rounded px-4 py-2">
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="pt-6">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow transition">
                💾 Сохранить изменения
            </button>
        </div>
    </form>
@endsection
