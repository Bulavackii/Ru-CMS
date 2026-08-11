@extends('layouts.frontend-install')

@section('accent', '#2563eb')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.database') }}"
          class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden"
          x-data="{showPass:false, submitting:false}"
          x-on:submit="submitting=true">
        @csrf

        {{-- Шапка шага — полосой, как на первом шаге: знак, номер, название
             и строка о том, что здесь происходит. Прежде это была
             центрированная колонка со значком, заголовком и бейджем. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white">
                <i data-lucide="database" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 03 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">
                    {{ __('install.database.title') }}
                    <span class="ins-tag" data-tip="{{ __('install.database.badge_tip') }}" data-tip-pos="bottom">
                        <i data-lucide="database-zap" class="w-3 h-3"></i> PostgreSQL
                    </span>
                </h1>
                <p class="ins-head__about">{{ __('install.about.database') }}</p>
            </div>
        </div>

        <div class="px-5 sm:px-6 pt-4 shrink-0">
            @include('Install::partials.steps', ['current' => 'database'])
        </div>

        {{-- Поля --}}
        <div class="px-5 sm:px-6 py-4 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if ($errors->any())
                <div class="bg-gray-900 text-white text-xs rounded-2xl p-3">
                    <div class="flex items-center gap-1.5 font-semibold mb-1"><i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> {{ __('install.common.error_title') }}</div>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2.5">
                <div class="col-span-2">
                    <label for="host" class="ins-label">
                        <i data-lucide="server" class="w-3 h-3 text-gray-400"></i> {{ __('install.database.host') }}
                    </label>
                    <input type="text" name="host" id="host"
                           value="{{ old('host', '127.0.0.1') }}"
                           autocomplete="off"
                           placeholder="127.0.0.1"
                           title="{{ __('install.database.host_tip') }}"
                           class="ins-input"
                           required autofocus>
                </div>
                <div>
                    <label for="port" class="ins-label">
                        <i data-lucide="plug" class="w-3 h-3 text-gray-400"></i> {{ __('install.database.port') }}
                    </label>
                    <input type="text" name="port" id="port"
                           value="{{ old('port', $defaultPort) }}"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                           title="{{ __('install.database.port_tip') }}"
                           class="ins-input"
                           required>
                </div>
            </div>

            <div>
                <label for="database" class="ins-label">
                    <i data-lucide="database" class="w-3 h-3 text-gray-400"></i> {{ __('install.database.database') }}
                </label>
                <input type="text"
                       name="database" id="database"
                       value="{{ old('database') }}"
                       placeholder="{{ __('install.database.db_placeholder') }}"
                       autocomplete="off"
                       title="{{ __('install.database.db_tip') }}"
                       class="ins-input"
                       required>
                {{-- Главное указание шага: базу создаёт не мастер. Раньше это
                     была серая строка 11px под полем — её пропускали и
                     упирались в ошибку подключения. --}}
                <p class="ins-callout">
                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                    <span>{!! __('install.database.db_note') !!}</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="username" class="ins-label">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> {{ __('install.database.username') }}
                    </label>
                    <input type="text" name="username" id="username"
                           value="{{ old('username') }}"
                           autocomplete="username"
                           title="{{ __('install.database.username_tip') }}"
                           class="ins-input"
                           required>
                </div>
                <div>
                    <label for="password" class="ins-label">
                        <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> {{ __('install.database.password') }}
                    </label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'"
                               name="password" id="password"
                               value="{{ old('password') }}"
                               placeholder="●●●●●●"
                               autocomplete="new-password"
                               class="ins-input pr-10">
                        <button type="button"
                                class="absolute right-1.5 inset-y-0 my-auto w-7 h-7 grid place-items-center rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100"
                                x-on:click="showPass=!showPass"
                                :title="showPass ? @js(__('install.common.hide_password')) : @js(__('install.common.show_password'))">
                            {{-- x-show на span-обёртке: Lucide заменяет <i> на <svg> и теряет Alpine-атрибуты --}}
                            <span x-show="!showPass" class="grid place-items-center"><i data-lucide="eye" class="w-4 h-4"></i></span>
                            <span x-show="showPass" x-cloak class="grid place-items-center"><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Что делать, если не подключается: это ответ на самую частую
                 заминку шага, поэтому блок оформлен как отдельная заметка, а
                 не как ещё одна бледная сноска. --}}
            <div class="ins-help">
                <span class="ins-help__cap">
                    <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i> {{ __('install.database.help_cap') }}
                </span>
                <span class="ins-help__text">{!! __('install.database.help', ['url' => route('install.requirements')]) !!}</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="ins-foot shrink-0 flex items-center justify-between">
            <a href="{{ route('install.requirements') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <button type="submit" class="ins-act ins-act--go" :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="cable" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? @js(__('install.database.submitting')) : @js(__('install.database.submit'))"></span>
            </button>
        </div>
    </form>
</div>
@endsection
