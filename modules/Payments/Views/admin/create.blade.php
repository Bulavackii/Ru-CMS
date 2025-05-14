@extends('layouts.admin')

@section('title', 'Добавить способ оплаты')

@section('content')
    <h1 class="text-2xl font-bold mb-6">➕ Новый способ оплаты</h1>

    <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-6 max-w-xl">
        @csrf
        @include('Payments::admin.partials.form')
        <button type="submit" class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow">💾 Сохранить</button>
    </form>
@endsection
