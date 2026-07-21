@extends('layouts.frontend-install')

@section('accent', '#0ea5e9')

@section('content')
<div class="w-full max-w-xl max-h-full flex flex-col">
    <form method="POST" action="{{ route('install.admin') }}"
          class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden"
          x-data="{showPass:false, submitting:false, strength:0, tipsOpen:false}"
          x-on:submit="submitting=true">
        @csrf

        {{-- Шапка --}}
        <div class="px-6 sm:px-8 pt-5 pb-3 shrink-0 space-y-3">
            @include('Install::partials.steps', ['current' => 'admin'])
            <div class="text-center">
                <div class="accent-badge mx-auto w-10 h-10 rounded-xl text-white grid place-items-center mb-2">
                    <i data-lucide="user-round" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">{{ __('install.admin.title') }}</h2>
                <p class="text-gray-500 text-xs flex items-center justify-center gap-1">
                    <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                    {{ __('install.admin.subtitle') }}
                </p>
            </div>
        </div>

        {{-- Поля --}}
        <div class="px-6 sm:px-8 overflow-y-auto install-scroll min-h-0 space-y-3">
            @if ($errors->any())
                <div class="bg-gray-900 text-white text-xs rounded-2xl p-3">
                    <div class="flex items-center gap-1.5 font-semibold mb-1"><i data-lucide="octagon-alert" class="w-3.5 h-3.5"></i> {{ __('install.common.error_title') }}</div>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    @if (config('app.debug') && $errors->has('artisan'))
                        <pre class="mt-2 text-[10px] whitespace-pre-wrap bg-black/40 rounded-lg p-2 max-h-32 overflow-auto install-scroll">{{ $errors->first('artisan') }}</pre>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="name" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3 text-gray-400"></i> {{ __('install.admin.name') }}
                    </label>
                    <input type="text"
                           name="name" id="name"
                           placeholder="{{ __('install.admin.name_placeholder') }}"
                           value="{{ old('name', __('install.admin.name_placeholder')) }}"
                           autocomplete="name"
                           title="{{ __('install.admin.name_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required autofocus>
                </div>
                <div>
                    <label for="email" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i> {{ __('install.admin.email') }}
                    </label>
                    <input type="email"
                           name="email" id="email"
                           placeholder="admin@example.com"
                           value="{{ old('email') }}"
                           autocomplete="email"
                           inputmode="email"
                           title="{{ __('install.admin.email_tip') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required>
                </div>
            </div>

            {{-- Пароль + индикатор --}}
            <div>
                <label for="password" class="mb-1 text-xs font-medium text-gray-700 flex items-center gap-1">
                    <i data-lucide="lock" class="w-3 h-3 text-gray-400"></i> {{ __('install.admin.password') }}
                </label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" id="password"
                           placeholder="●●●●●●"
                           autocomplete="new-password"
                           class="w-full pr-10 px-3 py-2 rounded-xl border border-gray-300 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-gray-900/20 focus:border-gray-900"
                           required
                           x-on:input="
                                const v = $event.target.value || '';
                                let score = 0;
                                if (v.length >= 8) score++;
                                if (v.length >= 12) score++;
                                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                                if (/\d/.test(v)) score++;
                                if (/[^A-Za-z0-9]/.test(v)) score++;
                                strength = Math.min(score, 5);
                           ">
                    <button type="button"
                            class="absolute right-1.5 inset-y-0 my-auto w-7 h-7 grid place-items-center rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100"
                            x-on:click="showPass=!showPass"
                            :title="showPass ? @js(__('install.common.hide_password')) : @js(__('install.common.show_password'))">
                        {{-- x-show на span-обёртке: Lucide заменяет <i> на <svg> и теряет Alpine-атрибуты --}}
                        <span x-show="!showPass" class="grid place-items-center"><i data-lucide="eye" class="w-4 h-4"></i></span>
                        <span x-show="showPass" x-cloak class="grid place-items-center"><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                    </button>
                </div>

                {{-- Шкала надёжности: 5 сегментов, цвет меняется от красного к зелёному --}}
                <div class="mt-2" x-data="{ strengthColors: ['#e5e7eb','#ef4444','#f97316','#eab308','#84cc16','#22c55e'] }">
                    <div class="flex items-center gap-1">
                        <template x-for="i in 5" :key="i">
                            <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                 :style="`background-color:${ i <= strength ? strengthColors[strength] : '#e5e7eb' }`"></div>
                        </template>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between">
                        <p class="text-[11px] text-gray-400 flex items-center gap-1">
                            <i data-lucide="shield" class="w-3 h-3"></i>
                            {{ __('install.admin.strength') }} <span class="font-semibold" :style="`color:${strength ? strengthColors[strength] : '#4b5563'}`" x-text="@js(__('install.admin.strength_levels'))[strength]"></span>
                        </p>
                        <button type="button" class="text-[11px] text-gray-500 hover:text-gray-900 underline decoration-dotted" x-on:click="tipsOpen=!tipsOpen">
                            {{ __('install.admin.tips') }}
                        </button>
                    </div>
                    <ul x-show="tipsOpen" x-cloak class="mt-1.5 text-[11px] text-gray-500 space-y-0.5 list-disc pl-5">
                        <li>{{ __('install.admin.tip_length') }}</li>
                        <li>{{ __('install.admin.tip_dictionary') }}</li>
                        <li>{{ __('install.admin.tip_manager') }}</li>
                    </ul>
                </div>
            </div>

            <div class="hint rounded-xl px-3 py-2 text-[11px] text-gray-500 flex items-start gap-1.5">
                <i data-lucide="life-buoy" class="w-3.5 h-3.5 mt-0.5 shrink-0 hint-ico"></i>
                <span>{!! __('install.admin.help', ['url' => route('install.requirements')]) !!}</span>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="px-6 sm:px-8 py-4 shrink-0 border-t border-gray-100 mt-3 flex items-center justify-between">
            <a href="{{ route('install.database') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('install.common.back') }}
            </a>
            <button type="submit"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 bg-gray-900 hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-sm font-semibold"
                    :disabled="submitting">
                <svg x-show="submitting" x-cloak viewBox="0 0 24 24" class="animate-spin h-4 w-4">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                </svg>
                <i data-lucide="user-plus" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? @js(__('install.admin.submitting')) : @js(__('install.admin.submit'))"></span>
            </button>
        </div>
    </form>
</div>
@endsection
