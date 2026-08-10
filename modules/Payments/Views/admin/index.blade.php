@extends('layouts.admin')

@section('title', __('admin.payments.title'))

@section('content')
@php
    use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;

    $required = SeedDefaultPaymentMethodsCommand::credentialFields();
@endphp

{{-- ── Шапка раздела ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-credit-card"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.payments.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.payments.subtitle') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.payments.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> {{ __('admin.payments.add') }}
    </a>
</div>


{{-- ── Сводка ──
     Тот же приём, что в «Меню»: главный вопрос раздела — что из этого
     реально принимает деньги. Раньше это выяснялось пересчётом плашек
     глазами: метод работает, только если он включён И у него есть ключи. --}}
@php
    $live = $methods->filter(fn ($m) => $m->active && ! collect($required[$m->type] ?? [])
        ->filter(fn ($f) => blank(((array) $m->settings)[$f] ?? null))->isNotEmpty())->count();
    $noKeys = $methods->filter(fn ($m) => collect($required[$m->type] ?? [])
        ->filter(fn ($f) => blank(((array) $m->settings)[$f] ?? null))->isNotEmpty())->count();
@endphp

<div class="pm-summary mb-4">
    <span class="pm-sum"><i class="fas fa-credit-card"></i> {{ __('admin.payments.sum_total') }} <b>{{ $methods->count() }}</b></span>
    <span class="pm-sum {{ $live ? 'is-on' : '' }}"><i class="fas fa-circle-check"></i> {{ __('admin.payments.sum_live') }} <b>{{ $live }}</b></span>
    @if($noKeys)
        <span class="pm-sum is-warn"><i class="fas fa-key"></i> {{ __('admin.payments.sum_nokeys') }} <b>{{ $noKeys }}</b></span>
    @endif
</div>

<div class="pm-grid">
@forelse($methods as $method)
    @php
        // Метод без ключей выглядит рабочим, но платёж не примет —
        // об этом надо сказать прямо в списке, а не внутри формы.
        $needed = $required[$method->type] ?? [];
        $settings = (array) $method->settings;
        $missing = collect($needed)->filter(fn ($field) => blank($settings[$field] ?? null))->isNotEmpty();
        $brand = $method->brand();
    @endphp

    {{-- Фирменный цвет приходит переменной, а не классом: цветов столько
         же, сколько методов, и перечислять их в CSS пришлось бы дважды. --}}
    <article class="pm-card {{ $method->active ? '' : 'is-off' }}"
             style="--pm:{{ $brand['color'] }}; --pm-ink:{{ $brand['ink'] }}">
        <div class="pm-card__bar"></div>

        <div class="pm-card__head">
            {{-- Настоящий знак, если файл положен; иначе значок способа
                 оплаты. Подложка под логотипом белая: знаки рисуются под
                 светлый фон, на фирменном цвете они бы слились. --}}
            <span class="pm-logo {{ $brand['logo'] ? 'has-logo' : '' }}">
                @if($brand['logo'])
                    <img src="{{ $brand['logo'] }}" alt="{{ $method->title }}" loading="lazy">
                @else
                    <i class="fas {{ $brand['icon'] }}"></i>
                @endif
            </span>

            <div class="pm-card__name">
                <b class="pm-title">{{ $method->title }}</b>
                @if($method->code)
                    <code class="pm-code">{{ $method->code }}</code>
                @endif
            </div>

            <span class="pm-state {{ $method->active ? 'is-on' : '' }}">
                <i class="fas {{ $method->active ? 'fa-circle-check' : 'fa-ban' }}"></i>
                {{ $method->active ? __('admin.payments.on') : __('admin.payments.off') }}
            </span>
        </div>

        @if($method->description)
            <p class="pm-desc">{{ $method->description }}</p>
        @endif

        <div class="pm-chips">
            <span class="pm-chip"><i class="fas fa-percent"></i> {{ (float) $method->commission }}%</span>

            @if($method->min_amount !== null || $method->max_amount !== null)
                <span class="pm-chip">
                    <i class="fas fa-arrows-left-right"></i>
                    {{ $method->min_amount !== null ? (int) $method->min_amount : '—' }}…{{ $method->max_amount !== null ? (int) $method->max_amount : '∞' }}
                </span>
            @endif

            @if($method->test_mode)
                <span class="pm-chip is-test"><i class="fas fa-flask"></i> {{ __('admin.payments.test_mode') }}</span>
            @endif

            @if($missing)
                <span class="pm-chip is-bad" title="{{ __('admin.payments.no_keys_hint') }}">
                    <i class="fas fa-key"></i> {{ __('admin.payments.no_keys') }}
                </span>
            @endif
        </div>

        <div class="pm-card__foot">
            @if($method->docs_url)
                <a href="{{ $method->docs_url }}" target="_blank" rel="noopener" class="pm-docs">
                    <i class="fas fa-book"></i> {{ __('admin.payments.docs') }}
                </a>
            @endif

            <span class="pm-actions">
                <a href="{{ route('admin.payments.edit', $method->id) }}" class="pm-icon"
                   title="{{ __('admin.payments.act_edit') }}"><i class="fas fa-pen"></i></a>

                <form action="{{ route('admin.payments.destroy', $method->id) }}" method="POST"
                      onsubmit="return confirm(@js(__('admin.payments.confirm_delete', ['name' => $method->title])))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pm-icon pm-icon--danger"
                            title="{{ __('admin.payments.act_delete') }}"><i class="fas fa-trash-can"></i></button>
                </form>
            </span>
        </div>
    </article>
@empty
    <div class="admin-card p-10 text-center pm-empty">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-credit-card"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.payments.empty') }}</h2>
        <p class="admin-hint max-w-xl mx-auto">{{ __('admin.payments.empty_hint') }}</p>
    </div>
@endforelse
</div>

@endsection

@push('styles')
<style>
    /* ── Способы оплаты карточками ────────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений. Скругления в панели сняты общим
       рубильником, поэтому классы rounded тут ничего не давали.

       Фирменный цвет приходит переменной --pm со стороны разметки:
       методов столько же, сколько цветов, и перечислять их правилами
       пришлось бы дважды — в CSS и в модели. */

    .pm-summary{ display:flex; flex-wrap:wrap; gap:.5rem }
    .pm-sum{ display:inline-flex; align-items:center; gap:.45rem;
        padding:.4rem .7rem; font-size:.8rem; color:#4b5563;
        background:#f9fafb; border:1px solid #e5e7eb }
    .pm-sum i{ color:#9ca3af }
    .pm-sum b{ color:#111827 }
    .pm-sum.is-on i{ color:#16a34a }
    .pm-sum.is-warn{ color:#92400e; background:#fffbeb; border-color:#f0d9a8 }
    .pm-sum.is-warn i, .pm-sum.is-warn b{ color:#b45309 }
    .dark .pm-sum{ color:#d1d5db; background:#111827; border-color:#374151 }
    .dark .pm-sum b{ color:#f3f4f6 }

    .pm-grid{ display:grid; gap:1rem;
        grid-template-columns:repeat(auto-fill, minmax(20rem, 1fr)) }

    .pm-card{ display:flex; flex-direction:column; gap:.7rem; padding:0 0 .9rem;
        background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, box-shadow .15s }
    .pm-card:hover{ border-color:var(--pm);
        box-shadow:0 6px 18px color-mix(in srgb, var(--pm) 18%, transparent) }
    .dark .pm-card{ background:#111827; border-color:#374151 }

    /* Выключенный метод приглушён, но читаем: он тут не для красоты, его
       ещё настраивать. */
    .pm-card.is-off .pm-logo{ filter:saturate(.35) }
    .pm-card.is-off .pm-title{ color:#6b7280 }
    .dark .pm-card.is-off .pm-title{ color:#9ca3af }

    /* Фирменная полоса — как акцентная полоса разделов панели, но в цвете
       платёжной системы. */
    .pm-card__bar{ height:3px; background:var(--pm) }

    .pm-card__head{ display:flex; align-items:center; gap:.7rem; padding:.9rem 1rem 0 }
    .pm-logo{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:2.5rem; height:2.5rem; font-size:1rem; overflow:hidden;
        color:var(--pm-ink); background:var(--pm) }
    /* С логотипом подложка светлая и с рамкой в фирменном цвете: знаки
       нарисованы под белый фон, на цветном они бы потерялись. */
    .pm-logo.has-logo{ background:#fff; border:1px solid color-mix(in srgb, var(--pm) 35%, transparent); padding:3px }
    .pm-logo img{ width:100%; height:100%; object-fit:contain; display:block }
    .dark .pm-logo.has-logo{ background:#fff }
    .pm-card__name{ display:flex; flex-direction:column; gap:.15rem; min-width:0; flex:1 }
    .pm-title{ font-size:.95rem; font-weight:700; color:#111827;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .pm-title{ color:#f3f4f6 }
    .pm-code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.68rem; color:#9ca3af }

    .pm-state{ display:inline-flex; align-items:center; gap:.3rem; flex:none;
        padding:.15rem .45rem; font-size:.68rem; font-weight:700; white-space:nowrap;
        color:#6b7280; background:#f3f4f6; border:1px solid #e5e7eb }
    .pm-state.is-on{ color:#15803d; background:#dcfce7; border-color:#86efac }
    .dark .pm-state{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .pm-desc{ margin:0; padding:0 1rem; font-size:.82rem; line-height:1.45; color:#6b7280 }
    .dark .pm-desc{ color:#9ca3af }

    .pm-chips{ display:flex; flex-wrap:wrap; gap:.35rem; padding:0 1rem }
    .pm-chip{ display:inline-flex; align-items:center; gap:.3rem;
        padding:.15rem .45rem; font-size:.7rem; font-weight:600; white-space:nowrap;
        color:#4b5563; background:#f9fafb; border:1px solid #e5e7eb }
    .pm-chip i{ font-size:.65rem; color:#9ca3af }
    .pm-chip.is-test{ color:#92400e; background:#fffbeb; border-color:#f0d9a8 }
    .pm-chip.is-test i{ color:#b45309 }
    .pm-chip.is-bad{ color:#b91c1c; background:#fef2f2; border-color:#fecaca }
    .pm-chip.is-bad i{ color:#dc2626 }
    .dark .pm-chip{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .pm-card__foot{ display:flex; align-items:center; gap:.5rem;
        margin-top:auto; padding:.7rem 1rem 0; border-top:1px solid #f1f5f9 }
    .dark .pm-card__foot{ border-top-color:#374151 }

    .pm-docs{ display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem;
        color:var(--pm); text-decoration:none; font-weight:600 }
    .pm-docs:hover{ text-decoration:underline }

    .pm-actions{ display:inline-flex; align-items:center; gap:.35rem; margin-left:auto }
    .pm-icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; font-size:.78rem; cursor:pointer;
        color:#4b5563; background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, color .15s }
    .pm-icon:hover{ border-color:var(--pm); color:var(--pm) }
    .pm-icon--danger:hover{ border-color:#dc2626; color:#b91c1c }
    .dark .pm-icon{ color:#d1d5db; background:#111827; border-color:#374151 }

    .pm-empty{ grid-column:1 / -1 }
</style>
@endpush
