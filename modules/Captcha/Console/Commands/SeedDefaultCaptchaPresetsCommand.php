<?php

namespace Modules\Captcha\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Captcha\Models\CaptchaPreset;

/**
 * Сборки каптчи по умолчанию.
 *
 * Вызывается мастером установки, поэтому сразу после установки в конструкторе
 * лежат готовые варианты, а не пустой список. Раньше их не заводилось вовсе:
 * владелец открывал раздел, видел пустоту и должен был собрать первую сборку с
 * нуля, чтобы просто попробовать вставить каптчу в форму.
 *
 *   php artisan captcha:seed-default            # дозаполнить недостающее
 *   php artisan captcha:seed-default --reset    # перезаписать настройки сборок
 *
 * Набор подобран так, чтобы показать ВСЕ четыре вида проверки и разную строгость
 * внутри одного вида: слабая отсекает простую автоматику и не мешает человеку,
 * строгая ставится туда, где заявка дорога́.
 */
class SeedDefaultCaptchaPresetsCommand extends Command
{
    protected $signature = 'captcha:seed-default {--reset : Перезаписать настройки сборок по умолчанию}';

    protected $description = 'Сборки каптчи по умолчанию: картинка, слайдер, арифметика, вопрос';

    /** Канонический набор сборок. */
    public static function definitions(): array
    {
        return [
            [
                'name'    => 'Картинка',
                'slug'    => 'kartinka',
                'type'    => 'image',
                'options' => ['length' => 5, 'width' => 260, 'height' => 60, 'noise' => 1, 'lines' => 4],
            ],
            [
                // Тот же вид, но строже: длиннее код и заметно больше помех.
                // Ставится туда, где заявка дорога́ и лишняя секунда посетителя
                // дешевле разбора спама.
                'name'    => 'Картинка построже',
                'slug'    => 'kartinka-postrozhe',
                'type'    => 'image',
                'options' => ['length' => 7, 'width' => 300, 'height' => 70, 'noise' => 3, 'lines' => 9],
            ],
            [
                // Самая дружелюбная: ничего не надо разбирать глазами.
                'name'    => 'Ползунок',
                'slug'    => 'polzunok',
                'type'    => 'slider',
                'options' => ['width' => 300, 'height' => 48, 'tolerance' => 14],
            ],
            [
                'name'    => 'Ползунок построже',
                'slug'    => 'polzunok-postrozhe',
                'type'    => 'slider',
                'options' => ['width' => 320, 'height' => 44, 'tolerance' => 6],
            ],
            [
                // Сложение и вычитание в пределах двадцати — считается в уме.
                'name'    => 'Простой пример',
                'slug'    => 'prostoy-primer',
                'type'    => 'math',
                'options' => ['min' => 1, 'max' => 20, 'operations' => ['+', '-']],
            ],
            [
                'name'    => 'Пример с умножением',
                'slug'    => 'primer-s-umnozheniem',
                'type'    => 'math',
                'options' => ['min' => 2, 'max' => 12, 'operations' => ['+', '-', '*']],
            ],
            [
                // Вопросы про очевидное. Своим списком, а не из конфига: по нему
                // видно, что вопросы задаются здесь и правятся в конструкторе.
                'name'    => 'Простой вопрос',
                'slug'    => 'prostoy-vopros',
                'type'    => 'question',
                'options' => ['questions' => [
                    ['q' => 'Сколько будет два плюс три?', 'a' => '5'],
                    ['q' => 'Какого цвета небо в ясный день?', 'a' => 'синее'],
                    ['q' => 'Сколько дней в неделе?', 'a' => '7'],
                    ['q' => 'Какое время года следует за зимой?', 'a' => 'весна'],
                    ['q' => 'Столица России?', 'a' => 'москва'],
                    ['q' => 'Сколько пальцев на одной руке?', 'a' => '5'],
                ]],
            ],
        ];
    }

    public function handle(): int
    {
        $count = self::seed((bool) $this->option('reset'));

        $this->info('Сборок заведено или обновлено: ' . $count . '.');

        return self::SUCCESS;
    }

    public static function seed(bool $reset = false): int
    {
        if (! Schema::hasTable('captcha_presets')) {
            return 0;
        }

        $touched = 0;

        foreach (self::definitions() as $definition) {
            $existing = CaptchaPreset::query()->where('slug', $definition['slug'])->first();

            // Без --reset уже заведённое не трогаем: владелец мог подправить
            // длину кода или список вопросов под себя.
            if ($existing && ! $reset) {
                continue;
            }

            $payload = [
                'name'      => $definition['name'],
                'slug'      => $definition['slug'],
                'type'      => $definition['type'],
                'options'   => $definition['options'],
                'is_active' => true,
            ];

            $existing ? $existing->update($payload) : CaptchaPreset::create($payload);
            $touched++;
        }

        return $touched;
    }
}
