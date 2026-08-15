{{--
    Шкурка страницы новости — шаблон «Журнал».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}}
@push('styles')
<style>
    /* Журнальный разворот: широкая мера строки, крупный первый абзац,
       заголовки отбиты линией. Материал читают долго, поэтому набор
       спокойнее, чем в ленте. */
    .news--magazine .news-content{ font-size:1.12rem; line-height:1.85 }
    .news--magazine .news-content > p:first-of-type{
        font-size:1.22rem; line-height:1.65; color:var(--surface-ink) }
    .news--magazine .news-content h2,
    .news--magazine .news-content h3{
        margin-top:2.2rem; padding-bottom:.45rem;
        border-bottom:1px solid var(--surface-bd) }
    .news--magazine .news-content blockquote{
        margin:1.75rem 0; padding:.9rem 1.2rem;
        border-left:3px solid var(--color-accent,#8b5cf6);
        background:var(--surface-2); font-size:1.08rem }
    .news--magazine .fx-section-title{ letter-spacing:-.02em }
</style>
@endpush
