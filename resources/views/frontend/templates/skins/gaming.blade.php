{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  ШКУРКА СТРАНИЦЫ НОВОСТИ — шаблон «Игры»                         ║
    ╠══════════════════════════════════════════════════════════════════╣
    ║  Подключается сама из frontend/news/show.blade.php по имени       ║
    ║  шаблона материала. Своей разметки почти не добавляет — только    ║
    ║  оформление, чтобы открытая новость выглядела как её карточка на  ║
    ║  главной, а не как любая другая.                                  ║
    ║                                                                  ║
    ║  ЗАВЕСТИ ШКУРКУ ДЛЯ ДРУГОГО ШАБЛОНА                              ║
    ║    Положить файл с именем шаблона в этот каталог. Ничего          ║
    ║    регистрировать не нужно, подключение по имени. Нет файла —     ║
    ║    страница выглядит как раньше.                                  ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}
@push('styles')
<style>
    /* Всё внутри .news--gaming: шкурка не имеет права задевать остальные
       материалы. Литеральный CSS — в собранном tailwind.min.css нет ни
       произвольных значений, ни прозрачности через дробь. */

    /* Карточки статьи тёмные, как витрина на главной. Уровни текста
       переопределяются прямо здесь: общие переменные поверхностей на
       светлой теме тёмные, и подписи оказались бы тёмными на тёмном —
       та же ловушка, что уже разобрана в самом шаблоне «Игры». */
    .news--gaming .fx-card{
        background:#0f172a; border-color:#1e293b;
        --surface-ink:  #f1f5f9;
        --surface-mute: #cbd5e1;
        --surface-dim:  #94a3b8;
    }
    .news--gaming .news-content{ color:#e2e8f0 }
    .news--gaming .news-content h2,
    .news--gaming .news-content h3,
    .news--gaming .news-content h4,
    .news--gaming .news-content strong{ color:#f8fafc }
    .news--gaming .news-content a{ color:#c4b5fd }
    .news--gaming .news-content blockquote{
        border-left:3px solid var(--color-accent,#8b5cf6);
        background:#0b1220; color:#cbd5e1 }
    .news--gaming .news-content li::marker{ color:var(--color-accent,#8b5cf6) }

    /* Заголовок статьи — акцентная полоса слева, как у карточки в сетке. */
    .news--gaming .fx-section-title{
        padding-left:.85rem;
        border-left:4px solid var(--color-accent,#8b5cf6) }

    /* Обложка внутри материала: та же пропорция 8:5, что у карточек, —
       иначе одна и та же картинка выглядит на списке и на странице
       по-разному. */
    .news--gaming .news-content > p:first-child img{
        width:100%; aspect-ratio:8 / 5; object-fit:cover; margin-top:0 }

    /* Плашка покупки на тёмном фоне: её собственные цвета рассчитаны на
       светлую поверхность и здесь тонули. */
    .news--gaming .buy-panel{ background:#0b1220; border-color:#1e293b }
    .news--gaming .buy-qty{ background:#0f172a; border-color:#334155 }
    .news--gaming .buy-qty__btn{ background:#1e293b; color:#e2e8f0 }
    .news--gaming .buy-qty__input{ background:#0f172a; color:#f1f5f9;
        border-left-color:#334155; border-right-color:#334155 }
    .news--gaming .buy-qty__label{ color:#cbd5e1 }
</style>
@endpush
