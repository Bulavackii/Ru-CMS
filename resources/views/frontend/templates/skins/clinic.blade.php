{{--
    Шкурка страницы новости — шаблон «Клиника».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}}
@push('styles')
<style>
    /* Медицинский раздел: спокойный набор, перечни с галочками —
       это чаще всего показания и что входит в приём. */
    .news--clinic .news-content ul{ list-style:none; padding-left:0 }
    .news--clinic .news-content ul > li{
        position:relative; padding-left:1.6rem; margin:.45rem 0 }
    .news--clinic .news-content ul > li::before{
        content:'✓'; position:absolute; left:0; top:0;
        font-weight:800; color:var(--color-primary,#6366f1) }
    .news--clinic .news-content h2,
    .news--clinic .news-content h3{ color:var(--color-primary,#6366f1) }
    .news--clinic .fx-section-title{
        padding-left:.85rem;
        border-left:4px solid var(--color-primary,#6366f1) }
</style>
@endpush
