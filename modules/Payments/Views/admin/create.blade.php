@extends('layouts.admin')

@section('title', __('admin.payments.create_title'))

@section('content')
<div class="admin-accent-bar mb-0"></div>
{{-- Шапка в два ряда (.mh-*, общее определение в лейауте). --}}
<div class="admin-glass mh border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-3 mb-6">
    <div class="mh-row">
        <span class="admin-icon-badge"><i class="fas fa-credit-card"></i></span>
        <h1 class="mh-title text-xl font-bold text-gray-900 dark:text-white truncate">{{ __('admin.payments.create_title') }}</h1>
    </div>

    <div class="mh-row mh-row--sub">
        <span class="mh-facts"></span>

        <a href="{{ route('admin.payments.index') }}"
           class="mh-back inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition">
            <i class="fas fa-arrow-left"></i> {{ __('admin.payments.to_list') }}
        </a>
    </div>
</div>


<form action="{{ route('admin.payments.store') }}" method="POST">
    @csrf
    @include('Payments::admin.partials.form')
</form>
@endsection
