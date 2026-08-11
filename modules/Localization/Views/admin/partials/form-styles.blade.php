{{-- Общие стили форм локализации: подключаются и правкой, и
     созданием страны. @once — чтобы стек не получил две копии,
     если партиал когда-нибудь подключат дважды на одной странице. --}}
@once
@push('styles')
<style>
    /* ── Страна: правка ───────────────────────────────────────────────
       Литеральный CSS: в сборке проекта нет ни прозрачности через дробь,
       ни произвольных значений. Типографика — как в «Оплате», «Доставке»
       и «Заказах»: подписи моноширинным капсом. */

    .loc-cols{ display:grid; gap:1rem; align-items:start }
    @media (min-width:1180px){ .loc-cols{ grid-template-columns:minmax(0,1fr) 20rem } }

    .loc-h2{ display:flex; align-items:center; gap:.45rem; margin:0 0 1rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .loc-label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.64rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase }

    .loc-input{ width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:var(--surface-ink,#111827); background:var(--surface,#fff);
        border:1px solid #d1d5db; transition:border-color .15s }
    .loc-input:focus{ outline:2px solid #6366f1; outline-offset:-1px; border-color:#6366f1 }
    .dark .loc-input{ color:#f3f4f6; background:#1f2937; border-color:#4b5563 }

    .loc-switch{ display:inline-flex; align-items:center; gap:.6rem; cursor:pointer; padding-top:.35rem }
    .loc-switch__text{ font-size:.85rem; color:#374151 }
    .dark .loc-switch__text{ color:#d1d5db }

    /* Знак страны в шапке — на подложке, как значки разделов. */
    .loc-flag-badge{ display:inline-flex; align-items:center; justify-content:center; flex:none;
        width:2.5rem; height:2.5rem; font-size:1.35rem; line-height:1;
        background:#eef2ff; border:1px solid #c7d2fe }
    .dark .loc-flag-badge{ background:#1e1b4b; border-color:#4338ca }

    .loc-code{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.78rem;
        padding:.05rem .3rem; background:#f3f4f6; color:#374151 }
    .dark .loc-code{ background:#1f2937; color:#d1d5db }

    /* Факты в боковой колонке: подпись слева, значение справа. */
    .loc-facts{ display:grid; gap:.4rem; margin:0 }
    .loc-facts > div{ display:flex; align-items:baseline; justify-content:space-between; gap:.75rem;
        padding:.35rem 0; border-bottom:1px dashed #eef2f7 }
    .loc-facts > div:last-child{ border-bottom:0 }
    .dark .loc-facts > div{ border-bottom-color:#374151 }
    .loc-facts dt{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:color-mix(in srgb, var(--surface-ink,#111827) 60%, var(--surface,#fff)) }
    .loc-facts dd{ margin:0; font-size:.88rem; font-weight:700; text-align:right;
        color:var(--surface-ink,#111827); font-variant-numeric:tabular-nums }
</style>
@endpush
@endonce
