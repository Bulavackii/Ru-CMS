@extends('layouts.admin')

@section('title', 'Новое сообщение')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- Назад --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.messages.index') }}"
               class="inline-flex items-center text-sm text-gray-600 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Назад к сообщениям
            </a>
        </div>

        {{-- Форма --}}
        <div class="bg-white shadow rounded-xl p-6 border border-gray-200 space-y-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                📝 Новое сообщение
            </h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.messages.store') }}" class="space-y-5">
                @csrf

                {{-- Кому --}}
                <div>
                    <label for="to_user_id" class="block font-semibold text-gray-700 mb-1">Получатель *</label>
                    <select name="to_user_id" id="to_user_id" required
                            class="w-full border rounded px-4 py-3 focus:ring-2 focus:ring-blue-400 @error('to_user_id') border-red-500 @enderror">
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

                {{-- Тема --}}
                <div>
                    <label for="subject" class="block font-semibold text-gray-700 mb-1">Тема сообщения *</label>
                    <input type="text" name="subject" id="subject" required
                           value="{{ old('subject') }}"
                           class="w-full border rounded px-4 py-3 focus:ring-2 focus:ring-blue-400 @error('subject') border-red-500 @enderror">
                    @error('subject')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Сообщение --}}
                <div>
                    <label for="body" class="block font-semibold text-gray-700 mb-1">Текст сообщения *</label>
                    <textarea name="body" id="body" rows="6" required
                              placeholder="Введите сообщение для других админов..."
                              class="w-full border rounded px-4 py-3 focus:ring-2 focus:ring-blue-400 @error('body') border-red-500 @enderror">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Кнопка --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded shadow transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
