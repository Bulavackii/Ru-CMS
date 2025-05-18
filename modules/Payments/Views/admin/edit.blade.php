@extends('layouts.admin')

@section('title', 'Редактировать способ оплаты')

@section('content')
    {{-- 📝 Заголовок --}}
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
        ✏️ Редактирование: <span class="text-black dark:text-white">{{ $method->title }}</span>
    </h1>

    {{-- 🧾 Форма редактирования метода оплаты --}}
    <form action="{{ route('admin.payments.update', $method->id) }}" method="POST"
          class="space-y-6 max-w-xl bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700">
        @csrf
        @method('PUT')

        {{-- 🔧 Поля формы --}}
        @include('Payments::admin.partials.form', ['method' => $method])

        {{-- 💾 Кнопка сохранения --}}
        <div class="text-right">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-6 py-2.5 rounded-md shadow font-semibold text-sm transition">
                <i class="fas fa-save text-xs"></i> Обновить
            </button>
        </div>
    </form>
@endsection
