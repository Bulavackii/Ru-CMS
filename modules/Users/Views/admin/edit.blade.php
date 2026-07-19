@extends('layouts.admin')

@section('title', 'Редактировать пользователя')

@section('content')
    {{-- 🧩 Заголовок страницы --}}
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white">
            ✏️ Редактировать пользователя
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $user->name }} ({{ $user->email }})</p>
    </div>

    {{-- 📝 Форма редактирования --}}
    <div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-xl shadow-md">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 🧑 Имя --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-user mr-1"></i> Имя
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                       placeholder="Введите имя"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('name')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 📧 Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-envelope mr-1"></i> Email
                </label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                       placeholder="example@domain.com"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 🔐 Администратор --}}
            <div class="flex items-center">
                <input type="checkbox" id="is_admin" name="is_admin" value="1" 
                       {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                       class="rounded border-gray-400 dark:border-gray-600">
                <label for="is_admin" class="ml-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-shield-alt mr-1"></i> Администратор
                </label>
            </div>

            {{-- 📞 Телефон --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    <i class="fas fa-phone mr-1"></i> Телефон
                </label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                       placeholder="+7 (999) 123-45-67"
                       class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                @error('phone')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 📍 Адрес --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                        Почтовый индекс
                    </label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                           placeholder="123456"
                           class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label for="region" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                        Регион
                    </label>
                    <input type="text" id="region" name="region" value="{{ old('region', $user->region) }}"
                           placeholder="Московская область"
                           class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                        Город
                    </label>
                    <input type="text" id="city" name="city" value="{{ old('city', $user->city) }}"
                           placeholder="Москва"
                           class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                        Адрес
                    </label>
                    <input type="text" id="address" name="address" value="{{ old('address', $user->address) }}"
                           placeholder="ул. Примерная, д. 1"
                           class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-black">
                </div>
            </div>

            {{-- 🔐 Роли (только для не-админов) --}}
            @if(!$user->is_admin)
            <div>
                <label class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">
                    <i class="fas fa-user-tag mr-1"></i> Роли
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-3">
                    @foreach($roles as $role)
                        <label class="flex items-center">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                   {{ $user->roles->contains($role->id) ? 'checked' : '' }}
                                   class="rounded border-gray-400 dark:border-gray-600">
                            <span class="ml-2 text-sm text-gray-800 dark:text-gray-200">
                                {{ $role->name }}
                                @if($role->description)
                                    <span class="text-gray-500 dark:text-gray-400">({{ $role->description }})</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            @else
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-1"></i> Администраторы имеют все права доступа
                </div>
            @endif

            {{-- 🕹️ Кнопки --}}
            <div class="flex justify-between items-center pt-4">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
                <div class="flex gap-3">
                    <a href="{{ route('admin.users.password.edit', $user) }}"
                       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md shadow text-sm font-semibold transition">
                        <i class="fas fa-key"></i> Сменить пароль
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-5 py-2 rounded-md shadow-md text-sm font-semibold transition">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection




