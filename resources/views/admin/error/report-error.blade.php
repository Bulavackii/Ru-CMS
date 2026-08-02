@extends('layouts.admin')

@section('title', __('admin.system.er_title'))

@section('content')
{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex items-center gap-3">
    <span class="admin-icon-badge"><i class="fas fa-bug"></i></span>
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.system.er_title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.system.er_subtitle') }}</p>
    </div>
</div>

@unless($mailReady)
    {{-- Раньше это выяснялось только после отправки — падением на 500. --}}
    <div class="er-warn mb-4">
        <i class="fas fa-envelope-circle-check"></i>
        <div>
            <p class="font-semibold">{{ __('admin.system.er_mail_off') }}</p>
            <p>{{ __('admin.system.er_mail_off_hint') }}</p>
        </div>
    </div>
@endunless

<form action="{{ route('admin.error.report.send') }}" method="POST" enctype="multipart/form-data"
      class="admin-card p-5 er-form" x-data="{ file: '' }">
    @csrf

    {{-- ── Сообщение ── --}}
    <div class="mb-4">
        <label for="message" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.system.er_message') }} <span class="text-red-500">*</span>
        </label>

        <textarea id="message" name="message" rows="6" required
                  placeholder="{{ __('admin.system.er_message_ph') }}"
                  class="w-full border px-3 py-2 text-sm dark:bg-gray-800 resize-y
                         @error('message') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">{{ old('message') }}</textarea>

        @error('message')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @else
            <p class="admin-hint mt-1">{{ __('admin.system.er_message_hint') }}</p>
        @enderror
    </div>

    {{-- ── E-mail ── --}}
    <div class="mb-4">
        <label for="email" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.system.er_email') }}
        </label>

        <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}"
               placeholder="{{ __('admin.system.er_email_ph') }}"
               class="w-full border px-3 py-2 text-sm dark:bg-gray-800
                      @error('email') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">

        @error('email')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @else
            <p class="admin-hint mt-1">{{ __('admin.system.er_email_hint') }}</p>
        @enderror
    </div>

    {{-- ── Вложение ── --}}
    <div class="mb-4">
        <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.system.er_file') }}
        </span>

        <label for="file" class="er-drop" :class="file && 'er-drop--filled'">
            <i class="fas fa-cloud-arrow-up"></i>
            <span x-text="file || @js(__('admin.system.er_file_pick'))"></span>
        </label>

        <input type="file" id="file" name="file" class="hidden"
               accept=".jpg,.jpeg,.png,.gif,.webp,.txt,.log,.pdf"
               @change="file = $event.target.files.length ? $event.target.files[0].name : ''">

        <div class="flex flex-wrap items-center gap-3 mt-1">
            <p class="admin-hint">{{ __('admin.system.er_file_hint') }}</p>

            <button type="button" x-show="file" x-cloak
                    class="text-sm font-semibold text-red-600 hover:underline"
                    @click="file = ''; document.getElementById('file').value = ''">
                {{ __('admin.system.er_file_clear') }}
            </button>
        </div>

        @error('file')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Что уходит вместе с письмом — чтобы это не было сюрпризом. --}}
    <div class="er-context mb-4">
        <i class="fas fa-circle-info"></i>
        <span>
            {{ __('admin.system.er_context') }}:
            {{ __('admin.system.er_ctx_user') }} · {{ __('admin.system.er_ctx_ip') }} ·
            {{ __('admin.system.er_ctx_ua') }} · {{ __('admin.system.er_ctx_url') }}.
        </span>
    </div>

    <div class="flex justify-end">
        <button type="submit" x-data="{ sent: false }" @click="sent = true" :disabled="sent"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                       px-5 py-2.5 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-paper-plane"></i>
            <span x-text="sent ? @js(__('admin.system.er_sending')) : @js(__('admin.system.er_send'))"></span>
        </button>
    </div>
</form>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .er-form{ max-width:46rem; margin-inline:auto }

    .er-drop{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem;
              border:2px dashed #cbd5e1; padding:1.5rem 1rem; cursor:pointer; text-align:center;
              font-size:.875rem; color:#64748b; transition:border-color .15s, color .15s }
    .er-drop:hover{ border-color:#818cf8; color:#4f46e5 }
    .er-drop i{ font-size:1.5rem; color:#94a3b8 }
    .er-drop--filled{ border-style:solid; border-color:#818cf8; color:#4f46e5 }
    .er-drop--filled i{ color:#6366f1 }

    .er-context{ display:flex; gap:.6rem; align-items:flex-start; font-size:.8rem; color:#475569;
                 background:#f8fafc; border:1px solid #eef2f7; padding:.65rem .85rem }

    .er-warn{ display:flex; gap:.75rem; align-items:flex-start; font-size:.875rem;
              color:#92400e; background:#fffbeb; border:1px solid #fde68a; padding:.85rem 1rem }
    .er-warn i{ font-size:1.1rem; margin-top:.1rem }

    @media (prefers-color-scheme: dark){
        .er-context{ background:transparent; border-color:#374151; color:#cbd5e1 }
        .er-drop{ border-color:#4b5563 }
    }
</style>
@endpush
