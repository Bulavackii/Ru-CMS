@extends('layouts.frontend-install')

@section('accent', '#16a34a')

@section('content')
<div class="w-full max-w-lg max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 07 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.waiting.title') }}</h1>
                <p class="ins-head__about">{{ __('install.waiting.about') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 py-4 space-y-3">
            <div class="ins-wait__bar"><span></span></div>

            <p class="ins-help__text">{{ __('install.waiting.note') }}</p>
        </div>
    </div>
</div>

{{-- Переход обратно на этот же шаг — уже после того, как сервер разработки
     успел перезапуститься. Задержку считает контроллер. --}}
<noscript>
    <meta http-equiv="refresh" content="{{ max(1, (int) ceil($delay / 1000)) }};url={{ route('install.finish') }}">
</noscript>
<script>
    setTimeout(function () {
        window.location.replace(@js(route('install.finish')));
    }, @js($delay));
</script>

@push('styles')
<style>
    /* Полоса без процентов: сколько осталось — неизвестно, и врать шкалой
       не надо. Показывает только то, что работа идёт. */
    .ins-wait__bar{ position:relative; height:4px; overflow:hidden;
        background:var(--surface-2,#f7f8fc); border:1px solid var(--surface-bd,#e3e6ee) }
    .ins-wait__bar span{ position:absolute; inset:0 auto 0 0; width:35%;
        background:var(--accent); animation:ins-wait 1.1s ease-in-out infinite }
    @keyframes ins-wait{ 0%{ left:-35% } 100%{ left:100% } }
</style>
@endpush
@endsection
