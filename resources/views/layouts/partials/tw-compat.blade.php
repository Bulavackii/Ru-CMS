{{--
    Совместимость с собранным public/assets/css/tailwind.min.css.

    Эта сборка статическая (не JIT) и уровня Tailwind v2: в ней ЕСТЬ `.transform`
    (объявляет --tw-translate-* и transform), но НЕТ самих утилит
    `-translate-y-1/2` / `-translate-x-1/2`. Из-за этого повсеместный приём
    центрирования `absolute top-1/2 -translate-y-1/2` молча НЕ центрировал элемент:
    он лишь позиционировался верхним краем на середине контейнера и «свисал» вниз
    (так лупа в полях поиска вылезала из инпута).

    Восстанавливаем недостающие утилиты литеральным CSS — тем же приёмом, что
    .admin-glass/.fx-card и прочие обходы неполной сборки. Правило для комбинации
    двух классов идёт последним и имеет более высокую специфичность, поэтому
    `-translate-x-1/2 -translate-y-1/2` даёт сдвиг по обеим осям, а не только по
    последней. Подключается во все лейауты, где используется этот приём
    (admin / frontend / guest / app).

    Проверить наличие класса в сборке:
      grep -acF '.-translate-y-1\/2{' public/assets/css/tailwind.min.css
--}}
<style>
    .-translate-y-1\/2{ transform: translateY(-50%); }
    .-translate-x-1\/2{ transform: translateX(-50%); }
    .translate-y-1\/2{ transform: translateY(50%); }
    .translate-x-1\/2{ transform: translateX(50%); }
    .-translate-x-1\/2.-translate-y-1\/2{ transform: translate(-50%, -50%); }
    .-translate-x-1\/2.translate-y-1\/2{ transform: translate(-50%, 50%); }
    .translate-x-1\/2.-translate-y-1\/2{ transform: translate(50%, -50%); }
    .translate-x-1\/2.translate-y-1\/2{ transform: translate(50%, 50%); }
</style>
