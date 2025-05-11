@extends('layouts.frontend')

@section('title', 'Личный кабинет')

@section('content')
    <h1 class="text-3xl font-extrabold text-center text-blue-900 mb-8">
        👤 Личный кабинет
    </h1>

    {{-- ✅ Сообщение об успехе --}}
    @if (session('success'))
        <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow text-center">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-black rounded-xl shadow-lg max-w-2xl mx-auto overflow-hidden">
        {{-- 🧾 Основной блок --}}
        <div class="p-6 space-y-3 text-sm text-gray-700">
            <div class="flex items-center gap-2">
                <i class="fas fa-user text-blue-600"></i>
                <span><strong>Имя:</strong> {{ $user->name }}</span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-envelope text-blue-600"></i>
                <span><strong>Email:</strong> {{ $user->email }}</span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-id-badge text-blue-600"></i>
                <span><strong>Тип пользователя:</strong> {{ $user->is_company ? 'Юридическое лицо' : 'Физическое лицо' }}</span>
            </div>
        </div>

        {{-- 🏢 Блок юр. лица --}}
        @if($user->is_company)
            <div class="bg-blue-50 border-t border-gray-200 px-6 py-4 space-y-3 text-sm text-gray-700">
                <div class="flex items-center gap-2">
                    <i class="fas fa-building text-indigo-600"></i>
                    <span><strong>Компания:</strong> {{ $user->company_name }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice text-indigo-600"></i>
                    <span><strong>ИНН:</strong> {{ $user->inn }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-indigo-600"></i>
                    <span><strong>ОГРН:</strong> {{ $user->ogrn }}</span>
                </div>
            </div>
        @endif

        {{-- 🔧 Действия --}}
        <div class="flex flex-col sm:flex-row justify-center gap-4 p-6 border-t border-gray-200 bg-gray-50">
            <a href="{{ route('dashboard.edit') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 text-center">
                ✏️ Редактировать
            </a>

            @if($user->is_company)
                <a href="{{ route('organization.edit') }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded shadow transition-transform transform hover:scale-105 text-center">
                    🏢 Редактировать
                </a>
            @endif
        </div>
    </div>
@endsection
