{{--
    Единый индикатор шагов мастера установки.
    Использование: @include('Install::partials.steps', ['current' => 'database'])
    'current' — ключ текущего шага. 'features' сознательно не входит в список:
    это необязательная информационная страница-«отступление», а не шаг мастера.

    Сверху — подпись «Шаг N из M» и полоса общего прогресса на всю ширину
    (заполняется слева направо). Ниже — чипы шагов: пройденные зелёные с
    галочкой, текущий залит цветом акцента со свечением, будущие — стекло.
--}}
@php
    // 'optional' => true — шаг можно пропустить, в чипе он помечается точкой.
    $__installSteps = [
        'welcome'      => ['label' => __('install.steps.welcome'),      'icon' => 'sparkles'],
        'requirements' => ['label' => __('install.steps.requirements'), 'icon' => 'clipboard-check'],
        'database'     => ['label' => __('install.steps.database'),     'icon' => 'database'],
        'admin'        => ['label' => __('install.steps.admin'),        'icon' => 'user-round'],
        'smtp'         => ['label' => __('install.steps.smtp'),         'icon' => 'mail', 'optional' => true],
        'license'      => ['label' => __('install.steps.license'),      'icon' => 'key-round'],
        'demo'         => ['label' => __('install.steps.demo'),         'icon' => 'package'],
        'finish'       => ['label' => __('install.steps.finish'),       'icon' => 'check-circle-2'],
    ];
    $__installStepKeys = array_keys($__installSteps);
    $__installCurrentIndex = array_search($current ?? 'welcome', $__installStepKeys, true);
    if ($__installCurrentIndex === false) {
        $__installCurrentIndex = 0;
    }
    $__installTotal = count($__installStepKeys) - 1;
    $__installProgress = $__installTotal > 0 ? round(($__installCurrentIndex / $__installTotal) * 100) : 0;
@endphp
<div class="space-y-2.5" aria-label="{{ __('install.steps.aria') }}">
    {{-- Заголовок прогресса + полоса на всю ширину --}}
    <div>
        <div class="flex items-center justify-between mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
            <span class="flex items-center gap-1">
                <span style="color:var(--accent)">{{ __('install.steps.step') }} {{ $__installCurrentIndex + 1 }}</span>
                <span>{{ __('install.steps.of') }} {{ $__installTotal + 1 }}</span>
                <span class="hidden sm:inline text-gray-300">·</span>
                <span class="hidden sm:inline normal-case tracking-normal text-gray-500">{{ $__installSteps[$__installStepKeys[$__installCurrentIndex]]['label'] }}</span>
            </span>
            <span style="color:var(--accent)">{{ $__installProgress }}%</span>
        </div>
        <div class="h-1.5 w-full bg-black/[.06] overflow-hidden">
            <div class="h-full transition-all duration-500 ease-out"
                 style="width: {{ $__installProgress }}%; background: linear-gradient(90deg, color-mix(in srgb, var(--accent) 50%, #fff), var(--accent))"></div>
        </div>
    </div>

    {{-- Чипы шагов --}}
    <ol class="flex flex-wrap items-center justify-center gap-1.5">
        @foreach ($__installSteps as $__stepKey => $__step)
            @php
                $__index = array_search($__stepKey, $__installStepKeys, true);
                $__isDone = $__index < $__installCurrentIndex;
                $__isCurrent = $__index === $__installCurrentIndex;
                $__isOptional = $__step['optional'] ?? false;

                $__tip = __('install.steps.chip_tip', [
                    'n'     => $__index + 1,
                    'total' => $__installTotal + 1,
                    'label' => $__step['label'],
                ]);
                if ($__isOptional) {
                    $__tip .= ' · ' . __('install.smtp.optional');
                }
            @endphp
            <li class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-all
                {{ $__isCurrent ? 'text-white' : ($__isDone ? '' : 'text-gray-400') }}"
                @if ($__isCurrent)
                    style="background-color:var(--accent);box-shadow:0 8px 18px -6px color-mix(in srgb, var(--accent) 60%, transparent)"
                @elseif ($__isDone)
                    style="background-color:#dcfce7;color:#15803d"
                @else
                    style="background-color:rgba(255,255,255,.55);box-shadow:inset 0 0 0 1px rgba(0,0,0,.06)"
                @endif
                data-tip="{{ $__tip }}"
                data-tip-pos="bottom">
                @if ($__isDone)
                    <i data-lucide="check" class="w-3 h-3"></i>
                @else
                    <i data-lucide="{{ $__step['icon'] }}" class="w-3 h-3"></i>
                @endif
                <span class="hidden md:inline">{{ $__step['label'] }}</span>
                @if ($__isOptional)
                    {{-- Точка-маркер: шаг можно пропустить. Расшифровка — в тултипе. --}}
                    <span class="w-1 h-1 rounded-full shrink-0 {{ $__isCurrent ? 'bg-white/70' : 'bg-gray-300' }}"></span>
                @endif
            </li>
        @endforeach
    </ol>
</div>
