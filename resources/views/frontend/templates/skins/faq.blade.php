{--
    Шкурка страницы новости — шаблон «Вопросы».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}
@push('styles')
<style>
    /* Вопросы и ответы: каждый заголовок — вопрос, поэтому он получает
       свою плашку, а ответ под ним набран обычным текстом. */
    .news--faq .news-content h2,
    .news--faq .news-content h3{
        margin-top:1.8rem; padding:.6rem .85rem;
        background:var(--surface-2);
        border-left:3px solid var(--color-primary,#6366f1);
        font-size:1.05rem; line-height:1.4 }
    .news--faq .news-content h2 + p,
    .news--faq .news-content h3 + p{ margin-top:.7rem; padding-left:.85rem }
    /* Раскрывающиеся блоки автор может вставить из редактора. */
    .news--faq .news-content details{
        margin:.6rem 0; padding:.6rem .85rem;
        border:1px solid var(--surface-bd); background:var(--surface) }
    .news--faq .news-content summary{ cursor:pointer; font-weight:600 }
</style>
@endpush
