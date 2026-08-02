@extends('layouts.admin')

@section('title', __('admin.system.si_title'))

@section('content')
{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-desktop"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.system.si_title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.system.si_subtitle') }}</p>
        </div>
    </div>

    {{-- Сводку почти всегда просят приложить к обращению — пусть копируется одной кнопкой. --}}
    <button type="button" id="si-copy"
            class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                   hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-copy"></i> <span>{{ __('admin.system.si_copy') }}</span>
    </button>
</div>

@if($debug)
    <div class="sys-warn mb-4">
        <i class="fas fa-triangle-exclamation"></i>
        <span>{{ __('admin.system.debug_warn') }}</span>
    </div>
@endif

@unless($storageLinked)
    <div class="sys-warn mb-4">
        <i class="fas fa-link-slash"></i>
        <span>{{ __('admin.system.storage_warn') }}</span>
    </div>
@endunless

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($groups as $group => $rows)
        <section class="admin-card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                <i class="fas fa-circle-dot text-indigo-500"></i> {{ __('admin.system.' . $group) }}
            </h2>

            <dl class="sys-list">
                @foreach($rows as $key => $value)
                    <div>
                        <dt>{{ __('admin.system.' . $key) }}</dt>
                        <dd>{{ $value !== '' ? $value : __('admin.system.none') }}</dd>
                    </div>
                @endforeach

                @if($group === 'g_runtime')
                    <div>
                        <dt>{{ __('admin.system.f_debug') }}</dt>
                        <dd>{{ $debug ? __('admin.system.on') : __('admin.system.off') }}</dd>
                    </div>
                @endif

                @if($group === 'g_app')
                    <div>
                        <dt>{{ __('admin.system.f_storage') }}</dt>
                        <dd>{{ $storageLinked ? __('admin.system.yes') : __('admin.system.no') }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    @endforeach
</div>

{{-- ── Расширения PHP ── --}}
<section class="admin-card p-5 mt-4" x-data="{ open: false, q: '' }">
    <button type="button" class="w-full flex items-center justify-between gap-3 text-left" @click="open = !open">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">
            <i class="fas fa-puzzle-piece text-indigo-500"></i> {{ __('admin.system.ext_title') }}
            <span class="normal-case font-semibold text-gray-400 ml-1">
                {{ __('admin.system.ext_count', ['count' => count($extensions)]) }}
            </span>
        </h2>
        <i class="fas fa-chevron-down text-gray-400 transition" :class="open && 'fa-chevron-up'"></i>
    </button>

    <div x-show="open" x-cloak class="mt-4">
        {{-- Расширений под сотню — без фильтра список бесполезен. --}}
        <input type="search" x-model="q" placeholder="{{ __('admin.system.ext_search') }}"
               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm mb-3">

        <div class="sys-ext">
            @foreach($extensions as $ext)
                <span x-show="q === '' || @js(strtolower($ext)).includes(q.toLowerCase())">{{ $ext }}</span>
            @endforeach
        </div>

        <p class="admin-hint mt-3"
           x-show="q !== '' && !@js(array_map('strtolower', $extensions)).some(e => e.includes(q.toLowerCase()))">
            {{ __('admin.system.ext_empty') }}
        </p>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.getElementById('si-copy')?.addEventListener('click', function () {
        // Собираем ровно то, что видно на странице: подписи уже переведены.
        const lines = [];

        document.querySelectorAll('.sys-list > div').forEach(function (row) {
            const dt = row.querySelector('dt'), dd = row.querySelector('dd');
            if (dt && dd) lines.push(dt.textContent.trim() + ': ' + dd.textContent.trim());
        });

        const label = this.querySelector('span');
        const before = label.textContent;

        navigator.clipboard.writeText(lines.join('\n')).then(function () {
            label.textContent = @js(__('admin.system.si_copied'));
            setTimeout(function () { label.textContent = before; }, 2000);
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни прозрачности
       через /NN, ни произвольных значений. */
    .sys-list{ display:grid; gap:.45rem; font-size:.9rem }
    .sys-list > div{ display:flex; gap:.75rem; align-items:baseline; justify-content:space-between;
                     padding-bottom:.45rem; border-bottom:1px solid #f1f5f9 }
    .sys-list > div:last-child{ border-bottom:0; padding-bottom:0 }
    .sys-list dt{ color:#6b7280; flex-shrink:0 }
    .sys-list dd{ margin:0; font-weight:600; color:#111827; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
                  font-size:.85rem; text-align:right; word-break:break-all }

    .sys-ext{ display:flex; flex-wrap:wrap; gap:.35rem }
    .sys-ext span{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.75rem;
                   background:#f8fafc; border:1px solid #eef2f7; color:#334155; padding:.2rem .5rem }

    .sys-warn{ display:flex; gap:.6rem; align-items:flex-start; font-size:.875rem;
               color:#92400e; background:#fffbeb; border:1px solid #fde68a; padding:.75rem 1rem }

    @media (prefers-color-scheme: dark){
        .sys-list > div{ border-color:#1f2937 }
        .sys-list dd{ color:#f3f4f6 }
        .sys-ext span{ background:transparent; border-color:#374151; color:#cbd5e1 }
    }
</style>
@endpush
