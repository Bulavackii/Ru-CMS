@extends('layouts.admin')

@section('title', __('admin.users.page_create'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-user-plus"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.users.new_user') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.users.new_hint') }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.common.back_to_list') }}
        </a>
    </div>

    @if($errors->any())
        <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
            <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">
                <i class="fas fa-triangle-exclamation mr-1"></i> {{ __('admin.users.check_form') }}
            </p>
            <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

            {{-- ── Левая колонка: данные ── --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Основное --}}
                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-id-card text-indigo-500"></i> {{ __('admin.users.main_data') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('admin.users.name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="{{ __('admin.users.name_ph') }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('name')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                   placeholder="example@domain.com"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('email')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                            <p class="admin-hint mt-1">{{ __('admin.users.email_hint') }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.users.phone') }}</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                   placeholder="+7 (999) 123-45-67"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('phone')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Пароль --}}
                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-lock text-indigo-500"></i> {{ __('admin.users.password') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('admin.users.password') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password" name="password" required
                                   placeholder="{{ __('admin.users.password_ph') }}" autocomplete="new-password"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('password')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('admin.users.password_repeat') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   placeholder="{{ __('admin.users.password_repeat_ph') }}" autocomplete="new-password"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 mt-3 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" id="showPassword" class="border-gray-400 dark:border-gray-600">
                        {{ __('admin.users.show_password') }}
                    </label>
                </div>

                {{-- Адрес --}}
                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-location-dot text-indigo-500"></i> {{ __('admin.users.address') }}
                        <span class="normal-case font-normal tracking-normal text-gray-400">{{ __('admin.users.optional') }}</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="postal_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.users.zip') }}</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                                   placeholder="123456" inputmode="numeric"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('postal_code')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="region" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.users.region') }}</label>
                            <input type="text" id="region" name="region" value="{{ old('region') }}"
                                   placeholder="{{ __('admin.users.region_ph') }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('region')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.users.city') }}</label>
                            <input type="text" id="city" name="city" value="{{ old('city') }}"
                                   placeholder="{{ __('admin.users.city_ph') }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('city')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.users.street') }}</label>
                            <input type="text" id="address" name="address" value="{{ old('address') }}"
                                   placeholder="{{ __('admin.users.street_ph') }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @error('address')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Правая колонка: доступ ── --}}
            <div class="space-y-5">

                {{-- Тип учётной записи --}}
                <div class="admin-card p-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-shield-halved text-indigo-500"></i> {{ __('admin.users.access') }}
                    </h2>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <span class="admin-toggle">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}>
                            <span class="track"></span>
                            <span class="knob"></span>
                        </span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('admin.users.admin') }}</span>
                    </label>

                    <p class="admin-hint mt-3">
                        {{ __('admin.users.access_hint') }}
                    </p>
                </div>

                {{-- Роли --}}
                <div class="admin-card p-5" id="rolesSection">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-tag text-indigo-500"></i> {{ __('admin.users.roles') }}
                    </h2>

                    @forelse($roles->sortByDesc('priority') as $role)
                        <label class="flex items-start gap-3 border border-gray-200 dark:border-gray-700 p-3 mb-2 cursor-pointer
                                      hover:border-indigo-400 transition">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                   {{ old('roles') && in_array($role->id, (array) old('roles')) ? 'checked' : '' }}
                                   class="mt-1 border-gray-400 dark:border-gray-600">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $role->name }}</span>
                                    <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5">
                                        {{ $role->permissions_count }} {{ trans_choice('admin.users.rights_plural', $role->permissions_count) }}
                                    </span>
                                </span>
                                @if($role->description)
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $role->description }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="admin-hint">
                            {{ __('admin.users.no_roles') }} <span class="font-mono">RbacSeeder</span> {{ __('admin.users.on_install') }}
                        </p>
                    @endforelse

                    @if($roles->isNotEmpty())
                        <p class="admin-hint mt-3">
                            {{ __('admin.users.roles_hint') }}
                        </p>
                    @endif
                </div>

                {{-- Действия --}}
                <div class="admin-card p-4 flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                                   px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                        <i class="fas fa-user-plus"></i> {{ __('admin.admin.create') }}
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                       class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                              text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                              px-4 py-2 text-sm font-semibold transition">
                        {{ __('admin.admin.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        // Блок ролей не нужен администратору — он и так получает полный доступ
        const isAdmin = document.getElementById('is_admin');
        const roles = document.getElementById('rolesSection');
        if (isAdmin && roles) {
            const sync = () => { roles.style.display = isAdmin.checked ? 'none' : ''; };
            isAdmin.addEventListener('change', sync);
            sync();
        }

        // Показать/скрыть оба поля пароля сразу
        const show = document.getElementById('showPassword');
        if (show) {
            show.addEventListener('change', function () {
                const type = this.checked ? 'text' : 'password';
                ['password', 'password_confirmation'].forEach(id => {
                    const field = document.getElementById(id);
                    if (field) field.type = type;
                });
            });
        }
    })();
</script>
@endpush
