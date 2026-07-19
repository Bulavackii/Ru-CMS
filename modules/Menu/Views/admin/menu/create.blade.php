@extends('layouts.admin')

@section('title', 'Создать меню')

@section('content')
    {{-- Заголовок --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">➕ Создать новое меню</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Укажите название, позицию и активируйте меню. Пункты вы добавите после сохранения.
            </p>
        </div>

        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:underline">
            @themeIcon('arrow-left') Назад к списку
        </a>
    </div>

    {{-- Ошибки валидации --}}
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-100 dark:border-red-800">
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
          class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-2xl shadow p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                       class="w-full border border-gray-300 dark:border-gray-700 rounded-md px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500"
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
                    <span class="text-xs text-gray-500 dark:text-gray-400">Используется для размещения на сайте</span>
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
                                class="pos-card relative text-left rounded-xl border p-4 transition
                                       {{ $pos === $c['key'] ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-900/40' : 'border-gray-300 dark:border-gray-700 hover:border-gray-400' }}">
                            <div class="flex items-start gap-3">
                                <span class="text-xl text-blue-600 dark:text-blue-400">@themeIcon($c['icon'])</span>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $c['title'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $c['desc'] }}</div>
                                </div>
                            </div>
                            @if($pos === $c['key'])
                                <span class="absolute top-2 right-2 text-green-600">@themeIcon('check')</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Активность --}}
            <div>
                <label class="inline-flex items-center gap-3 select-none">
                    <input type="checkbox" name="active" value="1"
                           {{ old('active', '1') ? 'checked' : '' }}
                           class="peer sr-only">
                    <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 peer-checked:bg-green-500 transition-all">
                        <span class="absolute left-1 peer-checked:left-6 h-4 w-4 rounded-full bg-white transition-all"></span>
                    </span>
                    <span class="text-sm text-gray-800 dark:text-gray-200">Активировать меню</span>
                </label>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Меню будет отображаться на сайте (если есть пункты).
                </div>
            </div>
        </div>

        {{-- Правая колонка: превью --}}
        <aside class="lg:col-span-1">
            <div class="rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">Превью</div>
                <div id="menuPreview" class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-blue-600">@themeIcon('bars')</span>
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

                <div class="mt-4 rounded-lg border border-blue-200/70 dark:border-blue-900/40 bg-blue-50 dark:bg-blue-900/30 p-3 text-xs text-blue-900 dark:text-blue-100">
                    @themeIcon('lightbulb') <b>Подсказка:</b> пункты добавляются после сохранения, во вкладке «Редактировать».
                </div>
            </div>
        </aside>

        {{-- Липкий бар действий --}}
        <div class="lg:col-span-3">
            <div class="sticky bottom-3 z-10 rounded-xl border bg-white/90 dark:bg-gray-900/90 backdrop-blur px-4 py-3
                        border-gray-300 dark:border-gray-700 shadow flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @themeIcon('keyboard') Горячая клавиша: <b>Ctrl + S</b> — сохранить.
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="bg-black hover:bg-gray-800 text-white px-5 py-2 rounded-md text-sm shadow transition">
                        @themeIcon('save') Сохранить меню
                    </button>
                    <a href="{{ route('admin.menus.index') }}"
                       class="px-4 py-2 rounded-md text-sm border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
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

    // Карточки позиции
    document.querySelectorAll('.pos-card').forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.pos;
            posHidden.value = value;
            previewPos.textContent = value;

            document.querySelectorAll('.pos-card').forEach(b => {
                b.classList.remove('border-blue-500','ring-2','ring-blue-200','dark:ring-blue-900/40');
                b.classList.add('border-gray-300','dark:border-gray-700');
                const check = b.querySelector('.pos-check');
                if (check) check.remove();
            });

            btn.classList.add('border-blue-500','ring-2','ring-blue-200','dark:ring-blue-900/40');
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
