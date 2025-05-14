@extends('layouts.admin')

@section('title', 'Методы доставки')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">🚚 Методы доставки</h1>
        <a href="{{ route('admin.delivery.create') }}"
           class="inline-flex items-center gap-2 bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-semibold transition">
            <i class="fas fa-plus"></i> Добавить метод
        </a>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 border rounded shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Название</th>
                    <th class="px-6 py-3 text-left font-semibold">Описание</th>
                    <th class="px-6 py-3 text-left font-semibold">Цена</th>
                    <th class="px-6 py-3 text-center font-semibold">Активен</th>
                    <th class="px-6 py-3 text-center font-semibold">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($methods as $method)
                    <tr>
                        <td class="px-6 py-4">{{ $method->title }}</td>
                        <td class="px-6 py-4">{{ $method->description }}</td>
                        <td class="px-6 py-4">{{ number_format($method->price, 2, ',', ' ') }} ₽</td>
                        <td class="px-6 py-4 text-center">
                            @if ($method->active)
                                ✅
                            @else
                                ❌
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <a href="{{ route('admin.delivery.edit', $method) }}"
                               class="text-blue-600 hover:text-blue-800">✏️</a>
                            <form action="{{ route('admin.delivery.destroy', $method) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm('Удалить метод доставки?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
