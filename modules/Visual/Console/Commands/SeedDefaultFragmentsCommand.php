<?php

namespace Modules\Visual\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Visual\Models\Fragment;

/**
 * Набор фрагментов сразу после установки — уже включённых.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed(false)).
 * Раньше таблица visual_fragments оставалась пустой: раздел встречал пустой
 * таблицей, и было непонятно, что вообще такое фрагмент и куда он попадает.
 *
 *   php artisan fragments:seed-default            # добавить недостающие
 *   php artisan fragments:seed-default --reset    # перезаписать содержимое
 *
 * Фрагменты включены намеренно: новый администратор должен увидеть их на
 * страницах и понять, что это редактируемые блоки. Поэтому содержимое —
 * не «Lorem», а осмысленные блоки: на сайте они выглядят как обычные
 * элементы оформления, а в панели прямо объясняют, что это фрагмент и где
 * он правится. Любой блок выключается одним переключателем в разделе.
 *
 * Оформление построено на CSS-переменных активной темы (--color-primary и
 * др.), поэтому блоки меняют цвет вместе с темой сайта.
 */
class SeedDefaultFragmentsCommand extends Command
{
    protected $signature = 'fragments:seed-default {--reset : Перезаписать содержимое фрагментов}';

    protected $description = 'Фрагменты для зон сайта и панели (включены, с пояснениями)';

    /**
     * Канонический набор: по две зоны у сайта и панели плюс полоса объявления
     * и блок под содержимым.
     */
    public static function definitions(): array
    {
        return [
            [
                'slug'  => 'frontend-topbar',
                'title' => 'Полоса объявления над шапкой',
                'zone'  => 'frontend.topbar',
                'html'  => <<<'HTML'
<div class="frg-topbar">
    <span class="frg-topbar__dot"></span>
    <span>Работаем ежедневно с 9:00 до 20:00</span>
    <a href="/contacts" class="frg-topbar__link">Связаться</a>
</div>
HTML,
                'css'   => <<<'CSS'
.frg-topbar{display:flex;align-items:center;justify-content:center;gap:.6rem;flex-wrap:wrap;
    padding:.55rem 1rem;font-size:.82rem;letter-spacing:.01em;color:#fff;
    background:var(--fx-grad, linear-gradient(135deg,#6366f1,#8b5cf6));}
.frg-topbar__dot{width:.45rem;height:.45rem;border-radius:999px;background:#fff;opacity:.8;flex:none;}
.frg-topbar__link{color:#fff;text-decoration:underline;text-underline-offset:2px;font-weight:600;}
.frg-topbar__link:hover{opacity:.85;}
CSS,
            ],

            [
                'slug'  => 'frontend-header',
                'title' => 'Блок под шапкой сайта',
                'zone'  => 'frontend.header',
                'html'  => <<<'HTML'
<div class="frg-underhead">
    <div class="frg-underhead__inner">
        <span class="frg-underhead__badge">Новое</span>
        <span class="frg-underhead__text">Собрали ответы на частые вопросы о работе с системой</span>
        <a href="/page/chastye-voprosy" class="frg-underhead__link">Открыть раздел →</a>
    </div>
</div>
HTML,
                'css'   => <<<'CSS'
.frg-underhead{border-bottom:1px solid rgba(17,24,39,.08);}
.frg-underhead__inner{max-width:1280px;margin:0 auto;padding:.7rem 1rem;display:flex;align-items:center;
    gap:.7rem;flex-wrap:wrap;font-size:.86rem;color:var(--color-text,#111827);}
.frg-underhead__badge{padding:.15rem .5rem;font-size:.68rem;font-weight:700;letter-spacing:.04em;
    text-transform:uppercase;color:#fff;background:var(--color-primary,#6366f1);flex:none;}
.frg-underhead__text{opacity:.85;}
.frg-underhead__link{margin-left:auto;font-weight:600;color:var(--color-primary,#6366f1);white-space:nowrap;}
.frg-underhead__link:hover{text-decoration:underline;}
CSS,
            ],

            [
                'slug'  => 'frontend-content-bottom',
                'title' => 'Блок под содержимым страницы',
                'zone'  => 'frontend.content.bottom',
                'html'  => <<<'HTML'
<div class="frg-cta">
    <div class="frg-cta__text">
        <strong class="frg-cta__title">Остались вопросы?</strong>
        <span class="frg-cta__sub">Ответим в течение рабочего дня — напишите нам удобным способом.</span>
    </div>
    <a href="/contacts" class="frg-cta__btn">Написать нам</a>
</div>
HTML,
                'css'   => <<<'CSS'
.frg-cta{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;
    padding:1.1rem 1.35rem;border:1px solid rgba(17,24,39,.1);
    background:linear-gradient(135deg, rgba(99,102,241,.06), transparent);}
.frg-cta__title{display:block;font-size:1rem;color:var(--color-text,#111827);}
.frg-cta__sub{display:block;font-size:.85rem;opacity:.75;margin-top:.15rem;color:var(--color-text,#111827);}
.frg-cta__btn{display:inline-flex;align-items:center;padding:.55rem 1.15rem;font-size:.85rem;font-weight:600;
    color:#fff;background:var(--fx-grad, linear-gradient(135deg,#6366f1,#8b5cf6));white-space:nowrap;}
.frg-cta__btn:hover{filter:brightness(1.07);color:#fff;}
CSS,
            ],

            [
                'slug'  => 'frontend-footer',
                'title' => 'Строка над подвалом сайта',
                'zone'  => 'frontend.footer',
                'html'  => <<<'HTML'
<div class="frg-preftr">
    <span>Информация на сайте носит справочный характер</span>
    <span class="frg-preftr__sep">·</span>
    <a href="/page/o-proekte">О проекте</a>
    <span class="frg-preftr__sep">·</span>
    <a href="/page/chastye-voprosy">Вопросы и ответы</a>
</div>
HTML,
                'css'   => <<<'CSS'
.frg-preftr{max-width:1280px;margin:0 auto;padding:.9rem 1rem;display:flex;align-items:center;
    justify-content:center;gap:.5rem;flex-wrap:wrap;font-size:.8rem;opacity:.75;
    color:var(--color-text,#6b7280);}
.frg-preftr a{color:var(--color-primary,#6366f1);font-weight:500;}
.frg-preftr a:hover{text-decoration:underline;}
.frg-preftr__sep{opacity:.5;}
CSS,
            ],

            [
                'slug'  => 'admin-header',
                'title' => 'Подсказка в панели (под шапкой)',
                'zone'  => 'admin.header',
                'html'  => <<<'HTML'
<div class="frg-start">
    <div class="frg-start__head">
        <span class="frg-start__badge">🚀</span>
        <div>
            <strong class="frg-start__title">Первые шаги</strong>
            <span class="frg-start__sub">Четыре раздела, с которых обычно начинают. Блок убирается в «Фрагментах».</span>
        </div>
        <a href="/admin/visual/fragments" class="frg-start__off">Убрать блок</a>
    </div>

    <div class="frg-start__grid">
        <a class="frg-start__card" href="/admin/news">
            <span class="frg-start__ico">📰</span>
            <span class="frg-start__name">Наполнить сайт</span>
            <span class="frg-start__hint">Новости и страницы. Демо-материалы можно править и удалять.</span>
        </a>
        <a class="frg-start__card" href="/admin/visual/themes">
            <span class="frg-start__ico">🎨</span>
            <span class="frg-start__name">Выбрать оформление</span>
            <span class="frg-start__hint">Цвета, шрифт и фон. Применяется сразу ко всему сайту.</span>
        </a>
        <a class="frg-start__card" href="/admin/menus">
            <span class="frg-start__ico">🧭</span>
            <span class="frg-start__name">Собрать меню</span>
            <span class="frg-start__hint">Шапка, подвал и боковая панель. Порядок — перетаскиванием.</span>
        </a>
        <a class="frg-start__card" href="/admin/modules">
            <span class="frg-start__ico">🧩</span>
            <span class="frg-start__name">Отключить лишнее</span>
            <span class="frg-start__hint">Не нужен магазин — выключите, и разделы пропадут из меню.</span>
        </a>
    </div>
</div>
HTML,
                'css'   => <<<'CSS'
/* Панель первых шагов. Раньше здесь висела строчка, объяснявшая сама себя
   и больше ничего не дававшая, — а занимала место на КАЖДОЙ странице. */
.frg-start{padding:.9rem 1.25rem 1.1rem;background:#f8fafc;
    border-bottom:1px solid rgba(99,102,241,.22);}
.frg-start__head{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin-bottom:.75rem;}
.frg-start__badge{display:flex;align-items:center;justify-content:center;width:2rem;height:2rem;
    flex:none;background:var(--admin-primary,#6366f1);font-size:.95rem;}
.frg-start__title{display:block;font-size:.95rem;color:#111827;}
.frg-start__sub{display:block;font-size:.76rem;color:#6b7280;}
.frg-start__off{margin-left:auto;flex:none;font-size:.74rem;font-weight:600;color:#6b7280;
    border:1px solid #e5e7eb;padding:.3rem .7rem;background:#fff;white-space:nowrap;}
.frg-start__off:hover{color:var(--admin-primary,#6366f1);border-color:#c7d2fe;}

.frg-start__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.55rem;}
.frg-start__card{display:block;padding:.7rem .85rem;background:#fff;border:1px solid #eef2f7;
    transition:border-color .15s,transform .15s;}
.frg-start__card:hover{border-color:#c7d2fe;transform:translateY(-1px);}
.frg-start__ico{font-size:1.05rem;}
.frg-start__name{display:block;margin-top:.2rem;font-size:.85rem;font-weight:700;color:#111827;}
.frg-start__hint{display:block;margin-top:.15rem;font-size:.74rem;line-height:1.4;color:#6b7280;}

@media (prefers-color-scheme: dark){
    .frg-start{background:#0f172a;}
    .frg-start__card{background:#111827;border-color:#1f2937;}
    .frg-start__title,.frg-start__name{color:#f3f4f6;}
}
CSS,
            ],

            [
                'slug'  => 'admin-footer',
                'title' => 'Памятка в подвале панели',
                'zone'  => 'admin.footer',
                'html'  => <<<'HTML'
<div class="frg-adm-note">
    <span class="frg-adm-note__title">Памятка редактора</span>
    <span>Перед публикацией проверьте заголовок и описание в разделе SEO,
        а оформление сайта настраивается в разделе «Темы».</span>
    <span class="frg-adm-note__hint">Этот текст — тоже фрагмент: «Фрагменты» → «Памятка в подвале панели»</span>
</div>
HTML,
                'css'   => <<<'CSS'
.frg-adm-note{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;padding:.75rem 1.25rem;
    font-size:.78rem;color:#6b7280;border-top:1px solid rgba(17,24,39,.06);}
.frg-adm-note__title{font-weight:700;color:#374151;}
.frg-adm-note__hint{margin-left:auto;font-size:.72rem;opacity:.7;font-style:italic;}
CSS,
            ],
        ];
    }

    public static function seed(bool $reset = false): void
    {
        DB::transaction(function () use ($reset) {
            foreach (self::definitions() as $definition) {
                $fragment = Fragment::where('slug', $definition['slug'])->first();

                $attributes = [
                    'title'       => $definition['title'],
                    'zone'        => $definition['zone'],
                    'type'        => 'html',
                    'html_cached' => trim($definition['html']),
                    // Оформление отдельно от содержимого: css_inline не переводится,
                    // поэтому блок выглядит одинаково на всех языках
                    'css_inline'  => trim($definition['css'] ?? ''),
                    'schema'      => [],
                    'data'        => [],
                ];

                if (! $fragment) {
                    // Включён: новый администратор должен увидеть блоки на
                    // страницах и понять, что это редактируемые фрагменты
                    Fragment::create($attributes + [
                        'slug'      => $definition['slug'],
                        'is_active' => true,
                    ]);
                    continue;
                }

                if ($reset) {
                    // Статус не трогаем: выключенный владельцем блок таким и останется
                    $fragment->update($attributes);
                }
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $count = count(self::definitions());
        $this->info(($reset ? 'Содержимое фрагментов перезаписано' : 'Фрагменты проверены/созданы')
            . " ({$count} шт.).");

        return self::SUCCESS;
    }
}
