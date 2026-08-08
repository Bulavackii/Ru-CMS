<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 📨 Присланная заявка.
 *
 * Хранится всегда, даже когда у формы настроена отправка на почту: письмо
 * может не уйти, и без записи в базе заявка исчезла бы бесследно.
 */
class FormSubmission extends Model
{
    protected $table = 'form_submissions';

    protected $fillable = ['form_id', 'data', 'ip', 'user_agent', 'page', 'is_read'];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'is_read' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Пары «подпись → значение» для показа в панели.
     *
     * Подписи берутся из формы, а не из заявки: так в списке видно человеческое
     * название поля. Но если поле из формы потом убрали, значение всё равно
     * показывается — под своим служебным именем. Прятать присланные данные
     * из-за того, что форму переделали, нельзя.
     *
     * @return array<int, array{label:string, value:string}>
     */
    public function readableData(): array
    {
        $labels = [];

        foreach ($this->form?->normalizedFields() ?? [] as $field) {
            if ($field['label'] !== '') {
                $labels[$field['name']] = $field['label'];
            }
        }

        $rows = [];

        foreach ((array) $this->data as $name => $value) {
            $rows[] = [
                'label' => $labels[$name] ?? $name,
                'value' => is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value,
            ];
        }

        return $rows;
    }
}
