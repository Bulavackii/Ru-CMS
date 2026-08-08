<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Forms\Models\Form;

/**
 * Формы по умолчанию.
 *
 * Вызывается мастером установки, поэтому сразу после установки в конструкторе
 * лежат рабочие примеры, а не пустой список: по ним видно, как собирается
 * форма, и их можно вставить в материал в тот же день.
 *
 *   php artisan forms:seed-default            # дозаполнить недостающее
 *   php artisan forms:seed-default --reset    # перезаписать содержимое форм
 */
class SeedDefaultFormsCommand extends Command
{
    protected $signature = 'forms:seed-default {--reset : Перезаписать поля и настройки дефолтных форм}';

    protected $description = 'Формы по умолчанию: обратная связь, заявка и запись на приём';

    /** Канонический набор форм. */
    public static function definitions(): array
    {
        return [
            [
                'title'       => 'Обратная связь',
                'slug'        => 'obratnaya-svyaz',
                'description' => 'Напишите нам — ответим на почту, которую вы укажете.',
                'fields'      => [
                    ['type' => 'text',     'name' => 'name',    'label' => 'Как к вам обращаться', 'required' => true,  'width' => 'half'],
                    ['type' => 'email',    'name' => 'email',   'label' => 'Электронная почта',    'required' => true,  'width' => 'half',
                     'hint' => 'На неё придёт ответ'],
                    ['type' => 'text',     'name' => 'subject', 'label' => 'Тема',                 'required' => false, 'width' => 'full'],
                    ['type' => 'textarea', 'name' => 'message', 'label' => 'Сообщение',            'required' => true,  'width' => 'full'],
                    ['type' => 'consent',  'name' => 'consent', 'label' => 'Согласен на обработку персональных данных', 'required' => true, 'width' => 'full'],
                ],
                'settings'    => [
                    'submit_label'    => 'Отправить',
                    'success_message' => 'Спасибо! Сообщение получено, мы ответим на указанную почту.',
                    'columns'         => true,
                    'show_title'      => true,
                ],
            ],
            [
                'title'       => 'Заявка на услугу',
                'slug'        => 'zayavka',
                'description' => 'Оставьте контакты — перезвоним и уточним детали.',
                'fields'      => [
                    ['type' => 'text',   'name' => 'name',    'label' => 'Имя',     'required' => true, 'width' => 'half'],
                    ['type' => 'tel',    'name' => 'phone',   'label' => 'Телефон', 'required' => true, 'width' => 'half',
                     'placeholder' => '+7 900 000-00-00'],
                    ['type' => 'select', 'name' => 'service', 'label' => 'Услуга',  'required' => true, 'width' => 'half',
                     'options' => ['Консультация', 'Расчёт стоимости', 'Выезд специалиста', 'Другое']],
                    ['type' => 'select', 'name' => 'contact_time', 'label' => 'Когда удобно позвонить', 'required' => false, 'width' => 'half',
                     'options' => ['В любое время', 'Утром', 'Днём', 'Вечером']],
                    ['type' => 'textarea', 'name' => 'comment', 'label' => 'Комментарий', 'required' => false, 'width' => 'full'],
                    ['type' => 'consent',  'name' => 'consent', 'label' => 'Согласен на обработку персональных данных', 'required' => true, 'width' => 'full'],
                ],
                'settings'    => [
                    'submit_label'    => 'Оставить заявку',
                    'success_message' => 'Заявка принята. Мы свяжемся с вами в ближайшее время.',
                    'note'            => 'Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности.',
                    'columns'         => true,
                    'show_title'      => true,
                ],
            ],
            [
                'title'       => 'Запись на приём',
                'slug'        => 'zapis-na-priem',
                'description' => 'Выберите специалиста и удобные дату и время.',
                'fields'      => [
                    ['type' => 'text',  'name' => 'name',  'label' => 'ФИО',     'required' => true, 'width' => 'half'],
                    ['type' => 'tel',   'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'width' => 'half'],
                    ['type' => 'select', 'name' => 'doctor', 'label' => 'Специалист', 'required' => true, 'width' => 'half',
                     'options' => ['Терапевт', 'Стоматолог', 'Педиатр', 'Врач УЗИ']],
                    ['type' => 'date',  'name' => 'day',   'label' => 'Дата',    'required' => true, 'width' => 'half'],
                    ['type' => 'time',  'name' => 'time',  'label' => 'Время',   'required' => false, 'width' => 'half'],
                    ['type' => 'textarea', 'name' => 'complaint', 'label' => 'Что беспокоит', 'required' => false, 'width' => 'full'],
                    ['type' => 'consent',  'name' => 'consent', 'label' => 'Согласен на обработку персональных данных', 'required' => true, 'width' => 'full'],
                ],
                'settings'    => [
                    'submit_label'    => 'Записаться',
                    'success_message' => 'Запись принята. Администратор перезвонит для подтверждения.',
                    'columns'         => true,
                    'show_title'      => true,
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $count = self::seed((bool) $this->option('reset'));

        $this->info('Форм заведено или обновлено: ' . $count . '.');

        return self::SUCCESS;
    }

    public static function seed(bool $reset = false): int
    {
        if (! Schema::hasTable('forms')) {
            return 0;
        }

        $touched = 0;

        foreach (self::definitions() as $definition) {
            $existing = Form::query()->where('slug', $definition['slug'])->first();

            if ($existing && ! $reset) {
                continue;
            }

            $payload = [
                'title'       => $definition['title'],
                'slug'        => $definition['slug'],
                'description' => $definition['description'],
                'fields'      => self::normalize($definition['fields']),
                'settings'    => $definition['settings'] + ['has_upload' => false],
                'is_active'   => true,
            ];

            $existing ? $existing->update($payload) : Form::create($payload);
            $touched++;
        }

        return $touched;
    }

    /**
     * Дописать полям недостающие ключи.
     *
     * В определениях перечислено только осмысленное, иначе они превратились бы
     * в стену из пустых строк. Полную структуру собираем здесь — отрисовка
     * ждёт её целиком.
     */
    private static function normalize(array $fields): array
    {
        return array_map(fn (array $field) => $field + [
            'placeholder' => '',
            'hint'        => '',
            'value'       => '',
            'required'    => false,
            'width'       => 'full',
            'options'     => [],
        ], $fields);
    }
}
