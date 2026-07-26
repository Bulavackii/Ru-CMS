@extends('layouts.frontend')

@section('title', 'Редактирование профиля')

@section('content')
    <div class="max-w-2xl mx-auto bg-white border border-gray-300 rounded-xl shadow-lg p-6 space-y-6">
        <h1 class="text-3xl font-bold text-center text-blue-900">✏️ {{ __('frontend.account.edit_profile') }}</h1>

        {{-- 🔴 Ошибки --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 text-sm px-4 py-3 rounded text-center shadow">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        {{-- ✅ Форма --}}
        <form method="POST" action="{{ route('dashboard.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- 👤 Имя --}}
            <x-dashboard.input name="name" label="Имя" required :value="old('name', $user->name)" />

            {{-- 📍 Адрес --}}
            <x-dashboard.input name="address" label="Адрес" :value="old('address', $user->address)" />

            {{-- ☎️ Телефон --}}
            <x-dashboard.input name="phone" label="Телефон" :value="old('phone', $user->phone)" />

            {{-- 💬 Telegram --}}
            <x-dashboard.input name="telegram" label="Telegram" :value="old('telegram', $user->telegram)" />

            {{-- 💬 WhatsApp --}}
            <x-dashboard.input name="whatsapp" label="WhatsApp" :value="old('whatsapp', $user->whatsapp)" />

            {{-- 🌐 ВКонтакте --}}
            <x-dashboard.input name="vk" label="ВКонтакте" :value="old('vk', $user->vk)" />

            {{-- ✉️ Почтовый индекс --}}
            <x-dashboard.input name="zip" label="Почтовый индекс" :value="old('zip', $user->zip)" />

            {{-- 🏢 Чекбокс юр. лица --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_company" id="is_company"
                       {{ old('is_company', $user->is_company) ? 'checked' : '' }}
                       class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                <label for="is_company" class="text-sm text-gray-700">{{ __('frontend.account.legal_entity') }}</label>
            </div>

            {{-- 📋 Блок юр. лица --}}
            <div id="company-fields"
                 class="transition-all duration-300 ease-in-out space-y-4 {{ old('is_company', $user->is_company) ? '' : 'hidden' }}">
                <x-dashboard.input name="company_name" label="Организация" :value="old('company_name', $user->company_name)" />
                <x-dashboard.input name="inn" label="ИНН" :value="old('inn', $user->inn)" />
                <x-dashboard.input name="ogrn" label="ОГРН" :value="old('ogrn', $user->ogrn)" />
                <x-dashboard.input name="ceo" label="Генеральный директор" :value="old('ceo', $user->ceo)" />
                <x-dashboard.input name="address_legal" label="Юридический адрес" :value="old('address_legal', $user->address_legal)" />
                <x-dashboard.input name="address_actual" label="Фактический адрес" :value="old('address_actual', $user->address_actual)" />
                <x-dashboard.input name="okato" label="ОКАТО" :value="old('okato', $user->okato)" />
            </div>

            {{-- 💾 Кнопка --}}
            <div class="pt-6 flex justify-center">
                <button type="submit"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-6 rounded-full shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 focus:outline-none">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </form>
    </div>

    {{-- 💡 JS --}}
    <script>
        document.getElementById('is_company')?.addEventListener('change', function () {
            document.getElementById('company-fields')?.classList.toggle('hidden', !this.checked);
        });
    </script>
@endsection
