@extends('layouts.admin')

@section('title', __('admin.account.title'))

@section('content')
    {{-- Шапка в общем для панели виде: акцентная полоса и значок в плитке.
         Раньше тут стоял обычный заголовок с эмодзи, и страница выбивалась
         из ряда остальных разделов. --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex items-center gap-3">
        <span class="admin-icon-badge"><i class="fas fa-user-gear"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.account.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.account.subtitle') }}</p>
        </div>
    </div>

    {{-- Семь отдельных карточек с одной строкой в каждой давали рваную
         сетку и последний ряд из единственной плитки. Сведено в два блока
         по смыслу: кто я и как защищён вход. --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">

        <section class="admin-card p-5">
            <h2 class="acc-head">
                <i class="fas fa-id-card text-indigo-500"></i> {{ __('admin.account.profile') }}
            </h2>

            <dl class="acc-list">
                <div>
                    <dt>{{ __('admin.account.name') }}</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.account.email') }}</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.account.role') }}</dt>
                    <dd>{{ $user->is_admin ? __('admin.account.role_admin') : __('admin.account.role_user') }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.account.id') }}</dt>
                    <dd class="acc-mono">{{ $user->id }}</dd>
                </div>
                <div>
                    <dt>{{ __('admin.account.updated') }}</dt>
                    <dd>{{ $user->updated_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="admin-card p-5">
            <h2 class="acc-head">
                <i class="fas fa-shield-halved text-indigo-500"></i> {{ __('admin.account.security') }}
            </h2>

            <div class="acc-actions">
                <a href="{{ route('password.change.form') }}" class="acc-action">
                    <span class="acc-action__ico"><i class="fas fa-key"></i></span>
                    <span class="acc-action__body">
                        <span class="acc-action__title">{{ __('admin.account.change_password') }}</span>
                        <span class="acc-action__note">{{ __('admin.account.change_password_note') }}</span>
                    </span>
                    <i class="fas fa-chevron-right acc-action__arrow"></i>
                </a>

                {{-- Маршруты проверяются: двухфакторная проверка и история
                     входов живут в отдельных частях проекта и могут быть
                     отключены. Ссылка на несуществующий маршрут уронила бы
                     страницу целиком. --}}
                @if (Route::has('two-factor.setup'))
                    <a href="{{ route('two-factor.setup') }}" class="acc-action">
                        <span class="acc-action__ico"><i class="fas fa-mobile-screen"></i></span>
                        <span class="acc-action__body">
                            <span class="acc-action__title">{{ __('admin.account.two_factor') }}</span>
                            <span class="acc-action__note">{{ __('admin.account.two_factor_note') }}</span>
                        </span>
                        {{-- Состояние прямо в строке: иначе понять, включена
                             проверка или нет, можно было только перейдя по
                             ссылке — а там показывается уже другая страница. --}}
                        <span class="acc-state {{ auth()->user()->hasTwoFactorEnabled() ? 'is-on' : '' }}">
                            {{ auth()->user()->hasTwoFactorEnabled() ? __('admin.account.two_factor_on') : __('admin.account.two_factor_off') }}
                        </span>
                        <i class="fas fa-chevron-right acc-action__arrow"></i>
                    </a>
                @endif

                @if (Route::has('dashboard.login-history'))
                    <a href="{{ route('dashboard.login-history') }}" class="acc-action">
                        <span class="acc-action__ico"><i class="fas fa-clock-rotate-left"></i></span>
                        <span class="acc-action__body">
                            <span class="acc-action__title">{{ __('admin.account.login_history') }}</span>
                            <span class="acc-action__note">{{ __('admin.account.login_history_note') }}</span>
                        </span>
                        <i class="fas fa-chevron-right acc-action__arrow"></i>
                    </a>
                @endif
            </div>

            {{-- Карточка «Восстановление — скоро» убрана: она обещала то, чего
                 нет. Восстановление по почте в системе есть и работает, но
                 через форму входа на сайте и только при настроенной отправке
                 писем — об этом и сказано. --}}
            <p class="acc-help">{{ __('admin.account.reset_hint') }}</p>
        </section>
    </div>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке проекта нет ни произвольных
       значений, ни прозрачности через дробь. */
    .acc-head{ display:flex; align-items:center; gap:.5rem; margin-bottom:.9rem;
        font-size:.75rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#94a3b8 }

    .acc-list{ display:grid; gap:.5rem; font-size:.9rem }
    .acc-list > div{ display:flex; align-items:baseline; justify-content:space-between;
        gap:1rem; padding-bottom:.5rem; border-bottom:1px solid #f1f5f9 }
    .acc-list > div:last-child{ padding-bottom:0; border-bottom:0 }
    .acc-list dt{ color:#64748b }
    .acc-list dd{ margin:0; font-weight:600; color:#0f172a; text-align:right; word-break:break-word }
    .acc-mono{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace }

    /* Действия — строками со значком, а не голыми ссылками: видно, что это
       переход, и есть место для пояснения, куда именно. */
    .acc-actions{ display:grid; gap:.4rem }
    .acc-action{ display:flex; align-items:center; gap:.75rem; padding:.65rem .7rem;
        text-decoration:none; color:inherit; border:1px solid #eef2f7;
        transition:border-color .15s, background-color .15s }
    .acc-action:hover{ border-color:var(--admin-primary,#6366f1); background:#f8fafc }
    .acc-action__ico{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; flex:0 0 auto; font-size:.85rem;
        color:var(--admin-primary,#6366f1); background:rgba(99,102,241,.1) }
    .acc-action__body{ display:flex; flex-direction:column; min-width:0; line-height:1.3 }
    .acc-action__title{ font-size:.9rem; font-weight:600; color:#0f172a }
    .acc-action__note{ font-size:.75rem; color:#64748b }
    .acc-action__arrow{ margin-left:auto; font-size:.7rem; color:#cbd5e1 }
    .acc-action:hover .acc-action__arrow{ color:var(--admin-primary,#6366f1) }

    /* Состояние строки. Стоит ПЕРЕД стрелкой, поэтому отодвигается влево
       само (стрелка забирает свободный отступ через margin-left:auto). */
    /* Тон потемнее серого: полужирная подпись в 0.68rem на светлой плашке
       иначе не дотягивает до 4.5 по контрасту. */
    /* Те же правила, что в кабинете на сайте: цвет подмешивается к
       подложке, а не задаётся готовой парой. Панель светлая, но оформление
       следует активной теме, и прибитая пара цветов на тёмных темах
       давала нечитаемую плашку. */
    .acc-action .acc-state{ flex:0 0 auto; margin-left:auto; padding:.12rem .45rem;
                font-size:.68rem; font-weight:700; white-space:nowrap;
                color:color-mix(in srgb, var(--surface-ink,#111827) 72%, var(--surface-2,#f3f4f6));
                background:var(--surface-2,#f3f4f6);
                border:1px solid var(--surface-bd,#e5e7eb) }
    .acc-state + .acc-action__arrow{ margin-left:.55rem }
    .acc-action .acc-state.is-on{
                color:color-mix(in srgb, #16a34a 45%, var(--surface-ink,#111827));
                background:color-mix(in srgb, #16a34a 16%, var(--surface,#ffffff));
                border-color:color-mix(in srgb, #16a34a 40%, var(--surface,#ffffff)) }

    .acc-help{ margin-top:.9rem; font-size:.78rem; line-height:1.5; color:#64748b }

    .dark .acc-list > div{ border-color:#1f2937 }
    .dark .acc-list dd, .dark .acc-action__title{ color:#f3f4f6 }
    .dark .acc-action{ border-color:#1f2937 }
    .dark .acc-action:hover{ background:#111827 }
</style>
@endpush
