<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Forms\Models\Form;

/**
 * Отрисовка формы и построение правил проверки по её описанию.
 *
 * Правила строятся ЗДЕСЬ, на сервере, из описания полей — а не приезжают с
 * формы. Разметку в браузере правит кто угодно: обязательность, длина и тип
 * поля, пришедшие вместе с ответом, не значат ничего.
 */
class FormService
{
    /**
     * Разрешённые расширения вложений — БЕЛЫЙ список.
     *
     * Живёт здесь, а конфиг модуля на него ссылается: если бы список был
     * записан и там, и здесь «на случай, когда конфиг не подмёржен», два
     * определения неизбежно разошлись бы, и разрешённое в панели отличалось
     * бы от разрешённого при приёме.
     */
    public const DEFAULT_UPLOAD_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'rtf', 'txt', 'csv',
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic',
        'zip', 'rar', '7z',
    ];

    /** Предел для одного текстового ответа. */
    public const MAX_TEXT = 500;

    /** Предел для многострочного ответа. */
    public const MAX_TEXTAREA = 5000;

    /**
     * Имя поля-ловушки.
     *
     * Обычный посетитель его не видит (спрятано стилем и вынесено из потока
     * табуляции), а простой бот заполняет всё подряд. Заполнено — заявка
     * отклоняется молча: сообщать боту, что его узнали, незачем.
     */
    public const HONEYPOT = 'website_url';

    /** Разметка формы для вставки в материал или шаблон. */
    public function render(Form $form, array $options = []): string
    {
        return (string) view('Forms::frontend.form', [
            'form'    => $form,
            'fields'  => $form->normalizedFields(),
            'options' => $options,
        ])->render();
    }

    /**
     * Правила проверки, собранные из описания полей.
     *
     * @return array{rules: array<string, mixed>, attributes: array<string, string>}
     */
    public function rules(Form $form): array
    {
        $rules = [];
        $attributes = [];

        foreach ($form->normalizedFields() as $field) {
            if (in_array($field['type'], Form::DECORATIVE_TYPES, true)) {
                continue;
            }

            $key = 'fields.' . $field['name'];
            $set = [$field['required'] ? 'required' : 'nullable'];

            switch ($field['type']) {
                case 'email':
                    $set[] = 'string';
                    $set[] = 'email:rfc';
                    $set[] = 'max:255';
                    break;

                case 'tel':
                    // Телефон не приводим к одному формату: у людей записаны
                    // и +7, и 8, и с пробелами. Ограничиваем только состав
                    // символов, чтобы в поле не уехал текст со ссылками.
                    $set[] = 'string';
                    $set[] = 'max:32';
                    $set[] = 'regex:/^[0-9\s()+\-]+$/';
                    break;

                case 'number':
                    $set[] = 'numeric';
                    break;

                case 'url':
                    $set[] = 'string';
                    $set[] = 'url';
                    $set[] = 'max:2000';
                    break;

                case 'date':
                    $set[] = 'date';
                    break;

                case 'time':
                    $set[] = 'date_format:H:i';
                    break;

                case 'textarea':
                    $set[] = 'string';
                    $set[] = 'max:' . self::MAX_TEXTAREA;
                    break;

                case 'select':
                case 'radio':
                    // Значение обязано быть одним из предложенных: иначе в
                    // заявке окажется что угодно, включая разметку.
                    $set[] = 'string';
                    $set[] = 'in:' . implode(',', $field['options']);
                    break;

                case 'checkboxes':
                    $set[] = 'array';
                    $rules[$key . '.*'] = ['string', 'in:' . implode(',', $field['options'])];
                    break;

                case 'checkbox':
                case 'consent':
                    // Согласие — это именно «принято»: nullable здесь означало
                    // бы, что галочку можно не ставить.
                    $set = [$field['required'] ? 'accepted' : 'nullable'];
                    break;

                case 'file':
                    $set[] = 'file';
                    $set[] = 'max:' . max_upload_kb();
                    $set[] = 'mimes:' . implode(',', (array) config('forms.upload_extensions', self::DEFAULT_UPLOAD_EXTENSIONS));
                    break;

                default:
                    $set[] = 'string';
                    $set[] = 'max:' . self::MAX_TEXT;
            }

            $rules[$key] = $set;
            $attributes[$key] = $field['label'] !== '' ? $field['label'] : $field['name'];
        }

        return ['rules' => $rules, 'attributes' => $attributes];
    }

    /**
     * Проверить ответы и вернуть готовые к сохранению данные.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(Form $form, array $payload): array
    {
        $prepared = $this->rules($form);

        $validated = Validator::make(
            ['fields' => $payload],
            $prepared['rules'],
            [],
            $prepared['attributes']
        )->validate();

        return $validated['fields'] ?? [];
    }
}
