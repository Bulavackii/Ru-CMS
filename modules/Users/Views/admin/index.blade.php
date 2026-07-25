@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content')
    @php
        $adminsCount = $users->where('is_admin', true)->count();
    @endphp

    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-users"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Пользователи</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Учётные записи, роли и доступ в панель управления.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0">
            <i class="fas fa-user-plus"></i> Добавить пользователя
        </a>
    </div>

    {{-- ── Фильтры ── --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="admin-card p-5 mb-5">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-indigo-500"></i> Фильтры
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="{{ $roles->count() > 0 ? 'md:col-span-2' : 'md:col-span-3' }}">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Поиск</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                         width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Имя, email или телефон…"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-10 pr-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Тип учётной записи</label>
                <select name="role" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Все</option>
                    <option value="admin" @selected($currentRole === 'admin')>Администраторы</option>
                    <option value="user" @selected($currentRole === 'user')>Обычные пользователи</option>
                </select>
            </div>

            @if($roles->count() > 0)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Роль</label>
                    <select name="role_filter" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Любая роль</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected($roleFilter == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-magnifying-glass"></i> Применить
            </button>
            @if($search || $currentRole || $roleFilter)
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium
                          text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <i class="fas fa-xmark"></i> Сбросить
                </a>
            @endif
        </div>
    </form>

    {{-- ── Подсказка + сводка ── --}}
    <div class="admin-hint px-4 py-3 mb-5 text-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 font-medium">
                <i class="fas fa-lightbulb"></i>
                <span>Свою учётную запись нельзя выбрать для массовых операций и удалить. Администраторы защищены от удаления.</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs shrink-0">
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                    Всего: {{ $users->total() }}
                </span>
                <span class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-gray-700 px-2 py-1">
                    Админов на странице: {{ $adminsCount }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Массовые операции.
         ⚠️ Раньше форма закрывалась ДО таблицы, а чекбоксы user_ids[] лежали
         внутри таблицы — то есть в запрос они не попадали вообще и любая массовая
         операция падала на валидации «user_ids обязательно». Теперь чекбоксы
         привязаны к форме атрибутом form="bulkForm" (HTML5), поэтому таблица
         может жить отдельно. ── --}}
    <form method="POST" action="{{ route('admin.users.bulkAction') }}" id="bulkForm" class="admin-card p-5 mb-5">
        @csrf
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-list-check text-indigo-500"></i> Массовые действия
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            {{-- Шаг 1: кого --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">1. Кого</label>
                <div id="bulkCounter"
                     class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                    Отметьте в таблице
                </div>
            </div>

            {{-- Шаг 2: что сделать --}}
            <div class="md:col-span-3">
                <label for="bulkAction" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">2. Что сделать</label>
                <select name="action" id="bulkAction"
                        class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Выберите действие…</option>
                    {{-- Пункт показываем, только если роли вообще есть: иначе он вёл
                         в тупик — список ролей пуст, а валидация требует role_id. --}}
                    @if($roles->count() > 0)
                        <option value="assign_role">Назначить роль</option>
                    @endif
                    <option value="delete">Удалить выбранных</option>
                </select>
            </div>

            {{-- Шаг 3: роль (только для назначения) --}}
            <div class="md:col-span-3" id="bulkRoleWrap" style="display:none;">
                <label for="bulkRole" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">3. Какую роль</label>
                <select name="role_id" id="bulkRole"
                        class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Выберите роль…</option>
                    @foreach($roles->sortByDesc('priority') as $role)
                        {{-- data-description подставляется под селект при выборе --}}
                        <option value="{{ $role->id }}" data-description="{{ $role->description }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <p id="bulkRoleHint" class="mt-1 text-xs text-gray-500 dark:text-gray-400"></p>
            </div>

            <div class="md:col-span-3">
                <button type="submit" id="bulkSubmit" disabled
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition
                               disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check"></i> Применить
                </button>
            </div>
        </div>

        @if($roles->count() === 0)
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-circle-info"></i>
                Ролей в системе пока нет, поэтому доступно только удаление.
                Роли создаются командой <code class="font-mono">php artisan db:seed --class=RbacSeeder</code>.
            </p>
        @else
            {{-- Краткая справка по ролям: свёрнута по умолчанию, чтобы не занимать
                 место, но объясняет, что именно даёт каждая роль. --}}
            <details class="mt-4 group">
                <summary class="inline-flex items-center gap-2 cursor-pointer select-none text-xs font-medium
                                text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    <i class="fas fa-circle-question"></i> Что означают роли
                    <i class="fas fa-chevron-down text-[0.6rem] opacity-60 group-open:hidden"></i>
                    <i class="fas fa-chevron-up text-[0.6rem] opacity-60 hidden group-open:inline"></i>
                </summary>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                    @foreach($roles->sortByDesc('priority') as $role)
                        <div class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold
                                             bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                    {{ $role->name }}
                                </span>
                                @php
                                    // Русское склонение считаем явно: trans_choice зависит
                                    // от текущей локали и на en дал бы неверную форму.
                                    $pc = (int) $role->permissions_count;
                                    $m10 = $pc % 10; $m100 = $pc % 100;
                                    $pw = ($m10 === 1 && $m100 !== 11) ? 'право'
                                        : (($m10 >= 2 && $m10 <= 4 && !($m100 >= 12 && $m100 <= 14)) ? 'права' : 'прав');
                                @endphp
                                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap"
                                      title="Количество прав в роли">
                                    {{ $pc }} {{ $pw }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-snug">
                                {{ $role->description ?: 'Описание не задано.' }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Роль расширяет доступ к разделам админки. Отдельный флаг «Администратор»
                    даёт полный доступ независимо от ролей.
                </p>
            </details>
        @endif
    </form>

    {{-- ── Таблица ── --}}
    <div class="admin-card overflow-hidden">
     <div class="overflow-x-auto">
        <table id="usersTable" class="min-w-full text-sm text-left">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="selectAll" class="h-4 w-4" title="Выбрать все">
                    </th>
                    <th class="px-4 py-3 font-semibold">Пользователь</th>
                    <th class="px-4 py-3 font-semibold hidden lg:table-cell">Телефон</th>
                    <th class="px-4 py-3 font-semibold">Роль</th>
                    <th class="px-4 py-3 font-semibold hidden xl:table-cell">Регистрация</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Последний вход</th>
                    <th class="px-4 py-3 text-center font-semibold w-36">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($users as $user)
                    @php $isSelf = auth()->id() === $user->id; @endphp
                    <tr class="user-row hover:bg-indigo-50/60 dark:hover:bg-gray-800 transition">
                        <td class="px-4 py-3 align-top">
                            @unless($isSelf)
                                {{-- form="bulkForm" связывает чекбокс с формой массовых операций --}}
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                       form="bulkForm" class="user-checkbox h-4 w-4">
                            @else
                                <span class="text-gray-300 dark:text-gray-600" title="Это вы"><i class="fas fa-user-check"></i></span>
                            @endunless
                        </td>

                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-gray-900 dark:text-white user-name">
                                {{ $user->name }}
                                @if($isSelf)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 text-xs font-medium
                                                 bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">это вы</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 user-email">{{ $user->email }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">ID {{ $user->id }}</div>
                        </td>

                        <td class="px-4 py-3 align-top hidden lg:table-cell text-gray-600 dark:text-gray-400">
                            {{ $user->formatted_phone ?? '—' }}
                        </td>

                        <td class="px-4 py-3 align-top">
                            @if ($user->is_admin)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                             bg-indigo-600 text-white">
                                    <i class="fas fa-shield-halved"></i> Администратор
                                </span>
                            @elseif($user->roles->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($user->roles as $role)
                                        {{-- Описание роли — во всплывающей подсказке --}}
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium cursor-help
                                                     bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                                              title="{{ $role->description ?: $role->name }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium
                                             bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="fas fa-user"></i> Без роли
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 align-top hidden xl:table-cell text-xs text-gray-600 dark:text-gray-400">
                            {{ $user->created_at->format('d.m.Y H:i') }}
                        </td>

                        <td class="px-4 py-3 align-top hidden md:table-cell text-xs text-gray-600 dark:text-gray-400">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d.m.Y H:i') }}
                                @if($user->last_login_ip)
                                    <div class="text-gray-400 dark:text-gray-500 font-mono">{{ $user->last_login_ip }}</div>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">Никогда</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 align-top text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition"
                                   title="Редактировать">
                                    <i class="fas fa-pen"></i>
                                </a>

                                <a href="{{ route('admin.users.loginHistory', $user) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                          text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                                   title="История входов">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </a>

                                @if (!$user->is_admin || $isSelf)
                                    <a href="{{ route('admin.users.password.edit', $user) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 border border-gray-300 dark:border-gray-600
                                              text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                                       title="Сменить пароль">
                                        <i class="fas fa-key"></i>
                                    </a>
                                @endif

                                @if ($isSelf)
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed"
                                          title="Нельзя удалить свою учётную запись"><i class="fas fa-lock"></i></span>
                                @elseif ($user->is_admin)
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed"
                                          title="Администратора удалить нельзя"><i class="fas fa-lock"></i></span>
                                @else
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Удалить пользователя «{{ $user->name }}»? Действие необратимо.')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-red-600 hover:bg-red-700 text-white shadow-sm transition"
                                                title="Удалить">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <span class="admin-icon-badge mx-auto mb-3"><i class="fas fa-users"></i></span>
                            <p class="text-gray-600 dark:text-gray-300 font-medium">Пользователи не найдены.</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                @if($search || $currentRole || $roleFilter)
                                    Измените фильтры или
                                    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">сбросьте их</a>.
                                @else
                                    <a href="{{ route('admin.users.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">Добавьте первого пользователя</a>.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
     </div>
    </div>

    {{-- ── Пагинация ── --}}
    <div class="mt-6">
        {{ $users->withQueryString()->links('vendor.pagination.tailwind') }}
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const boxes = () => [...document.querySelectorAll('.user-checkbox')];
        const selectAll  = document.getElementById('selectAll');
        const actionSel  = document.getElementById('bulkAction');
        const roleSel    = document.getElementById('bulkRole');
        const submitBtn  = document.getElementById('bulkSubmit');
        const counter    = document.getElementById('bulkCounter');

        // Русское склонение для счётчика выбранных
        function word(n) {
            const m10 = n % 10, m100 = n % 100;
            if (m10 === 1 && m100 !== 11) return 'пользователь';
            if (m10 >= 2 && m10 <= 4 && !(m100 >= 12 && m100 <= 14)) return 'пользователя';
            return 'пользователей';
        }

        function refresh() {
            const n = boxes().filter(b => b.checked).length;
            if (counter) {
                counter.textContent = n ? `${n} ${word(n)}` : 'Отметьте в таблице';
                counter.classList.toggle('text-indigo-700', n > 0);
                counter.classList.toggle('font-semibold', n > 0);
            }
            // Кнопка активна, только когда выбраны люди, выбрано действие и (для
            // назначения роли) выбрана сама роль — иначе запрос всё равно упадёт
            // на валидации role_id.
            const needRole = actionSel?.value === 'assign_role';
            const roleOk = !needRole || (roleSel && roleSel.value);
            if (submitBtn) submitBtn.disabled = !(n > 0 && actionSel && actionSel.value && roleOk);
            if (selectAll) {
                selectAll.checked = n > 0 && n === boxes().length;
                selectAll.indeterminate = n > 0 && n < boxes().length;
            }
        }

        selectAll?.addEventListener('change', function () {
            boxes().forEach(cb => (cb.checked = this.checked));
            refresh();
        });

        boxes().forEach(cb => cb.addEventListener('change', refresh));

        const roleWrap = document.getElementById('bulkRoleWrap');

        actionSel?.addEventListener('change', function () {
            const needRole = this.value === 'assign_role';
            if (roleWrap) roleWrap.style.display = needRole ? 'block' : 'none';
            if (roleSel) {
                roleSel.required = needRole;
                if (!needRole) roleSel.value = '';
            }
            refresh();
        });

        // Под селектом показываем описание выбранной роли — понятно, что она даёт
        const roleHint = document.getElementById('bulkRoleHint');
        roleSel?.addEventListener('change', function () {
            if (roleHint) {
                const opt = this.options[this.selectedIndex];
                roleHint.textContent = opt?.dataset?.description || '';
            }
            refresh();
        });

        document.getElementById('bulkForm')?.addEventListener('submit', function (e) {
            const n = boxes().filter(b => b.checked).length;
            if (actionSel?.value === 'delete' && !confirm(`Удалить выбранных пользователей (${n})? Действие необратимо.`)) {
                e.preventDefault();
            }
        });

        refresh();
    })();
</script>
@endpush
