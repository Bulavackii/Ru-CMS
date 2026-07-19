@extends('layouts.frontend')

@section('title', 'Организация')

@section('content')
    <h1 class="text-2xl font-bold mb-6 text-center">🏢 Данные организации</h1>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded shadow-sm">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('organization.update') }}" class="space-y-5 bg-white border border-black p-6 rounded shadow max-w-xl mx-auto">
        @csrf
        @method('PUT')

        {{-- 🏛️ Название компании --}}
        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700">Наименование организации</label>
            <input id="company_name" name="company_name" type="text"
                   value="{{ old('company_name', $user->company_name) }}"
                   required
                   class="mt-1 block w-full border-gray-300 rounded shadow-sm px-4 py-2">
        </div>

        {{-- 🔢 ИНН --}}
        <div>
            <label for="inn" class="block text-sm font-medium text-gray-700">ИНН</label>
            <input id="inn" name="inn" type="text"
                   value="{{ old('inn', $user->inn) }}"
                   required
                   class="mt-1 block w-full border-gray-300 rounded shadow-sm px-4 py-2">
        </div>

        {{-- 📄 ОГРН --}}
        <div>
            <label for="ogrn" class="block text-sm font-medium text-gray-700">ОГРН</label>
            <input id="ogrn" name="ogrn" type="text"
                   value="{{ old('ogrn', $user->ogrn) }}"
                   required
                   class="mt-1 block w-full border-gray-300 rounded shadow-sm px-4 py-2">
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow transition-transform transform hover:scale-105">
                💾 Сохранить
            </button>
        </div>
    </form>
@endsection
