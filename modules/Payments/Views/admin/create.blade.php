@extends('layouts.admin')

@section('title', __('admin.payments.create_title'))

@section('content')
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-credit-card"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.payments.create_title') }}</h1>
        </div>
    </div>

    <a href="{{ route('admin.payments.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> {{ __('admin.payments.to_list') }}
    </a>
</div>


<form action="{{ route('admin.payments.store') }}" method="POST">
    @csrf
    @include('Payments::admin.partials.form')
</form>
@endsection
