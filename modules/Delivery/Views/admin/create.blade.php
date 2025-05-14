@extends('layouts.admin')

@section('title', 'Добавить метод доставки')

@section('content')
    <h1 class="text-2xl font-bold mb-6">➕ Добавить метод доставки</h1>

    <form method="POST" action="{{ route('admin.delivery.store') }}" class="space-y-6">
        @include('Delivery::admin.form')
        <button type="submit"
                class="bg-black text-white px-6 py-2 rounded shadow hover:bg-gray-800 transition">
            💾 Сохранить
        </button>
    </form>
@endsection
