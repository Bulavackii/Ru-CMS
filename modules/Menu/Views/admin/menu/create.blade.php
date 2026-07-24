@extends('layouts.admin')

@section('title', 'Создать меню')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge">@themeIcon('plus')</span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Создать меню</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Название, позиция и активность. Пункты добавляются после сохранения.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 shrink-0">
            @themeIcon('arrow-left') К списку
        </a>
    </div>

    {{-- Ошибки валидации --}}
    @if ($errors->any())
        <div class="mb-6 border-l-4 border-red-500 bg-red-50 dark:bg-red-900 px-4 py-3 text-sm text-red-800 dark:text-red-100">
            <div class="font-semibold mb-1">@themeIcon('exclamation-triangle') Пожалуйста, исправьте ошибки:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Форма --}}
    <form id="menuCreateForm"
          action="{{ route('admin.menus.store') }}"
          method="POST"
          class="admin-card p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        {{-- Левая колонка: поля --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Название --}}
            <div>
                <label for="title" class="block text-sm font-semibold mb-1 text-gray-800 dark:text-gray-200">
                    🏷️ Название меню
                </label>
                <input type="text" id="title" name="title" maxlength="80" autocomplete="off"
                       value="{{ old('title') }}"
                       class="w-full border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Например: Основное меню" required>
                <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Понятное имя, чтобы различать меню в админке.</span>
                    <span><span id="titleCounter">0</span>/80</span>
                </div>
            </div>

            {{-- Позиция меню (карточки) --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200">📍 Позиция меню</label>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Где разместить на сайте</span>
                </div>

                <input type="hidden" name="position" id="positionHidden" value="{{ old('position','header') }}">

                <div class="grid sm:grid-cols-3 gap-3" role="tablist" aria-label="Позиция меню">
                    @php
                        $pos = old('position', 'header');
                        $cards = [
                            ['key' => 'header',  'title' => 'Шапка',   'desc' => 'Навигация вверху', 'icon' => 'window-maximize'],
                            ['key' => 'footer',  'title' => 'Подвал',   'desc' => 'Ссылки внизу',     'icon' => 'window-minimize'],
                            ['key' => 'sidebar', 'title' => 'Сайдбар',  'desc' => 'Боковая панель',  'icon' => 'columns'],
                        ];
                    @endphp
                    @foreach($cards as $c)
                        <button type="button"
                                data-pos="{{ $c['key'] }}"
                                class="pos-card relative text-left border p-4 transition
                                       {{ $pos === $c['key'] ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-300 dark:border-gray-700 hover:border-indigo-400' }}">
                            <div class="flex items-start gap-3">
                                <span class="text-xl text-indigo-600 dark:text-indigo-400">@themeIcon($c['icon'])</span>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $c['title'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $c['desc'] }}</div>
                                </div>
                            </div>
                            <span class="pos-check absolute top-2 right-2 text-indigo-600 dark:text-indigo-400 {{ $pos === $c['key'] ? '' : 'hidden' }}">@themeIcon('check')</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Активность --}}
            <div>
                <label class="inline-flex items-center gap-2.5 select-none cursor-pointer">
                    <span class="admin-toggle">
                        <input type="checkbox" name="active" value="1" {{ old('active', '1') ? 'checked' : '' }}>
                        <span class="track"></span>
                        <span class="knob"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Активировать меню</span>
                </label>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Меню будет отображаться на сайте (если есть пункты).
                </div>
            </div>
        </div>

        {{-- Правая колонка: превью --}}
        <aside class="lg:col-span-1">
            <div class="border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">Превью</div>
                <div id="menuPreview" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-indigo-600 dark:text-indigo-400">@themeIcon('bars')</span>
                        <span id="previewTitle" class="font-medium text-gray-900 dark:text-white">Основное меню</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Позиция: <span id="previewPos" class="font-medium">header</span>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Статус: <span id="previewStatus" class="inline-flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-green-500 inline-block"></span> Активно
                        </span>
                    </div>
                </div>

                <div class="admin-hint mt-4 p-3 text-xs">
                    @themeIcon('lightbulb') <b>Подсказка:</b> пункты добавляются после сохранения, во вкладке «Редактировать».
                </div>
            </div>
        </aside>

        {{-- Липкий бар действий --}}
        <div class="lg:col-span-3">
            <div class="admin-glass sticky bottom-3 z-10 border px-4 py-3
                        border-gray-300 dark:border-gray-700 shadow flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @themeIcon('keyboard') Горячая клавиша: <b>Ctrl + S</b> — сохранить.
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                        @themeIcon('save') Сохранить меню
                    </button>
                    <a href="{{ route('admin.menus.index') }}"
                       class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Отмена
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Счётчик символов + превью
    const titleInput   = document.getElementById('title');
    const titleCounter = document.getElementById('titleCounter');
    const previewTitle = document.getElementById('previewTitle');
    const previewPos   = document.getElementById('previewPos');
    const previewStatus= document.getElementById('previewStatus');
    const posHidden    = document.getElementById('positionHidden');

    function updateTitle() {
        titleCounter.textContent = (titleInput.value || '').length;
        previewTitle.textContent = titleInput.value.trim() || 'Основное меню';
    }
    titleInput.addEventListener('input', updateTitle);
    updateTitle();

    // Карточки позиции — подсветка индиго-акцентом (border + фон, без ring/opacity,
    // которых нет в статической Tailwind-сборке)
    document.querySelectorAll('.pos-card').forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.pos;
            posHidden.value = value;
            previewPos.textContent = value;

            document.querySelectorAll('.pos-card').forEach(b => {
                const on = b === btn;
                b.classList.toggle('border-indigo-500', on);
                b.classList.toggle('bg-indigo-50', on);
                b.classList.toggle('dark:bg-indigo-900', on);
                b.classList.toggle('border-gray-300', !on);
                b.classList.toggle('dark:border-gray-700', !on);
                b.querySelector('.pos-check')?.classList.toggle('hidden', !on);
            });
        });
    });

    // Статус из чекбокса
    document.querySelector('input[name="active"]').addEventListener('change', (e) => {
        previewStatus.innerHTML = e.target.checked
            ? '<span class="h-2 w-2 rounded-full bg-green-500 inline-block"></span> Активно'
            : '<span class="h-2 w-2 rounded-full bg-gray-400 inline-block"></span> Не активно';
    });

    // Ctrl+S => submit
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            document.getElementById('menuCreateForm').submit();
        }
    });

    // Фокус на название
    setTimeout(() => titleInput?.focus(), 50);
</script>
@endpush
