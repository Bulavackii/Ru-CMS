@extends('layouts.admin')

@section('title', 'Новое сообщение')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0 space-y-6">

        {{-- 🔙 Назад --}}
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center hover:text-blue-600 dark:hover:text-blue-400 transition">
                <i class="fas fa-arrow-left mr-1"></i> Назад к сообщениям
            </a>
        </div>

        {{-- 📝 Форма создания сообщения --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow px-6 py-8 space-y-6">

            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                📝 Новое сообщение
            </h1>

            {{-- 🔴 Ошибки валидации --}}
            @if ($errors->any())
                <div class="bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded shadow">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.messages.store') }}" class="space-y-6">
                @csrf

                {{-- 👤 Кому --}}
                <div>
                    <label for="to_user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        👤 Получатель *
                    </label>
                    <select name="to_user_id" id="to_user_id" required
                            class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('to_user_id') border-red-500 @enderror">
                        <option value="">-- Выберите администратора --</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected(old('to_user_id') == $admin->id)>
                                {{ $admin->name }} ({{ $admin->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('to_user_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 🏷️ Тема --}}
                <div>
                    <label for="subject" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        🏷️ Тема сообщения *
                    </label>
                    <input type="text" name="subject" id="subject" required
                           value="{{ old('subject') }}"
                           class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('subject') border-red-500 @enderror">
                    @error('subject')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 💬 Текст --}}
                <div>
                    <label for="body" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        💬 Сообщение *
                    </label>
                    <textarea name="body" id="body" rows="6" required
                              placeholder="Введите сообщение для других админов..."
                              class="w-full border rounded-lg px-4 py-3 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 @error('body') border-red-500 @enderror">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 📤 Кнопка отправки --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow transition-all duration-200">
                        <i class="fas fa-paper-plane"></i> Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
