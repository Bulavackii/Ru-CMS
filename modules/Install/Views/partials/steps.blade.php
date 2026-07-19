{{--
    Единый индикатор шагов мастера установки (монохром: чёрный/белый/серый).
    Использование: @include('Install::partials.steps', ['current' => 'database'])
    'current' — ключ текущего шага из списка ниже. 'features' сознательно
    не входит в список: это необязательная информационная страница-«отступление»,
    а не шаг мастера, который нужно пройти.
--}}
@php
    $__installSteps = [
        'welcome'      => ['label' => 'Приветствие',    'icon' => 'sparkles'],
        'requirements' => ['label' => 'Требования',     'icon' => 'clipboard-check'],
        'database'     => ['label' => 'База данных',    'icon' => 'database'],
        'admin'        => ['label' => 'Администратор',  'icon' => 'user-round'],
        'license'      => ['label' => 'Лицензия',       'icon' => 'key-round'],
        'demo'         => ['label' => 'Демо-данные',    'icon' => 'package'],
        'finish'       => ['label' => 'Готово',         'icon' => 'check-circle-2'],
    ];
    $__installStepKeys = array_keys($__installSteps);
    $__installCurrentIndex = array_search($current ?? 'welcome', $__installStepKeys, true);
    if ($__installCurrentIndex === false) {
        $__installCurrentIndex = 0;
    }
@endphp
<ol class="flex flex-wrap items-center justify-center gap-1.5" aria-label="Шаги установки">
    @foreach ($__installSteps as $__stepKey => $__step)
        @php
            $__index = array_search($__stepKey, $__installStepKeys, true);
            $__isDone = $__index < $__installCurrentIndex;
            $__isCurrent = $__index === $__installCurrentIndex;
        @endphp
        <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium transition-colors
            {{ $__isCurrent
                ? 'bg-gray-900 text-white shadow-sm'
                : ($__isDone ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 text-gray-400') }}"
            title="{{ $__step['label'] }}">
            @if ($__isDone)
                <i data-lucide="check" class="w-3 h-3"></i>
            @else
                <i data-lucide="{{ $__step['icon'] }}" class="w-3 h-3"></i>
            @endif
            <span class="hidden md:inline">{{ $__step['label'] }}</span>
        </li>
    @endforeach
</ol>
