@extends('layouts.admin')

@section('title', 'Добавить способ оплаты')

@section('content')
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">➕ Новый способ оплаты</h1>

    {{-- 🧾 Форма создания способа оплаты --}}
    <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-6 max-w-xl bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700">
        @csrf

        {{-- 🔧 Поля формы (вынесены отдельно) --}}
        @include('Payments::admin.partials.form')

        {{-- 💾 Кнопка сохранения --}}
        <div class="text-right">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2.5 rounded-md shadow font-semibold text-sm transition">
                <i class="fas fa-save text-xs"></i> Сохранить
            </button>
        </div>
    </form>
@endsection
