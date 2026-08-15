{{-- Стили раздела «Слайдшоу» — один файл на правку и создание.
     Второй набор тех же правил рядом с первым в этом проекте уже
     расходился не раз, поэтому общее живёт здесь, а не копируется.
     Печатается один раз: обе вьюхи включают этот партиал. --}}
@once
@push('styles')
<style>
    /* ── Раздел «Слайдшоу» ────────────────────────────────────────────
       Литеральный CSS, а не Tailwind-утилиты: в собранном
       tailwind.min.css этого проекта нет ни прозрачности через дробь
       (была `bg-gray-50/60` — не рендерилась вовсе), ни произвольных
       значений (`max-h-[90vh]`). Скругления в панели и так сняты общим
       рубильником `body.admin-sharp`. */

    .sl-cardhead{ display:flex; align-items:center; gap:.5rem; padding:.7rem 1.25rem;
        font-size:.72rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#64748b; border-bottom:1px solid #e5e7eb }
    .sl-cardhead i{ color:var(--admin-primary,#6366f1) }
    .sl-cardhead--row{ justify-content:space-between; flex-wrap:wrap; gap:.75rem }
    .sl-cardhead--row > span{ display:inline-flex; align-items:center; gap:.5rem }
    .sl-cardhead__right{ gap:.6rem }
    .dark .sl-cardhead{ color:#94a3b8; border-bottom-color:#374151 }

    .sl-count{ min-width:1.6rem; padding:.1rem .45rem; font-size:.72rem; font-weight:700;
        text-align:center; color:#4b5563; background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .sl-count{ color:#d1d5db; background:#1f2937; border-color:#374151 }

    /* ── Поля ── */
    .sl-field{ display:flex; flex-direction:column; gap:.3rem; min-width:0 }
    .sl-label{ display:inline-flex; align-items:center; gap:.4rem;
        font-size:.78rem; font-weight:600; color:#374151 }
    .sl-label i{ width:.9rem; text-align:center; color:#9ca3af }
    .dark .sl-label{ color:#d1d5db }

    .sl-input{ display:block; width:100%; padding:.5rem .75rem; font-size:.875rem;
        color:#111827; background:#fff; border:1px solid #d1d5db;
        transition:border-color .15s, box-shadow .15s }
    .sl-input:focus{ outline:none; border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 22%, transparent) }
    .dark .sl-input{ color:#f3f4f6; background:#111827; border-color:#374151 }
    textarea.sl-input{ resize:vertical }

    .sl-color{ width:100%; height:2.4rem; padding:.15rem; background:#fff;
        border:1px solid #d1d5db; cursor:pointer }
    .dark .sl-color{ background:#111827; border-color:#374151 }

    .sl-hint{ font-size:.72rem; line-height:1.4; color:#6b7280 }
    .dark .sl-hint{ color:#9ca3af }

    /* ── Переключатели ── */
    .sl-switches{ display:flex; flex-wrap:wrap; align-items:center; gap:1.25rem;
        margin-top:1.1rem; padding-top:1rem; border-top:1px solid #e5e7eb }
    .dark .sl-switches{ border-top-color:#374151 }
    .sl-switch{ display:inline-flex; align-items:center; gap:.55rem; font-size:.85rem;
        color:#374151; cursor:pointer }
    .dark .sl-switch{ color:#d1d5db }
    .sl-switches__save{ margin-left:auto }

    /* ── Кнопки ── */
    .sl-btn{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .8rem;
        font-size:.8rem; font-weight:600; white-space:nowrap; cursor:pointer;
        color:#374151; background:#fff; border:1px solid #d1d5db; text-decoration:none;
        transition:background-color .15s, border-color .15s, color .15s }
    .sl-btn:hover{ background:#f3f4f6; border-color:var(--admin-primary); color:var(--admin-primary) }
    .dark .sl-btn{ color:#d1d5db; background:#1f2937; border-color:#374151 }
    .sl-btn--primary{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary) }
    .sl-btn--primary:hover{ color:var(--admin-on-primary,#fff); background:var(--admin-primary);
        border-color:var(--admin-primary); filter:brightness(1.08) }

    .sl-icon{ display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; cursor:pointer; font-size:.8rem;
        color:#4b5563; background:#fff; border:1px solid #e5e7eb;
        transition:border-color .15s, color .15s }
    .sl-icon:hover{ border-color:var(--admin-primary); color:var(--admin-primary) }
    .sl-icon--danger:hover{ border-color:#dc2626; color:#dc2626 }
    .dark .sl-icon{ color:#d1d5db; background:#111827; border-color:#374151 }

    /* ── Форма добавления ── */
    .sl-addform{ display:grid; gap:1.1rem; padding:1.25rem;
        background:#f9fafb; border-bottom:1px solid #e5e7eb }
    .dark .sl-addform{ background:#0f172a; border-bottom-color:#374151 }
    .sl-addform__foot{ display:flex; justify-content:flex-end; margin-top:.25rem }

    /* ── Зона перетаскивания ── */
    .sl-dropzone{ display:flex; flex-wrap:wrap; gap:1rem; padding:1rem;
        background:#fff; border:1px dashed #cbd5e1; transition:border-color .15s }
    .sl-dropzone:hover{ border-color:var(--admin-primary) }
    .sl-dropzone.is-over{ border-color:var(--admin-primary);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--admin-primary) 20%, transparent) }
    .dark .sl-dropzone{ background:#111827; border-color:#374151 }

    .sl-dropzone__preview{ display:flex; align-items:center; justify-content:center;
        width:11rem; height:8rem; flex:none; overflow:hidden;
        background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .sl-dropzone__preview{ background:#1f2937; border-color:#374151 }
    .sl-dropzone__preview svg{ width:2.25rem; height:2.25rem; color:#cbd5e1 }
    .sl-dropzone__preview img, .sl-dropzone__preview video{ width:100%; height:100%; object-fit:cover }

    .sl-dropzone__body{ flex:1; min-width:14rem; display:flex; flex-direction:column; gap:.5rem }
    .sl-dropzone__text{ margin:0; font-size:.85rem; color:#4b5563 }
    .dark .sl-dropzone__text{ color:#d1d5db }
    .sl-dropzone__or{ color:#9ca3af }
    .sl-dropzone__actions{ display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; margin-top:auto }

    /* ── Список слайдов ── */
    .sl-dragnote{ display:flex; align-items:center; gap:.4rem; margin:0 0 .75rem }
    .sl-grid{ display:grid; gap:1rem; margin:0; padding:0; list-style:none;
        grid-template-columns:repeat(auto-fill, minmax(min(100%, 15rem), 1fr)) }

    .sl-slide{ display:flex; flex-direction:column; overflow:hidden;
        background:#fff; border:1px solid #e5e7eb; transition:border-color .15s }
    .sl-slide:hover{ border-color:var(--admin-primary) }
    .dark .sl-slide{ background:#111827; border-color:#374151 }

    .sl-slide__media{ position:relative; height:9.5rem; background:#f3f4f6 }
    .dark .sl-slide__media{ background:#1f2937 }
    .sl-slide__media img, .sl-slide__media video{ width:100%; height:100%; object-fit:cover }

    .sl-slide__kind{ position:absolute; left:.5rem; top:.5rem;
        display:inline-flex; align-items:center; justify-content:center;
        width:1.6rem; height:1.6rem; font-size:.7rem; color:#fff; background:rgba(17,24,39,.65) }

    .sl-slide__actions{ position:absolute; right:.5rem; top:.5rem; display:flex; gap:.35rem }

    .sl-slide__body{ display:flex; flex-direction:column; gap:.25rem; padding:.7rem .8rem;
        border-top:1px solid #e5e7eb }
    .dark .sl-slide__body{ border-top-color:#374151 }
    .sl-slide__caption{ font-size:.85rem; font-weight:600; color:#111827;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .sl-slide__caption{ color:#f3f4f6 }
    .sl-slide__link{ display:block; font-size:.72rem; color:var(--admin-primary);
        text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .sl-slide__link:hover{ text-decoration:underline }

    /* ── Пусто ── */
    .sl-empty{ display:flex; flex-direction:column; align-items:center; gap:.7rem;
        padding:2.5rem 1.25rem; text-align:center; color:#6b7280 }
    .sl-empty i{ font-size:1.8rem; color:#cbd5e1 }
    .sl-empty p{ margin:0; font-size:.88rem }
    .dark .sl-empty{ color:#9ca3af }

    /* ── Модал ── */
    .sl-modal__box{ max-height:90vh; overflow-y:auto }
    .sl-modal__title{ display:flex; align-items:center; gap:.5rem; margin-bottom:1rem;
        font-size:1rem; font-weight:700; color:#111827 }
    .sl-modal__title i{ color:var(--admin-primary,#6366f1) }
    .dark .sl-modal__title{ color:#f3f4f6 }
    .sl-modal__fields{ display:grid; gap:.9rem }
/* ── Экран создания ── */
    .sl-create{ display:grid; gap:1rem; align-items:start }
    @media (min-width:1100px){
        .sl-create{ grid-template-columns:minmax(0,1fr) 21rem }
    }
    .sl-create__body{ display:grid; gap:1.1rem; padding:1.25rem; max-width:34rem }
    .sl-create__foot{ display:flex; justify-content:flex-end; gap:.6rem;
        padding:1rem 1.25rem; border-top:1px solid #e5e7eb }
    .dark .sl-create__foot{ border-top-color:#374151 }

    .sl-errors{ display:flex; align-items:flex-start; gap:.6rem; margin-bottom:1.5rem;
        padding:.8rem 1rem; font-size:.85rem; color:#991b1b;
        background:#fef2f2; border-left:3px solid #dc2626 }
    .sl-errors i{ margin-top:.15rem }
    .sl-errors__title{ font-weight:700; margin-bottom:.2rem }
    .sl-errors ul{ margin:0; padding-left:1.1rem; list-style:disc }
    .dark .sl-errors{ color:#fecaca; background:#3f1d1d }

    /* Порядок действий: три шага с номером в плитке — тот же приём, что на
       странице входа. Раньше об этом сообщала строка мелким шрифтом. */
    .sl-steps{ display:grid; gap:.7rem; margin:0; padding:1rem 1.25rem; list-style:none }
    .sl-steps li{ display:flex; gap:.6rem; align-items:flex-start;
        font-size:.82rem; line-height:1.45; color:#4b5563 }
    .dark .sl-steps li{ color:#d1d5db }
    .sl-steps__n{ display:flex; align-items:center; justify-content:center; flex:none;
        width:1.3rem; height:1.3rem; margin-top:.05rem;
        font-size:.68rem; font-weight:700; color:var(--admin-on-primary,#fff);
        background:var(--admin-primary,#6366f1) }

    .sl-keys{ padding:.9rem 1.25rem 1.1rem; border-top:1px solid #e5e7eb }
    .dark .sl-keys{ border-top-color:#374151 }
    .sl-keys__title{ display:block; margin-bottom:.5rem;
        font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:#94a3b8 }
    .sl-keys ul{ display:grid; gap:.35rem; margin:0; padding:0; list-style:none;
        font-size:.76rem; color:#6b7280 }
    .dark .sl-keys ul{ color:#9ca3af }
    .sl-keys kbd{ display:inline-block; padding:.1rem .35rem; margin-right:.15rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.7rem;
        color:#374151; background:#f3f4f6; border:1px solid #e5e7eb }
    .dark .sl-keys kbd{ color:#e5e7eb; background:#1f2937; border-color:#374151 }

    /* Чипы выбора позиции: литеральный CSS вместо отсутствующих в сборке
       peer-checked/*-вариантов. */
    .pos-switch{ display:inline-flex; align-items:center; gap:.5rem }
    .pos-switch .pos-chip{
        display:inline-flex; align-items:center; gap:.4rem; cursor:pointer; user-select:none;
        padding:.45rem .85rem; font-size:.85rem; font-weight:600;
        border:1px solid #d1d5db; background:#fff; color:#374151;
        transition:background .15s, color .15s, border-color .15s }
    .dark .pos-switch .pos-chip{ background:#111827; border-color:#374151; color:#d1d5db }
    .pos-switch .pos-chip:hover{ border-color:var(--admin-primary,#6366f1); color:var(--admin-primary,#6366f1) }
    .pos-switch input:checked + .pos-chip{
        color:var(--admin-on-primary,#fff);
        background:var(--admin-primary,#6366f1); border-color:var(--admin-primary,#6366f1) }
    .pos-switch input:focus-visible + .pos-chip{ outline:2px solid var(--admin-primary,#6366f1); outline-offset:2px }

    .sl-btn[disabled]{ opacity:.5; cursor:not-allowed }
</style>

@endpush
@endonce
