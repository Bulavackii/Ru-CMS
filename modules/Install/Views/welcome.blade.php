@extends('layouts.frontend-install')

@section('accent', '#6366f1')

@section('content')
<div class="w-full max-w-3xl max-h-full flex flex-col">
    <div class="install-card rounded-3xl flex flex-col max-h-full overflow-hidden">

        {{-- Шапка карточки называет ШАГ, а не продукт: имя «Nexum Core» уже
             написано крупно в левой колонке, а вот чем занят текущий шаг —
             больше нигде. Знак, номер, название и одна строка о том, что
             здесь происходит: тот же приём, что у заголовков разделов на
             сайте и в панели. --}}
        <div class="ins-head shrink-0">
            <div class="accent-badge ins-head__badge grid place-items-center text-white"
                 data-tip="{{ __('install.welcome.logo_tip') }}">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
            </div>

            <div class="min-w-0">
                <p class="ins-eyebrow">{{ __('install.steps.step') }} 01 · {{ __('install.welcome.suffix') }}</p>
                <h1 class="ins-title break-words">{{ __('install.steps.welcome') }}</h1>
                <p class="ins-head__about">{{ __('install.about.welcome') }}</p>
            </div>
        </div>

        {{-- Прокручиваемая середина (на маленьких экранах), обычно всё влезает --}}
        <div class="px-5 sm:px-6 py-5 overflow-y-auto install-scroll min-h-0 space-y-4">

            {{-- 🌍 Выбор языка: флаг — главный элемент --}}
            <div class="rounded-2xl border border-white/60 bg-white/40 backdrop-blur p-4"
                 style="box-shadow: inset 0 1px 0 rgba(255,255,255,.6)">
                {{-- flex-wrap: в разных языках подпись заметно разной длины,
                     без переноса строка распирала бы карточку по ширине. --}}
                <div class="ins-group-title">
                    <i data-lucide="languages" class="w-3.5 h-3.5 shrink-0"></i>
                    <span>{{ __('install.welcome.lang_title') }}</span>
                    <span class="ins-group-note">{{ __('install.welcome.lang_change') }}</span>
                </div>
                @php
                    // Инлайн-SVG флаги: Windows не отображает эмодзи флагов вообще
                    // (Segoe UI Emoji их не содержит), поэтому эмодзи из COUNTRY_PRESETS
                    // здесь бесполезны. SVG — локально, без единого внешнего запроса.
                    $flagSvg = [
                        'RU' => '<svg viewBox="0 0 30 20" class="w-10 h-7 rounded shadow-sm"><rect width="30" height="20" fill="#fff"/><rect y="6.67" width="30" height="6.67" fill="#0039A6"/><rect y="13.33" width="30" height="6.67" fill="#D52B1E"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'BY' => '<svg viewBox="0 0 30 20" class="w-10 h-7 rounded shadow-sm"><rect width="30" height="20" fill="#CE1720"/><rect y="13.33" width="30" height="6.67" fill="#007C30"/><rect width="3.3" height="20" fill="#fff"/><path d="M.8 1.5h1.7v2H.8zM.8 5.5h1.7v2H.8zM.8 9.5h1.7v2H.8zM.8 13.5h1.7v2H.8zM.8 17h1.7v2H.8z" fill="#CE1720"/><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'KZ' => '<svg viewBox="0 0 30 20" class="w-10 h-7 rounded shadow-sm"><rect width="30" height="20" fill="#00AFCA"/><circle cx="15" cy="9" r="3.4" fill="#FEC50C"/><g stroke="#FEC50C" stroke-width=".7"><line x1="15" y1="3.6" x2="15" y2="5"/><line x1="15" y1="13" x2="15" y2="14.4"/><line x1="9.6" y1="9" x2="11" y2="9"/><line x1="19" y1="9" x2="20.4" y2="9"/><line x1="11.2" y1="5.2" x2="12.2" y2="6.2"/><line x1="17.8" y1="11.8" x2="18.8" y2="12.8"/><line x1="18.8" y1="5.2" x2="17.8" y2="6.2"/><line x1="12.2" y1="11.8" x2="11.2" y2="12.8"/></g><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                        'US' => '<svg viewBox="0 0 30 20" class="w-10 h-7 rounded shadow-sm"><rect width="30" height="20" fill="#fff"/><g fill="#B22234"><rect width="30" height="1.54"/><rect y="3.08" width="30" height="1.54"/><rect y="6.15" width="30" height="1.54"/><rect y="9.23" width="30" height="1.54"/><rect y="12.31" width="30" height="1.54"/><rect y="15.38" width="30" height="1.54"/><rect y="18.46" width="30" height="1.54"/></g><rect width="12" height="10.77" fill="#3C3B6E"/><g fill="#fff"><circle cx="2" cy="1.8" r=".55"/><circle cx="5" cy="1.8" r=".55"/><circle cx="8" cy="1.8" r=".55"/><circle cx="11" cy="1.8" r=".55"/><circle cx="3.5" cy="3.6" r=".55"/><circle cx="6.5" cy="3.6" r=".55"/><circle cx="9.5" cy="3.6" r=".55"/><circle cx="2" cy="5.4" r=".55"/><circle cx="5" cy="5.4" r=".55"/><circle cx="8" cy="5.4" r=".55"/><circle cx="11" cy="5.4" r=".55"/><circle cx="3.5" cy="7.2" r=".55"/><circle cx="6.5" cy="7.2" r=".55"/><circle cx="9.5" cy="7.2" r=".55"/><circle cx="2" cy="9" r=".55"/><circle cx="5" cy="9" r=".55"/><circle cx="8" cy="9" r=".55"/><circle cx="11" cy="9" r=".55"/></g><rect width="30" height="20" fill="none" stroke="#00000022" stroke-width=".5"/></svg>',
                    ];
                @endphp
                <form method="GET" action="{{ route('install.welcome') }}">
                    {{-- Колонок ровно столько, сколько кнопок: при двух языках сетка на
                         четыре оставляла половину строки пустой. --}}
                    <div class="ins-langs {{ count($presetCountries) > 2 ? 'is-many' : '' }}">
                        @foreach($presetCountries as $code => $country)
                            @php $isSel = ($currentCountry ?? 'RU') === $code; @endphp
                            <button type="submit"
                                    name="country_code"
                                    value="{{ $code }}"
                                    data-tip="{{ $country['name'] ?? $code }} · {{ $country['currency_code'] ?? '' }} · {{ $country['timezone'] ?? '' }}"
                                    data-tip-pos="bottom"
                                    class="ins-lang {{ $isSel ? 'is-sel' : '' }}">
                                <span class="ins-lang__flag">{!! $flagSvg[$code] ?? '<span class="text-xl leading-none">🌍</span>' !!}</span>

                                <span class="ins-lang__body">
                                    <span class="ins-lang__name break-words">{{ $country['lang'] ?? $country['name'] ?? $code }}</span>
                                    {{-- Страна — на её собственном языке: рядом с флагом это
                                         читается естественно на любой локали интерфейса. --}}
                                    <span class="ins-lang__native break-words">{{ $country['native_name'] ?? $country['name'] ?? $code }}</span>
                                </span>

                                @if ($isSel)
                                    <i data-lucide="check" class="w-4 h-4 ins-lang__tick"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="ins-note mt-2.5 text-center flex flex-wrap items-center justify-center gap-x-1 gap-y-0.5">
                        <i data-lucide="info" class="w-3 h-3 shrink-0"></i>
                        {{ __('install.welcome.lang_note') }}
                    </p>
                </form>
            </div>

            {{-- Шаги --}}
            @include('Install::partials.steps', ['current' => 'welcome'])

            {{-- Три мини-фичи в одну строку --}}
            <div class="ins-feats">
                {{-- min-w-0 + break-words: колонок три, а слова в разных языках
                     длинные и неразрывные («Безопасность», «Қауіпсіздік»).
                     Без этого min-content колонки распирал бы сетку. --}}
                <div class="ins-feat" data-tip="{{ __('install.welcome.f_easy_tip') }}">
                    <span class="ins-feat__ico"><i data-lucide="gauge" class="w-4 h-4"></i></span>
                    <span class="min-w-0">
                        <span class="ins-feat__title break-words">{{ __('install.welcome.f_easy') }}</span>
                        <span class="ins-feat__sub break-words">{{ __('install.welcome.f_easy_sub') }}</span>
                    </span>
                </div>
                <div class="ins-feat" data-tip="{{ __('install.welcome.f_secure_tip') }}">
                    <span class="ins-feat__ico"><i data-lucide="shield-check" class="w-4 h-4"></i></span>
                    <span class="min-w-0">
                        <span class="ins-feat__title break-words">{{ __('install.welcome.f_secure') }}</span>
                        <span class="ins-feat__sub break-words">{{ __('install.welcome.f_secure_sub') }}</span>
                    </span>
                </div>
                <div class="ins-feat" data-tip="{{ __('install.welcome.f_nocdn_tip') }}">
                    <span class="ins-feat__ico"><i data-lucide="hard-drive" class="w-4 h-4"></i></span>
                    <span class="min-w-0">
                        <span class="ins-feat__title break-words">{{ __('install.welcome.f_nocdn') }}</span>
                        <span class="ins-feat__sub break-words">{{ __('install.welcome.f_nocdn_sub') }}</span>
                    </span>
                </div>
            </div>

            {{-- Что понадобится: одна строка про реквизиты выглядела пустой
                 в широкой плашке. Здесь то, что стоит подготовить ДО
                 запуска, и что можно пропустить. --}}
            <div class="ins-need">
                <span class="ins-need__cap">{{ __('install.welcome.need_title') }}</span>

                <ul class="ins-need__list">
                    <li><i data-lucide="database" class="w-3.5 h-3.5"></i><span>{{ __('install.welcome.need_db') }}</span></li>
                    <li><i data-lucide="user-round" class="w-3.5 h-3.5"></i><span>{{ __('install.welcome.need_admin') }}</span></li>
                    <li><i data-lucide="skip-forward" class="w-3.5 h-3.5"></i><span>{{ __('install.welcome.need_skip') }}</span></li>
                </ul>
            </div>
        </div>

        {{-- Кнопки: прижаты к низу карточки --}}
        <div class="ins-foot shrink-0">
            {{-- Полосы во всю ширину вместо трёх кнопок по центру: главное
                 действие занимает столько же места, сколько весит по смыслу,
                 и попасть в него мышью проще, чем в кнопку 180px. --}}
            <div class="ins-actions">
                <a href="{{ route('install.requirements') }}" class="ins-act ins-act--go">
                    {{-- Значка «play» слева нет: он дублировал стрелку справа —
                         два указателя направления на одной кнопке. --}}
                    <span>{{ __('install.welcome.start') }}</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 ins-act__go"></i>
                </a>

                <a href="{{ route('install.features') }}" class="ins-act"
                   data-tip="{{ __('install.welcome.features_tip') }}">
                    <i data-lucide="star" class="w-4 h-4"></i>
                    <span>{{ __('install.welcome.features') }}</span>
                </a>

                <a href="https://github.com/#" target="_blank" rel="noopener" class="ins-act ins-act--dim"
                   data-tip="{{ __('install.welcome.github_tip') }}">
                    <i data-lucide="github" class="w-4 h-4"></i>
                    <span>GitHub</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ins-tagline{ margin:.15rem 0 0; font-size:.72rem; color:#4b5563 }

    .ins-group-title{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center;
        gap:.4rem; margin-bottom:.75rem; text-align:center;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:#374151 }
    .ins-group-title i{ color:var(--accent,#6366f1) }
    .ins-group-note{ font-weight:600; letter-spacing:.04em; text-transform:none; color:#6b7280 }

    /* ── Выбор языка ──────────────────────────────────────────────────
       Флаг слева, подписи справа, отметка в конце строки. Прежде карточка
       была центрированной колонкой на половину ширины: флаг, два слова и
       много пустого поля вокруг. */
    .ins-langs{ display:grid; gap:.5rem; grid-template-columns:repeat(2, minmax(0,1fr)) }
    .ins-langs.is-many{ grid-template-columns:repeat(2, minmax(0,1fr)) }
    @media (min-width:640px){ .ins-langs.is-many{ grid-template-columns:repeat(4, minmax(0,1fr)) } }

    .ins-lang{ display:flex; align-items:center; gap:.6rem; padding:.55rem .7rem; min-width:0;
        text-align:left; background:var(--surface,#fff);
        border:1px solid var(--surface-bd,#e3e6ee);
        transition:border-color .15s ease, background .15s ease, box-shadow .15s ease }
    .ins-lang:hover{ border-color:color-mix(in srgb, var(--accent) 45%, var(--surface-bd,#e3e6ee)) }
    .ins-lang.is-sel{ border-color:var(--accent);
        box-shadow:inset 0 0 0 1px var(--accent) }

    .ins-lang__flag{ display:flex; flex:none }
    .ins-lang__flag svg{ width:1.75rem; height:1.2rem; display:block }
    .ins-lang__body{ display:flex; flex-direction:column; min-width:0 }
    .ins-lang__name{ font-size:.8rem; font-weight:700; color:#374151 }
    .ins-lang.is-sel .ins-lang__name{ color:#111827 }
    .ins-lang__native{ font-size:.65rem; color:#6b7280 }
    .ins-lang__tick{ margin-left:auto; flex:none; color:var(--accent) }

    /* ── Что понадобится ── */
    .ins-need{ padding:.75rem .9rem; background:var(--surface-2,#f7f8fc);
        border:1px solid var(--surface-bd,#e3e6ee) }
    .ins-need__cap{ display:block; margin-bottom:.4rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.6rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:#4b5563 }
    .ins-need__list{ display:grid; gap:.35rem; margin:0; padding:0; list-style:none }
    .ins-need__list li{ display:flex; align-items:flex-start; gap:.5rem;
        font-size:.74rem; line-height:1.45; color:#4b5563 }
    .ins-need__list i{ margin-top:.15rem; flex:none; color:var(--accent) }

    .ins-note{ font-size:.7rem; line-height:1.5; color:#4b5563 }

    .country-select-btn { transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease; }
    .country-select-btn:hover { transform: translateY(-3px); }
    .country-select-btn:active { transform: translateY(0); }
</style>
@endpush
@endsection
