{{--
    Единый индикатор шагов мастера установки.
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
<ol class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2" aria-label="Шаги установки">
    @foreach ($__installSteps as $__stepKey => $__step)
        @php
            $__index = array_search($__stepKey, $__installStepKeys, true);
            $__isDone = $__index < $__installCurrentIndex;
            $__isCurrent = $__index === $__installCurrentIndex;
        @endphp
        <li class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-[11px] sm:text-xs font-medium transition-colors
            {{ $__isCurrent
                ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/30'
                : ($__isDone ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500') }}">
            @if ($__isDone)
                <i data-lucide="check" class="w-3 h-3"></i>
            @else
                <span class="tabular-nums">{{ $__index + 1 }}</span>
            @endif
            <span class="hidden sm:inline">{{ $__step['label'] }}</span>
        </li>
    @endforeach
</ol>
