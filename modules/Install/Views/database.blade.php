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

@push('styles')
<style>
    /* Шаг «База данных» пользуется общими классами первого шага
       (.ins-head, .ins-act и т. д.) — они объявлены во вьюхе welcome и
       на этой странице не подключаются. Дублировать весь набор незачем:
       здесь только то, что нужно этому шагу. */
    .ins-eyebrow{ margin:0 0 .1rem; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.6rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase;
        color:var(--accent,#6366f1) }
    .ins-title{ margin:0; font-size:1.5rem; font-weight:800; letter-spacing:-.03em;
        line-height:1.05; color:#111827 }
    .ins-head{ display:flex; align-items:center; gap:.85rem; padding:1.1rem 1.5rem;
        border-bottom:1px solid var(--surface-bd,#e3e6ee) }
    .ins-head__badge{ width:2.6rem; height:2.6rem; flex:none }
    .ins-head__about{ margin:.2rem 0 0; font-size:.78rem; line-height:1.45; color:#4b5563 }
    @media (max-width:640px){ .ins-head{ padding:1rem 1.1rem } }

    /* Значок СУБД рядом с названием шага. */
    .ins-tag{ display:inline-flex; align-items:center; gap:.25rem; vertical-align:middle;
        margin-left:.4rem; padding:.1rem .4rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#fff; background:var(--accent) }

    .ins-label{ display:flex; align-items:center; gap:.35rem; margin-bottom:.3rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:#4b5563 }
    .ins-label i{ color:var(--accent) }

    .ins-input{ width:100%; padding:.5rem .75rem; font-size:.875rem; color:#111827;
        background:var(--surface,#fff); border:1px solid var(--surface-bd,#d1d5db);
        transition:border-color .15s ease, box-shadow .15s ease }
    .ins-input:focus{ outline:none; border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent) }

    /* Указание, без которого шаг не пройти. */
    .ins-callout{ display:flex; align-items:flex-start; gap:.5rem; margin:.4rem 0 0;
        padding:.5rem .6rem; font-size:.73rem; line-height:1.45; color:#374151;
        background:color-mix(in srgb, var(--accent) 7%, transparent);
        border-left:3px solid var(--accent) }
    .ins-callout i{ margin-top:.1rem; flex:none; color:var(--accent) }
    .ins-callout code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.72rem }

    /* Ответ на частую заминку шага. */
    .ins-help{ display:block; padding:.65rem .8rem;
        background:var(--surface-2,#f7f8fc); border:1px solid var(--surface-bd,#e3e6ee) }
    .ins-help__cap{ display:inline-flex; align-items:center; gap:.35rem; margin-bottom:.25rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:#4b5563 }
    .ins-help__cap i{ color:var(--accent) }
    .ins-help__text{ display:block; font-size:.74rem; line-height:1.5; color:#4b5563 }
    .ins-help__text a{ color:var(--accent); text-decoration:underline }

    .ins-foot{ padding:1rem 1.5rem; border-top:1px solid var(--surface-bd,#e3e6ee) }

    .ins-act{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
        padding:.7rem 1.2rem; font-size:.85rem; font-weight:700; cursor:pointer;
        color:#374151; background:var(--surface-2,#f7f8fc);
        border:1px solid var(--surface-bd,#e3e6ee) }
    .ins-act--go{ color:#fff; border-color:transparent;
        background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 62%, #8b5cf6)) }
    .ins-act--go:hover{ filter:brightness(1.08) }
    .ins-act:disabled{ opacity:.6; cursor:not-allowed }
</style>
@endpush
