@extends('layouts.admin')

@section('title', __('admin.users.password_change'))

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-key"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.users.password_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ $user->name }} · {{ $user->email }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.users.edit', $user) }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.users.to_user') }}
        </a>
    </div>

    @if($errors->any())
        <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
            <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.password.update', $user->id) }}" method="POST"
          class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        @csrf
        @method('PUT')

        <div class="lg:col-span-2 admin-card p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-lock text-indigo-500"></i> {{ __('admin.users.new_password') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('admin.users.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" id="password" required autocomplete="new-password"
                           placeholder="{{ __('admin.users.password_ph') }}"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    @error('password')<p class="text-sm text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('admin.users.password_repeat') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                           placeholder="{{ __('admin.users.password_repeat_ph') }}"
                           class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <label class="inline-flex items-center gap-2 mt-3 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                <input type="checkbox" id="showPassword" class="border-gray-400 dark:border-gray-600">
                {{ __('admin.users.show_password') }}
            </label>
        </div>

        <div class="space-y-5">
            <div class="admin-card p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                    <i class="fas fa-circle-info text-indigo-500"></i> {{ __('admin.users.what_happens') }}
                </h2>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-1"></i>
                        <span>{{ __('admin.users.pwd_note_1') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-1"></i>
                        <span>{{ __('admin.users.pwd_note_2') }}</span>
                    </li>
                </ul>
                <p class="admin-hint mt-3">
                    {{ __('admin.users.pwd_note_3') }}
                </p>
            </div>

            <div class="admin-card p-4 flex items-center gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               px-4 py-2 text-sm font-semibold shadow-sm transition flex-1">
                    <i class="fas fa-save"></i> {{ __('admin.users.update_password') }}
                </button>
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800
                          px-4 py-2 text-sm font-semibold transition">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const show = document.getElementById('showPassword');
        if (!show) return;
        show.addEventListener('change', function () {
            const type = this.checked ? 'text' : 'password';
            ['password', 'password_confirmation'].forEach(id => {
                const field = document.getElementById(id);
                if (field) field.type = type;
            });
        });
    })();
</script>
@endpush
