{{--
    Шкурка страницы новости — шаблон «Отзывы».
    Подключается сама из frontend/news/show.blade.php по имени шаблона.
    Только оформление: разметку не трогаем, поэтому крошки, плашка покупки
    и «поделиться» остаются общими для всех материалов.

    Цвета — из переменных темы, поэтому шкурка следует выбранному
    оформлению и не ломается на тёмных темах.
--}}
@push('styles')
<style>
    /* Отзывы — это чужая прямая речь, поэтому весь материал набран как
       цитата: втяжка, крупная кавычка, приглушённый цвет. */
    .news--reviews .news-content{
        position:relative; padding-left:2.4rem;
        color:var(--surface-mute); font-size:1.06rem; line-height:1.8 }
    .news--reviews .news-content::before{
        content:'“'; position:absolute; left:0; top:-.35rem;
        font-size:3.4rem; line-height:1;
        color:var(--color-accent,#8b5cf6); opacity:.35 }
    .news--reviews .news-content strong{ color:var(--surface-ink) }
    .news--reviews .fx-section-title{ font-style:italic }
</style>
@endpush
