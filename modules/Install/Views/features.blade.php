@extends('layouts.frontend-install')

@section('accent', '#8b5cf6')

@section('content')
<div class="w-full max-w-4xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка полосой, как у шагов. Прежде здесь была единственная в
             мастере цветная шапка-градиент во всю ширину — страница
             выбивалась из ряда. Номера шага нет: страница необязательная
             и в счётчике шагов не участвует. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.optional') }} · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.features.title') }}</h1>
                <p class="ins-head__about">{{ __('install.features.subtitle') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-3">
            <div class="ins-feats ins-feats--cards">
                @foreach ($features as $feature)
                    <div class="ins-feat ins-feat--card {{ ($feature['highlight'] ?? false) ? 'ins-feat--key' : '' }}"
                         data-tip="{{ $feature['description'] }}">
                        @if ($feature['highlight'] ?? false)
                            <i data-lucide="star" class="w-3 h-3 ins-feat__star"
                               data-tip="{{ __('install.features.key_tip') }}" data-tip-pos="bottom"></i>
                        @endif

                        <span class="ins-feat__ico"><i data-lucide="{{ $feature['icon'] }}" class="w-4 h-4"></i></span>
                        <span class="ins-feat__title break-words">{{ $feature['title'] }}</span>
                        <p class="ins-feat__text">{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="ins-callout">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5"></i>
                <span>{{ __('install.features.hint') }}</span>
            </p>
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0 flex items-center justify-between gap-2">
            <a href="{{ route('install.requirements') }}" class="ins-back">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.features.back') }}
            </a>

            <a href="{{ route('install.database') }}" class="ins-act ins-act--go">
                <span>{{ __('install.features.continue') }}</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>
@endsection
