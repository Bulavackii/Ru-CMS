@extends('layouts.admin')

@section('title', 'Управление модулями')
@section('header', 'Модули системы')

@section('content')

    {{-- Уведомления --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <h2 class="text-lg font-bold mb-4">Список установленных модулей</h2>

    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr class="border-b">
                <th class="text-left px-4 py-2">Название</th>
                <th class="text-left px-4 py-2">Версия</th>
                <th class="text-left px-4 py-2">Активен</th>
                <th class="text-left px-4 py-2">Действие</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($modules as $module)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $module->name }}</td>
                    <td class="px-4 py-2">{{ $module->version }}</td>
                    <td class="px-4 py-2">
                        @if ($module->active)
                            <span class="text-green-600">Да</span>
                        @else
                            <span class="text-red-600">Нет</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.modules.toggle', $module->id) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                class="px-3 py-1 rounded text-white
                                {{ $module->active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' }}">
                                {{ $module->active ? 'Отключить' : 'Включить' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="my-8">

    <h2 class="text-lg font-bold mb-4">Установить модуль из ZIP</h2>

    <form method="POST" action="{{ route('admin.modules.install') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="file" name="module" accept=".zip" required class="border p-2">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Установить
        </button>
    </form>

@endsection
