@extends('layouts.frontend-install')

@section('accent', '#16a34a')

@section('content')
<div class="w-full max-w-3xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        @php
            $reqTotal = count($requirements ?? []);
            $reqOk = collect($requirements ?? [])->filter()->count();
            $hasErrors = $reqOk < $reqTotal;
        @endphp

        {{-- Шапка шага — полосой, как на остальных шагах. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 02 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.requirements.title') }}</h1>
                <p class="ins-head__about">{{ __('install.about.requirements') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 pt-4 shrink-0">
            @include('Install::partials.steps', ['current' => 'requirements'])
        </div>

        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-3">
            {{-- Счёт проверок: сколько из скольких. Раньше это приходилось
                 пересчитывать глазами по списку. --}}
            <div>
                <div class="ins-score">
                    <span>{{ $hasErrors ? __('install.requirements.failed') : __('install.requirements.passed') }}</span>
                    <span class="ins-score__bar {{ $hasErrors ? 'is-bad' : '' }}">
                        <span style="width: {{ $reqTotal ? round($reqOk / $reqTotal * 100) : 0 }}%"></span>
                    </span>
                    <span>{{ $reqOk }}/{{ $reqTotal }}</span>
                </div>

                <div class="ins-reqs">
                    @foreach ($requirements as $label => $ok)
                        @php
                            // Ключи требований техничные и стабильные (их формирует
                            // контроллер), а вот расшифровка к ним — переводимая.
                            $reqTip = match ($label) {
                                'PHP >= 8.5'                  => __('install.requirements.tip_php', ['version' => PHP_VERSION]),
                                'PDO PostgreSQL (pdo_pgsql)'  => __('install.requirements.tip_pgsql'),
                                'Fileinfo'                    => __('install.requirements.tip_fileinfo'),
                                'Writable: storage/'          => __('install.requirements.tip_storage'),
                                'Writable: bootstrap/cache'   => __('install.requirements.tip_bootstrap'),
                                default                       => __('install.requirements.tip_default'),
                            };
                        @endphp
                        <div class="ins-req {{ $ok ? 'is-ok' : 'is-bad' }}" data-tip="{{ $reqTip }}">
                            <i data-lucide="{{ $ok ? 'check' : 'x' }}" class="w-3.5 h-3.5"></i>
                            <span class="ins-req__name">{{ $label }}</span>
                            <span class="ins-req__state">{{ $ok ? __('install.requirements.ok') : __('install.requirements.fail') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($hasErrors)
                <div class="ins-help">
                    <span class="ins-help__cap">
                        <i data-lucide="wrench" class="w-3.5 h-3.5"></i> {{ __('install.requirements.fix_title') }}
                    </span>
                    <ul class="ins-warn-list">
                        <li>{!! __('install.requirements.fix_php') !!}</li>
                        <li>{!! __('install.requirements.fix_ext') !!}</li>
                        <li>{!! __('install.requirements.fix_perms') !!}</li>
                        <li>{{ __('install.requirements.fix_retry') }}</li>
                    </ul>
                </div>
            @else
                <p class="ins-callout">
                    <i data-lucide="party-popper" class="w-3.5 h-3.5"></i>
                    <span>{{ __('install.requirements.all_ok') }}</span>
                </p>
            @endif

            <p class="ins-help__text">{{ __('install.requirements.subtitle') }}</p>
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0 flex items-center justify-between gap-2">
            <a href="{{ route('install.welcome') }}" class="ins-back">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ url()->current() }}" class="ins-act">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i> {{ __('install.requirements.recheck') }}
                </a>
                <a href="{{ route('install.database') }}"
                   class="ins-act ins-act--go {{ $hasErrors ? 'pointer-events-none opacity-40' : '' }}">
                    <span>{{ __('install.common.continue') }}</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
