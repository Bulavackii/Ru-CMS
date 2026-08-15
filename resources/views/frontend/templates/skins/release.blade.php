{--
    Шкурка страницы новости — шаблон «Релизы».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}
@push('styles')
<style>
    /* Патчноут: моноширинные перечни и версии, как в списке изменений.
       Набор плотный — такие материалы просматривают, а не читают. */
    .news--release .news-content{ font-size:.97rem; line-height:1.7 }
    .news--release .news-content ul{ list-style:none; padding-left:0 }
    .news--release .news-content ul > li{
        position:relative; padding-left:1.5rem; margin:.3rem 0;
        font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.9rem }
    .news--release .news-content ul > li::before{
        content:'—'; position:absolute; left:0; top:0;
        color:var(--color-accent,#8b5cf6) }
    .news--release .news-content h2,
    .news--release .news-content h3{
        font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:1.02rem; letter-spacing:-.01em;
        color:var(--color-primary,#6366f1) }
    .news--release .news-content code{
        padding:.1rem .35rem; background:var(--surface-2);
        border:1px solid var(--surface-bd) }
</style>
@endpush
