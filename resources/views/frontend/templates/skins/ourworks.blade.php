{{--
    Шкурка страницы новости — шаблон «Наши услуги».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}}
@push('styles')
<style>
    /* Услуги: перечни — это состав работ, поэтому они с маркером-точкой
       в цвете темы и плотнее обычного. */
    .news--ourworks .news-content ul{ list-style:none; padding-left:0 }
    .news--ourworks .news-content ul > li{
        position:relative; padding-left:1.35rem; margin:.4rem 0 }
    .news--ourworks .news-content ul > li::before{
        content:''; position:absolute; left:0; top:.62em;
        width:.45rem; height:.45rem;
        background:var(--color-accent,#8b5cf6) }
    .news--ourworks .fx-section-title{
        padding-left:.85rem;
        border-left:4px solid var(--color-accent,#8b5cf6) }
</style>
@endpush
