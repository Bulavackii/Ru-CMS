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

    /**
     * Канонический набор форм.
     *
     * Тринадцать образцов под РАЗНЫЕ задачи, а не тринадцать вариантов одной.
     * Смысл двойной: их можно применить как есть, а можно открыть и посмотреть,
     * как собрана форма нужного вида. Поэтому набор подобран так, чтобы вместе
     * они показывали ВСЕ семнадцать типов полей и все приёмы — оформление
     * подписи, ссылку в согласии, скрытое поле, вложение, разметку внутри
     * формы, письмо на почту, свою страницу благодарности.
     *
     * Ширина полей здесь не указана: её считает по типу normalize(), тем же
     * правилом, что и конструктор.
     */
    public static function definitions(): array
    {
        // Согласие повторяется почти в каждой форме — держим его одной строкой,
        // иначе тринадцать копий разъедутся при первой же правке.
        $consent = [
            'type' => 'consent', 'name' => 'consent', 'icon' => 'fas fa-shield-halved', 'required' => true,
            'label' => 'Согласен на обработку персональных данных и с [политикой конфиденциальности](/privacy)',
        ];

        return [
            // ── 1. Самая простая: две строки ────────────────────────────
            [
                'title'       => 'Обратный звонок',
                'slug'        => 'obratnyy-zvonok',
                'description' => 'Оставьте телефон — перезвоним в течение рабочего дня.',
                'fields'      => [
                    ['type' => 'text', 'name' => 'name',  'label' => 'Как к вам обращаться', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',  'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone',
                     'placeholder' => '+7 900 000-00-00'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-phone', 'submit_label' => 'Жду звонка',
                    'success_message' => 'Спасибо! Перезвоним в ближайшее время.',
                    'note' => 'Звоним с 9:00 до 19:00 по будням.',
                ],
            ],

            // ── 2. Классическая обратная связь ──────────────────────────
            [
                'title'       => 'Обратная связь',
                'slug'        => 'obratnaya-svyaz',
                'description' => 'Напишите нам — ответим на почту, которую вы укажете.',
                'fields'      => [
                    ['type' => 'text',     'name' => 'name',    'label' => 'Как к вам обращаться', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'email',    'name' => 'email',   'label' => 'Электронная почта', 'required' => true, 'icon' => 'fas fa-at',
                     'hint' => 'На неё придёт ответ'],
                    ['type' => 'text',     'name' => 'subject', 'label' => 'Тема'],
                    ['type' => 'textarea', 'name' => 'message', 'label' => 'Сообщение', 'required' => true, 'icon' => 'fas fa-comment'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-envelope', 'submit_label' => 'Отправить',
                    'success_message' => 'Спасибо! Сообщение получено, мы ответим на указанную почту.',
                ],
            ],

            // ── 3. Заявка со списками ───────────────────────────────────
            [
                'title'       => 'Заявка на услугу',
                'slug'        => 'zayavka',
                'description' => 'Оставьте контакты — перезвоним и уточним детали.',
                'fields'      => [
                    ['type' => 'text',   'name' => 'name',  'label' => 'Имя', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',    'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone',
                     'placeholder' => '+7 900 000-00-00'],
                    ['type' => 'select', 'name' => 'service', 'label' => 'Услуга', 'required' => true,
                     'options' => ['Консультация', 'Расчёт стоимости', 'Выезд специалиста', 'Другое']],
                    ['type' => 'select', 'name' => 'contact_time', 'label' => 'Когда удобно позвонить',
                     'options' => ['В любое время', 'Утром', 'Днём', 'Вечером']],
                    ['type' => 'textarea', 'name' => 'comment', 'label' => 'Комментарий'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-file-lines', 'submit_label' => 'Оставить заявку',
                    'success_message' => 'Заявка принята. Мы свяжемся с вами в ближайшее время.',
                ],
            ],

            // ── 4. Дата и время ─────────────────────────────────────────
            [
                'title'       => 'Запись на приём',
                'slug'        => 'zapis-na-priem',
                'description' => 'Выберите специалиста и удобные дату и время.',
                'fields'      => [
                    ['type' => 'text',  'name' => 'name',  'label' => 'ФИО', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',   'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone'],
                    ['type' => 'select', 'name' => 'doctor', 'label' => 'Специалист', 'required' => true, 'icon' => 'fas fa-stethoscope',
                     'options' => ['Терапевт', 'Стоматолог', 'Педиатр', 'Врач УЗИ']],
                    ['type' => 'date',  'name' => 'day',  'label' => 'Дата', 'required' => true, 'icon' => 'fas fa-calendar-days'],
                    ['type' => 'time',  'name' => 'time', 'label' => 'Время', 'icon' => 'fas fa-clock',
                     'hint' => 'Если не выберете, подберём ближайшее свободное'],
                    ['type' => 'textarea', 'name' => 'complaint', 'label' => 'Что беспокоит'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-calendar-days', 'submit_label' => 'Записаться',
                    'success_message' => 'Запись принята. Администратор перезвонит для подтверждения.',
                ],
            ],

            // ── 5. Число, флажки и вложение ─────────────────────────────
            [
                'title'       => 'Расчёт стоимости',
                'slug'        => 'raschet-stoimosti',
                'description' => 'Опишите задачу — пришлём смету на почту.',
                'fields'      => [
                    ['type' => 'heading',   'name' => 'h_task', 'label' => 'Что нужно сделать', 'icon' => 'fas fa-wrench'],
                    ['type' => 'select',    'name' => 'kind', 'label' => 'Вид работ', 'required' => true,
                     'options' => ['Монтаж', 'Ремонт', 'Обслуживание', 'Проектирование']],
                    ['type' => 'number',    'name' => 'area', 'label' => 'Площадь, м²', 'icon' => 'fas fa-ruble-sign',
                     'placeholder' => '120'],
                    ['type' => 'checkboxes', 'name' => 'extras', 'label' => 'Дополнительно',
                     'options' => ['Замер на объекте', 'Доставка материалов', 'Вывоз мусора', 'Гарантийное обслуживание']],
                    ['type' => 'file',      'name' => 'plan', 'label' => 'Чертёж или фото объекта', 'icon' => 'fas fa-paperclip',
                     'hint' => 'PDF, изображение или архив — до 10 файлов одним архивом'],
                    ['type' => 'heading',   'name' => 'h_contacts', 'label' => 'Куда прислать расчёт'],
                    ['type' => 'text',      'name' => 'name', 'label' => 'Имя', 'required' => true],
                    ['type' => 'email',     'name' => 'email', 'label' => 'Почта', 'required' => true, 'icon' => 'fas fa-at'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-ruble-sign', 'submit_label' => 'Получить расчёт',
                    'success_message' => 'Спасибо! Пришлём смету на указанную почту в течение двух рабочих дней.',
                ],
            ],

            // ── 6. Ссылка на портфолио и вложение ───────────────────────
            [
                'title'       => 'Отклик на вакансию',
                'slug'        => 'otklik-na-vakansiyu',
                'description' => 'Расскажите о себе — посмотрим и ответим каждому.',
                'fields'      => [
                    ['type' => 'text',   'name' => 'name',  'label' => 'ФИО', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',    'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone'],
                    ['type' => 'email',  'name' => 'email', 'label' => 'Почта', 'required' => true, 'icon' => 'fas fa-at'],
                    ['type' => 'select', 'name' => 'position', 'label' => 'Должность', 'required' => true, 'icon' => 'fas fa-briefcase',
                     'options' => ['Менеджер по продажам', 'Специалист поддержки', 'Разработчик', 'Другая']],
                    ['type' => 'url',    'name' => 'portfolio', 'label' => 'Ссылка на портфолио', 'icon' => 'fas fa-link',
                     'placeholder' => 'https://', 'hint' => 'Сайт, профиль или папка с работами'],
                    ['type' => 'file',   'name' => 'cv', 'label' => 'Резюме файлом', 'icon' => 'fas fa-file-lines',
                     'hint' => 'PDF или документ Word'],
                    ['type' => 'textarea', 'name' => 'about', 'label' => 'Несколько слов о себе'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-briefcase', 'submit_label' => 'Откликнуться',
                    'success_message' => 'Спасибо! Мы прочитаем отклик и ответим на указанную почту.',
                ],
            ],

            // ── 7. Оценка переключателями ───────────────────────────────
            [
                'title'       => 'Отзыв о работе',
                'slug'        => 'otzyv',
                'description' => 'Нам важно знать, что понравилось, а что нет.',
                'fields'      => [
                    ['type' => 'text',  'name' => 'name', 'label' => 'Как вас зовут', 'icon' => 'fas fa-user',
                     'hint' => 'Можно не указывать'],
                    ['type' => 'radio', 'name' => 'rating', 'label' => 'Оценка', 'required' => true, 'icon' => 'fas fa-star',
                     'options' => ['Отлично', 'Хорошо', 'Нормально', 'Плохо']],
                    ['type' => 'radio', 'name' => 'again', 'label' => 'Обратитесь ли снова',
                     'options' => ['Да', 'Скорее да', 'Скорее нет', 'Нет']],
                    ['type' => 'textarea', 'name' => 'text', 'label' => 'Что хотите добавить', 'required' => true],
                    ['type' => 'checkbox', 'name' => 'publish', 'label' => 'Можно опубликовать отзыв на сайте'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-star', 'submit_label' => 'Отправить отзыв',
                    'success_message' => 'Спасибо за отзыв! Мы читаем каждый.',
                    'columns' => false,
                ],
            ],

            // ── 8. Реквизиты организации ────────────────────────────────
            [
                'title'       => 'Заявка от организации',
                'slug'        => 'zayavka-ot-organizacii',
                'description' => 'Для работы по договору и безналичной оплаты.',
                'fields'      => [
                    ['type' => 'heading', 'name' => 'h_org', 'label' => 'Организация', 'icon' => 'fas fa-building'],
                    ['type' => 'text',   'name' => 'company', 'label' => 'Название', 'required' => true,
                     'placeholder' => 'ООО «Ромашка»'],
                    ['type' => 'text',   'name' => 'inn', 'label' => 'ИНН', 'required' => true, 'icon' => 'fas fa-id-card',
                     'placeholder' => '7701234567'],
                    ['type' => 'heading', 'name' => 'h_person', 'label' => 'Контактное лицо'],
                    ['type' => 'text',   'name' => 'person', 'label' => 'ФИО', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',    'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone'],
                    ['type' => 'email',  'name' => 'email', 'label' => 'Почта для документов', 'required' => true, 'icon' => 'fas fa-at'],
                    ['type' => 'textarea', 'name' => 'task', 'label' => 'Что требуется', 'required' => true],
                    // Скрытое поле: показывает, зачем оно нужно — различать
                    // источник заявки, когда форм на сайте несколько.
                    ['type' => 'hidden', 'name' => 'source', 'label' => 'Источник', 'value' => 'Страница для организаций'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-building', 'submit_label' => 'Отправить заявку',
                    'success_message' => 'Заявка получена. Пришлём счёт и договор на указанную почту.',
                ],
            ],

            // ── 9. Разметка внутри формы и оформление подписи ───────────
            [
                'title'       => 'Обращение или жалоба',
                'slug'        => 'obrashchenie',
                'description' => 'Разберёмся и ответим письменно.',
                'fields'      => [
                    ['type' => 'paragraph', 'name' => 'p_intro', 'icon' => 'fas fa-circle-info',
                     'label' => 'Опишите ситуацию **своими словами**. Чем больше подробностей — номер заказа, дата, имя сотрудника, — тем быстрее разберёмся.'],
                    ['type' => 'text',   'name' => 'order',  'label' => 'Номер заказа или договора', 'icon' => 'fas fa-tag',
                     'hint' => 'Если знаете'],
                    ['type' => 'date',   'name' => 'happened', 'label' => 'Когда это было', 'icon' => 'fas fa-calendar-days'],
                    ['type' => 'textarea', 'name' => 'text', 'label' => 'Суть обращения', 'required' => true],
                    ['type' => 'file',   'name' => 'proof', 'label' => 'Документ или фото', 'icon' => 'fas fa-paperclip'],
                    ['type' => 'heading', 'name' => 'h_back', 'label' => 'Как с вами связаться'],
                    ['type' => 'text',   'name' => 'name',  'label' => 'ФИО', 'required' => true],
                    ['type' => 'email',  'name' => 'email', 'label' => 'Почта', 'required' => true, 'icon' => 'fas fa-at'],
                    ['type' => 'tel',    'name' => 'phone', 'label' => 'Телефон'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-circle-question', 'submit_label' => 'Отправить обращение',
                    'success_message' => 'Обращение зарегистрировано. Ответим письменно в течение десяти рабочих дней.',
                    'note' => 'Срок ответа — {#c62828|10 рабочих дней} по закону об обращениях граждан.',
                ],
            ],

            // ── 10. Мероприятие: число участников и формат ──────────────
            [
                'title'       => 'Регистрация на мероприятие',
                'slug'        => 'registraciya-na-meropriyatie',
                'description' => 'Участие бесплатное, число мест ограничено.',
                'fields'      => [
                    ['type' => 'text',   'name' => 'name',  'label' => 'Имя и фамилия', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'email',  'name' => 'email', 'label' => 'Почта', 'required' => true, 'icon' => 'fas fa-at',
                     'hint' => 'Пришлём напоминание за день до начала'],
                    ['type' => 'text',   'name' => 'company', 'label' => 'Компания или должность', 'icon' => 'fas fa-briefcase'],
                    ['type' => 'radio',  'name' => 'format', 'label' => 'Как будете участвовать', 'required' => true,
                     'options' => ['Приду лично', 'Подключусь онлайн']],
                    ['type' => 'number', 'name' => 'guests', 'label' => 'Сколько человек с вами', 'value' => '0',
                     'hint' => 'Считая вас — оставьте пустым, если придёте один'],
                    ['type' => 'checkboxes', 'name' => 'topics', 'label' => 'Какие темы интересны',
                     'options' => ['Практика внедрения', 'Разбор ошибок', 'Ответы на вопросы', 'Знакомство с коллегами']],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-calendar-check', 'submit_label' => 'Зарегистрироваться',
                    'success_message' => 'Вы в списке! Подробности придут на почту.',
                ],
            ],

            // ── 11. Бронирование ────────────────────────────────────────
            [
                'title'       => 'Бронирование',
                'slug'        => 'bronirovanie',
                'description' => 'Забронируем и подтвердим по телефону.',
                'fields'      => [
                    ['type' => 'text',   'name' => 'name',  'label' => 'Имя', 'required' => true, 'icon' => 'fas fa-user'],
                    ['type' => 'tel',    'name' => 'phone', 'label' => 'Телефон', 'required' => true, 'icon' => 'fas fa-phone'],
                    ['type' => 'date',   'name' => 'day',   'label' => 'Дата', 'required' => true, 'icon' => 'fas fa-calendar-days'],
                    ['type' => 'time',   'name' => 'time',  'label' => 'Время', 'required' => true, 'icon' => 'fas fa-clock'],
                    ['type' => 'number', 'name' => 'guests', 'label' => 'Гостей', 'required' => true, 'placeholder' => '2'],
                    ['type' => 'textarea', 'name' => 'wishes', 'label' => 'Пожелания',
                     'hint' => 'Столик у окна, детский стул, повод'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-map', 'submit_label' => 'Забронировать',
                    'success_message' => 'Бронь принята. Перезвоним для подтверждения.',
                ],
            ],

            // ── 12. Самая короткая: одно поле ───────────────────────────
            [
                'title'       => 'Подписка на новости',
                'slug'        => 'podpiska',
                'description' => 'Письмо раз в неделю, без рекламы. Отписаться можно в один клик.',
                'fields'      => [
                    ['type' => 'email', 'name' => 'email', 'label' => 'Электронная почта', 'required' => true, 'icon' => 'fas fa-at',
                     'placeholder' => 'you@example.com'],
                    ['type' => 'consent', 'name' => 'consent', 'icon' => 'fas fa-shield-halved', 'required' => true,
                     'label' => 'Согласен получать письма и с [политикой конфиденциальности](/privacy)'],
                ],
                'settings'    => [
                    'icon' => 'fas fa-envelope', 'submit_label' => 'Подписаться',
                    'success_message' => 'Готово! Первое письмо придёт в ближайшую рассылку.',
                    'columns' => false, 'show_title' => false,
                ],
            ],

            // ── 13. Вопрос с ответом на сайте ───────────────────────────
            [
                'title'       => 'Задать вопрос',
                'slug'        => 'zadat-vopros',
                'description' => 'Ответим на почту, а частые вопросы добавим в раздел «Вопросы и ответы».',
                'fields'      => [
                    ['type' => 'select', 'name' => 'topic', 'label' => 'Тема вопроса', 'required' => true,
                     'options' => ['Товары и услуги', 'Оплата', 'Доставка', 'Гарантия и возврат', 'Другое']],
                    ['type' => 'textarea', 'name' => 'question', 'label' => 'Ваш вопрос', 'required' => true, 'icon' => 'fas fa-circle-question'],
                    ['type' => 'text',   'name' => 'name',  'label' => 'Имя', 'icon' => 'fas fa-user'],
                    ['type' => 'email',  'name' => 'email', 'label' => 'Почта для ответа', 'required' => true, 'icon' => 'fas fa-at'],
                    ['type' => 'paragraph', 'name' => 'p_faq',
                     'label' => 'Возможно, ответ уже есть в разделе [частых вопросов](/faq) — загляните перед отправкой.'],
                    $consent,
                ],
                'settings'    => [
                    'icon' => 'fas fa-comments', 'submit_label' => 'Отправить вопрос',
                    'success_message' => 'Спасибо за вопрос! Ответим на указанную почту.',
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
            'icon'        => '',
            'placeholder' => '',
            'hint'        => '',
            'value'       => '',
            'required'    => false,
            // Ширину считаем по типу — тем же правилом, что конструктор.
            // Прописывать её в каждом определении значило бы повторять одно и
            // то же двести раз и однажды разойтись с панелью.
            'width'       => Form::widthFor($field['type']),
            'options'     => [],
        ], $fields);
    }
}
