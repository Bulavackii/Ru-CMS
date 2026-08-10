{{-- ── Полоса шагов входа ───────────────────────────────────────────────
     Вход в этой CMS — последовательность из двух шагов: почта с паролем,
     затем код из приложения. Второй шаг до сих пор был невидим: о нём
     узнавали, только упёршись в него, а вернувшись на форму — набирали
     пароль заново.

     Нумерация здесь не украшение: это настоящая последовательность, и
     номер несёт сведения, которых иначе нет на странице. У второго шага
     стоит оговорка «если включена проверка» — без неё полоса обещала бы
     шаг тем, у кого проверка выключена.

     Оформление — язык разделов «Фрагменты» и «Меню»: прямые края, волосяные
     линии, моноширинные микроподписи. Общая часть этого языка (края,
     заголовок, подписи полей) живёт в лейауте — она нужна всем экранам
     входа. Здесь только стили самой полосы, и печатаются они один раз:
     партиал включают две страницы последовательности.

     Параметры:
       $step — текущий шаг, 1 или 2
       $link — адрес второго шага, если на него можно перейти прямо сейчас
--}}

@php
    $step = $step ?? 1;
    $link = $link ?? null;

    $marks = [
        1 => ['n' => '01', 'title' => __('frontend.auth.step_password')],
        2 => ['n' => '02', 'title' => __('frontend.auth.step_code')],
    ];
@endphp

<ol class="sif-rail" aria-label="{{ __('frontend.auth.steps_label') }}">
    @foreach ($marks as $index => $mark)
        @php
            $state = $index === $step ? 'is-now' : ($index < $step ? 'is-done' : 'is-next');
            $tag = ($index === 2 && $link && $index !== $step) ? 'a' : 'span';
        @endphp

        <li class="sif-step {{ $state }}">
            <{{ $tag }} class="sif-step__body" @if($tag === 'a') href="{{ $link }}" @endif>
                <span class="sif-step__n">{{ $mark['n'] }}</span>
                <span class="sif-step__t">{{ $mark['title'] }}</span>
                {{-- Оговорка нужна ровно до тех пор, пока неизвестно, включена
                     ли проверка. Если шаг уже начат (есть куда перейти) —
                     она превратилась бы в неправду. --}}
                @if ($index === 2 && $state === 'is-next' && ! $link)
                    <span class="sif-step__if">{{ __('frontend.auth.step_code_if') }}</span>
                @endif
                @if ($tag === 'a')
                    <i class="fas fa-arrow-right sif-step__go" aria-hidden="true"></i>
                @endif
            </{{ $tag }}>
        </li>
    @endforeach
</ol>

@once
    @push('styles')
    <style>
        /* Оформление полосы. Прямые края, крупный заголовок и моноширинные
           подписи полей — общие для всех экранов входа и живут в лейауте:
           здесь остаётся только сама полоса. */

        /* ── Полоса шагов ── */
        .sif-rail {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0;
            margin: 0 0 16px; padding: 0; list-style: none;
            border: 1px solid var(--au-line);
        }
        .sif-step + .sif-step { border-left: 1px solid var(--au-line) }

        .sif-step__body {
            display: block; height: 100%; padding: 8px 10px;
            text-decoration: none; color: inherit;
        }
        a.sif-step__body { transition: background .15s ease }
        a.sif-step__body:hover { background: var(--au-soft) }

        .sif-step__n {
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .62rem; font-weight: 700; letter-spacing: .14em;
            color: var(--au-muted);
        }
        .sif-step__t {
            display: block; margin-top: 2px;
            font-size: .78rem; font-weight: 600; line-height: 1.25;
            color: color-mix(in srgb, var(--au-text) 62%, var(--au-card));
        }
        .sif-step__if {
            display: block; margin-top: 1px;
            font-size: .66rem; line-height: 1.3; color: var(--au-muted);
        }
        .sif-step__go { margin-top: 3px; font-size: .62rem; color: var(--au-primary) }

        /* Текущий шаг: заливка акцентом и подчёркивающая линия снизу —
           положение в полосе видно, не считая номера глазами. */
        .sif-step.is-now { position: relative; background: color-mix(in srgb, var(--au-primary) 8%, var(--au-card)) }
        .sif-step.is-now::after {
            content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px;
            background: linear-gradient(90deg, var(--au-primary), var(--au-accent));
        }
        /* Чистый акцент на этой подложке даёт 3.56 — для полужирной подписи
           в 0.62rem мало. Индиго, приглушённый цветом текста: тот же цвет
           по смыслу, но 5.42. */
        .sif-step.is-now .sif-step__n { color: color-mix(in srgb, var(--au-primary) 70%, var(--au-text)) }
        .sif-step.is-now .sif-step__t { color: var(--au-text) }

        /* Пройденный шаг остаётся читаемым: он же — путь назад. */
        .sif-step.is-done .sif-step__n { color: color-mix(in srgb, var(--au-primary) 70%, var(--au-muted)) }

        @media (prefers-reduced-motion: reduce) {
            a.sif-step__body { transition: none }
        }
    </style>
    @endpush
@endonce
