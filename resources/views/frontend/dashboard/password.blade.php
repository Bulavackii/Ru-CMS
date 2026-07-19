@extends('layouts.frontend')

@section('title', 'Смена пароля')

@section('content')
    <div class="max-w-xl mx-auto bg-white border border-gray-300 rounded-xl shadow-lg p-6 space-y-6">
        <h1 class="text-2xl font-bold text-center text-blue-900">🔒 Смена пароля</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded shadow text-sm">
                 {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded shadow text-sm">
                 {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-dashboard.input name="current_password" type="password" label="Текущий пароль" required />
            <x-dashboard.input name="new_password" type="password" label="Новый пароль" required />
            <x-dashboard.input name="new_password_confirmation" type="password" label="Подтверждение пароля" required />

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow text-sm font-semibold">
                💾 Сменить пароль
            </button>
        </form>
    </div>
@endsection
