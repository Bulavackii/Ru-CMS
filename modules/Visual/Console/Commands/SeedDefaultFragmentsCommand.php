<?php

namespace Modules\Visual\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Visual\Models\Fragment;

/**
 * Набор фрагментов-заготовок сразу после установки.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed(false)).
 * Раньше таблица visual_fragments оставалась пустой: раздел встречал пустой
 * таблицей, и было непонятно, что вообще такое фрагмент и куда он попадает.
 *
 *   php artisan fragments:seed-default            # добавить недостающие
 *   php artisan fragments:seed-default --reset    # перезаписать заготовки
 *
 * ⚠️ Все заготовки создаются ВЫКЛЮЧЕННЫМИ. Фрагмент — это дополнительный блок
 * в готовой странице, а не замена шапки или подвала: включать его должен
 * владелец сайта осознанно. Пока фрагмент выключен, страницы выглядят ровно
 * так же, как без модуля.
 */
class SeedDefaultFragmentsCommand extends Command
{
    protected $signature = 'fragments:seed-default {--reset : Перезаписать содержимое заготовок}';

    protected $description = 'Заготовки фрагментов для зон сайта и панели (выключенные)';

    /**
     * Канонический набор заготовок: по две зоны у сайта и панели плюс полоса
     * объявления и блок под содержимым.
     */
    public static function definitions(): array
    {
        return [
            [
                'slug'  => 'frontend-topbar',
                'title' => 'Объявление над шапкой',
                'zone'  => 'frontend.topbar',
                'html'  => <<<'HTML'
<div style="background:var(--color-primary,#6366f1); color:#fff; text-align:center; padding:.5rem 1rem; font-size:.85rem;">
    Работаем без выходных · <a href="/contacts" style="color:#fff; text-decoration:underline;">связаться с нами</a>
</div>
HTML,
            ],
            [
                'slug'  => 'frontend-header',
                'title' => 'Блок под шапкой сайта',
                'zone'  => 'frontend.header',
                'html'  => <<<'HTML'
<div style="max-width:1280px; margin:0 auto; padding:.75rem 1rem; font-size:.9rem; color:var(--color-text,#111827);">
    <strong>Новое на сайте:</strong> раздел с ответами на частые вопросы —
    <a href="/page/chastye-voprosy" style="color:var(--color-primary,#6366f1);">посмотреть</a>
</div>
HTML,
            ],
            [
                'slug'  => 'frontend-content-bottom',
                'title' => 'Блок под содержимым страницы',
                'zone'  => 'frontend.content.bottom',
                'html'  => <<<'HTML'
<div style="border:1px solid rgba(17,24,39,.1); padding:1rem 1.25rem; font-size:.9rem;">
    <strong>Не нашли нужного?</strong>
    Напишите нам — <a href="/contacts" style="color:var(--color-primary,#6366f1);">форма обратной связи</a>.
</div>
HTML,
            ],
            [
                'slug'  => 'frontend-footer',
                'title' => 'Блок над подвалом сайта',
                'zone'  => 'frontend.footer',
                'html'  => <<<'HTML'
<div style="max-width:1280px; margin:0 auto; padding:1rem; text-align:center; font-size:.85rem; color:var(--color-text,#6b7280);">
    Сайт работает на RU CMS. Материалы носят информационный характер.
</div>
HTML,
            ],
            [
                'slug'  => 'admin-header',
                'title' => 'Объявление в панели (под шапкой)',
                'zone'  => 'admin.header',
                'html'  => <<<'HTML'
<div style="background:#eef2ff; color:#312e81; padding:.6rem 1rem; font-size:.85rem; border-bottom:1px solid rgba(99,102,241,.25);">
    Напоминание для редакторов: перед публикацией проверяйте заголовок и описание в разделе SEO.
</div>
HTML,
            ],
            [
                'slug'  => 'admin-footer',
                'title' => 'Блок над подвалом панели',
                'zone'  => 'admin.footer',
                'html'  => <<<'HTML'
<div style="padding:.75rem 1rem; font-size:.8rem; color:#6b7280;">
    Служебная информация: резервные копии делаются автоматически. Контакт администратора — admin@example.com
</div>
HTML,
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
                    'schema'      => [],
                    'data'        => [],
                ];

                if (! $fragment) {
                    // Выключен: включать блок на живом сайте — решение владельца
                    Fragment::create($attributes + [
                        'slug'      => $definition['slug'],
                        'is_active' => false,
                    ]);
                    continue;
                }

                if ($reset) {
                    // Статус не трогаем: если фрагмент уже включили, он таким и останется
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
        $this->info(($reset ? 'Заготовки фрагментов перезаписаны' : 'Заготовки фрагментов проверены/созданы')
            . " ({$count} шт., все выключены).");

        return self::SUCCESS;
    }
}
