{--
    Шкурка страницы новости — шаблон «Товары».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}
@push('styles')
<style>
    /* Карточка товара: главное здесь — плашка покупки, поэтому она
       выделена подложкой и полосой, а описание набрано компактнее. */
    .news--products .buy-panel{
        border-left:4px solid var(--color-primary,#6366f1);
        background:var(--surface-2) }
    .news--products .news-content{ font-size:.98rem; line-height:1.7 }
    .news--products .news-content ul > li{ margin:.3rem 0 }
    .news--products .news-content img{ margin:1.25rem auto }
</style>
@endpush
