@extends('layouts.admin')

@section('title', __('admin.delivery.title'))

@section('content')
@php
    use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;

    $required = SeedDefaultDeliveryMethodsCommand::credentialFields();

    // Ключи нужны только тому, у кого включён расчёт по API: у самовывоза
    // и курьера по городу цена фиксированная, и предупреждать не о чем.
    $lacksKeys = function ($method) use ($required) {
        $needed = $required[$method->code] ?? [];
        $settings = (array) $method->api_settings;

        return $method->api_enabled
            && collect($needed)->filter(fn ($field) => blank($settings[$field] ?? null))->isNotEmpty();
    };
@endphp

{{-- ── Шапка раздела ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-truck"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.delivery.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.delivery.subtitle') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.delivery.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> {{ __('admin.delivery.add') }}
    </a>
</div>

{{-- ── Сводка ──
     Тот же приём, что в «Оплате» и «Меню»: главный вопрос раздела — что
     из этого реально доступно покупателю. Раньше это выяснялось
     пересчётом плашек глазами. --}}
@php
    $live = $methods->filter(fn ($m) => $m->active && ! $lacksKeys($m))->count();
    $noKeys = $methods->filter($lacksKeys)->count();
@endphp

<div class="dl-summary mb-4">
    <span class="dl-sum"><i class="fas fa-truck"></i> {{ __('admin.delivery.sum_total') }} <b>{{ $methods->count() }}</b></span>
    <span class="dl-sum {{ $live ? 'is-on' : '' }}"><i class="fas fa-circle-check"></i> {{ __('admin.delivery.sum_live') }} <b>{{ $live }}</b></span>
    @if($noKeys)
        <span class="dl-sum is-warn"><i class="fas fa-key"></i> {{ __('admin.delivery.sum_nokeys') }} <b>{{ $noKeys }}</b></span>
    @endif
</div>

<div class="dl-grid">
@forelse($methods as $method)
    @php
        $brand = $method->brand();
        $missing = $lacksKeys($method);
    @endphp

    {{-- Фирменный цвет приходит переменной, а не классом: служб столько
         же, сколько цветов, и перечислять их в CSS пришлось бы дважды. --}}
    <article class="dl-card {{ $method->active ? '' : 'is-off' }}"
             style="--dl:{{ $brand['color'] }}; --dl-ink:{{ $brand['ink'] }}">
        <div class="dl-card__bar"></div>

        <div class="dl-card__head">
            {{-- Настоящий знак службы, если файл положен; иначе значок по
                 типу доставки. Логотипы приведены к квадрату СО СВОИМ
                 фоном: у Почты и Boxberry знак белый, и на белой плитке
                 он был бы не виден вовсе. --}}
            <span class="dl-logo {{ $brand['logo'] ? 'has-logo' : '' }}">
                @if($brand['logo'])
                    <img src="{{ $brand['logo'] }}" alt="{{ $method->title }}" loading="lazy">
                @else
                    <i class="fas {{ $brand['icon'] }}"></i>
                @endif
            </span>

            <div class="dl-card__name">
                <b class="dl-title">{{ $method->title }}</b>
                @if($method->code)
                    <code class="dl-code">{{ $method->code }}</code>
                @endif
            </div>

            <span class="dl-state {{ $method->active ? 'is-on' : '' }}">
                <i class="fas {{ $method->active ? 'fa-circle-check' : 'fa-ban' }}"></i>
                {{ $method->active ? __('admin.delivery.on') : __('admin.delivery.off') }}
            </span>
        </div>

        @if($method->description)
            <p class="dl-desc">{{ $method->description }}</p>
        @endif

        {{-- Цена и срок — главное, за чем сюда заходят, поэтому они
             вынесены строкой, а не подмешаны к остальным чипам. --}}
        <div class="dl-figures">
            <span class="dl-fig">
                <span class="dl-fig__label">{{ __('admin.delivery.price_short') }}</span>
                <b>{{ $method->formatted_price }}</b>
            </span>
            <span class="dl-fig">
                <span class="dl-fig__label">{{ __('admin.delivery.terms') }}</span>
                <b>{{ $method->delivery_days }}</b>
            </span>
        </div>

        <div class="dl-chips">
            @if($method->free_delivery_threshold > 0)
                <span class="dl-chip"><i class="fas fa-gift"></i> {{ __('admin.delivery.from') }}
                    {{ number_format((float) $method->free_delivery_threshold, 0, ',', ' ') }} ₽</span>
            @endif

            @if($method->weight_limit !== null)
                <span class="dl-chip"><i class="fas fa-weight-hanging"></i> {{ (float) $method->weight_limit }} кг</span>
            @endif

            @if($method->api_enabled)
                <span class="dl-chip is-api"><i class="fas fa-plug"></i> {{ __('admin.delivery.api_on') }}</span>
            @endif

            @if($missing)
                <span class="dl-chip is-bad" title="{{ __('admin.delivery.no_keys_hint') }}">
                    <i class="fas fa-key"></i> {{ __('admin.delivery.no_keys') }}
                </span>
            @endif
        </div>

        <div class="dl-card__foot">
            @if($method->docs_url)
                <a href="{{ $method->docs_url }}" target="_blank" rel="noopener" class="dl-docs">
                    <i class="fas fa-book"></i> {{ __('admin.delivery.docs') }}
                </a>
            @endif

            <span class="dl-actions">
                <a href="{{ route('admin.delivery.edit', $method->id) }}" class="dl-icon"
                   title="{{ __('admin.delivery.act_edit') }}"><i class="fas fa-pen"></i></a>

                <form action="{{ route('admin.delivery.destroy', $method->id) }}" method="POST"
                      onsubmit="return confirm(@js(__('admin.delivery.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dl-icon dl-icon--danger"
                            title="{{ __('admin.delivery.act_delete') }}"><i class="fas fa-trash-can"></i></button>
                </form>
            </span>
        </div>
    </article>
@empty
    <div class="admin-card p-10 text-center dl-empty">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-truck"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ __('admin.delivery.empty') }}</h2>
        <p class="admin-hint max-w-xl mx-auto">{{ __('admin.delivery.empty_hint') }}</p>
    </div>
@endforelse
</div>

@endsection

@push('styles')
<style>
    /* ── Службы доставки карточками ───────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений, а скругления сняты общим рубильником.

       Фирменный цвет приходит переменной --dl со стороны разметки:
       служб столько же, сколько цветов, и перечислять их правилами
       пришлось бы дважды — в CSS и в модели. */

    .dl-summary{ display:flex; flex-wrap:wrap; gap:.5rem }
    .dl-sum{ display:inline-flex; align-items:center; gap:.45rem;
        padding:.4rem .7rem; font-size:.8rem; color:#4b5563;
        background:#f9fafb; border:1px solid #e5e7eb }
    .dl-sum i{ color:#9ca3af }
    .dl-sum b{ color:#111827 }
    .dl-sum.is-on i{ color:#16a34a }
    .dl-sum.is-warn{ color:#92400e; background:#fffbeb; border-color:#f0d9a8 }
    .dl-sum.is-warn i, .dl-sum.is-warn b{ color:#b45309 }
    .dark .dl-sum{ color:#d1d5db; background:#111827; border-color:#374151 }
    .dark .dl-sum b{ color:#f3f4f6 }

    .dl-grid{ display:grid; gap:1rem;
        grid-template-columns:repeat(auto-fill, minmax(min(100%, 20rem), 1fr)) }

    .dl-card{ display:flex; flex-direction:column; gap:.6rem; padding:0 0 .8rem;
        background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, box-shadow .15s }
    .dl-card:hover{ border-color:var(--dl);
        box-shadow:0 6px 18px color-mix(in srgb, var(--dl) 18%, transparent) }
    .dark .dl-card{ background:#111827; border-color:#374151 }

    /* Выключенная служба приглушена, но читаема: её ещё настраивать. */
    .dl-card.is-off .dl-logo{ filter:saturate(.35) }
    .dl-card.is-off .dl-title{ color:#6b7280 }
    .dark .dl-card.is-off .dl-title{ color:#9ca3af }

    .dl-card__bar{ height:3px; background:var(--dl) }

    .dl-card__head{ display:flex; align-items:center; gap:.7rem; padding:.9rem 1rem 0 }
    .dl-logo{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:2.5rem; height:2.5rem; font-size:1rem; overflow:hidden;
        color:var(--dl-ink); background:var(--dl) }
    /* Логотип занимает плитку целиком: фирменный фон нарисован в самом
       файле, поэтому отступ и подложка тут только мешали бы. */
    .dl-logo.has-logo{ background:#fff; border:1px solid color-mix(in srgb, var(--dl) 35%, transparent) }
    .dl-logo img{ width:100%; height:100%; object-fit:cover; display:block }

    .dl-card__name{ display:flex; flex-direction:column; gap:.15rem; min-width:0; flex:1 }
    .dl-title{ font-size:.95rem; font-weight:700; color:#111827;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .dl-title{ color:#f3f4f6 }
    .dl-code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.68rem; color:#9ca3af }

    .dl-state{ display:inline-flex; align-items:center; gap:.3rem; flex:none;
        padding:.15rem .45rem; font-size:.68rem; font-weight:700; white-space:nowrap;
        color:#6b7280; background:#f3f4f6; border:1px solid #e5e7eb }
    .dl-state.is-on{ color:#15803d; background:#dcfce7; border-color:#86efac }
    .dark .dl-state{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .dl-desc{ margin:0; padding:0 1rem; font-size:.82rem; line-height:1.45; color:#6b7280 }
    .dark .dl-desc{ color:#9ca3af }

    /* Цена и срок — две равные ячейки: так их видно с одного взгляда и
       карточки выстраиваются по одной сетке независимо от длины строки.
       Подпись и значение стоят В СТРОКУ, а не столбиком: столбиком
       карточка выходила на 35px выше карточки в «Оплате», и на той же
       странице появлялась вертикальная прокрутка. */
    .dl-figures{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin:0 1rem }
    .dl-fig{ display:flex; align-items:baseline; gap:.4rem; padding:.3rem .55rem;
        background:#f9fafb; border:1px solid #eef2f7; min-width:0 }
    .dl-fig__label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#6b7280; flex:none }
    .dl-fig b{ font-size:.82rem; color:#111827; margin-left:auto;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .dl-fig{ background:#0f172a; border-color:#374151 }
    .dark .dl-fig__label{ color:#94a3b8 }
    .dark .dl-fig b{ color:#f3f4f6 }

    .dl-chips{ display:flex; flex-wrap:wrap; gap:.35rem; padding:0 1rem }
    .dl-chip{ display:inline-flex; align-items:center; gap:.3rem;
        padding:.15rem .45rem; font-size:.7rem; font-weight:600; white-space:nowrap;
        color:#4b5563; background:#f9fafb; border:1px solid #e5e7eb }
    .dl-chip i{ font-size:.65rem; color:#9ca3af }
    .dl-chip.is-api{ color:#3730a3; background:#eef2ff; border-color:#c7d2fe }
    .dl-chip.is-api i{ color:#4f46e5 }
    .dl-chip.is-bad{ color:#b91c1c; background:#fef2f2; border-color:#fecaca }
    .dl-chip.is-bad i{ color:#dc2626 }
    .dark .dl-chip{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    .dl-card__foot{ display:flex; align-items:center; gap:.5rem;
        margin-top:auto; padding:.7rem 1rem 0; border-top:1px solid #f1f5f9 }
    .dark .dl-card__foot{ border-top-color:#374151 }

    /* Цвет ссылки — фирменный, но подмешанный к тёмному: чистый цвет
       службы бывает слишком светлым (зелёный СДЭК на белом — контраст
       около 1.9, читать нечем). */
    .dl-docs{ display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem;
        color:color-mix(in srgb, var(--dl) 55%, #111827); text-decoration:none; font-weight:600 }
    .dl-docs:hover{ text-decoration:underline }
    .dark .dl-docs{ color:color-mix(in srgb, var(--dl) 65%, #e5e7eb) }

    .dl-actions{ display:inline-flex; align-items:center; gap:.35rem; margin-left:auto }
    .dl-icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; font-size:.78rem; cursor:pointer;
        color:#4b5563; background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, color .15s }
    .dl-icon:hover{ border-color:var(--dl); color:color-mix(in srgb, var(--dl) 55%, #111827) }
    .dl-icon--danger:hover{ border-color:#dc2626; color:#b91c1c }
    .dark .dl-icon{ color:#d1d5db; background:#111827; border-color:#374151 }

    .dl-empty{ grid-column:1 / -1 }
</style>
@endpush
