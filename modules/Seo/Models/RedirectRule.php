<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RedirectRule extends Model
{
    use SoftDeletes;

    protected $table = 'redirect_rules';

    protected $fillable = ['from', 'to', 'code', 'is_regex', 'priority'];

    protected $attributes = [
        'code'      => '301',
        'is_regex'  => false,
        'priority'  => 100,
    ];

    protected $casts = [
        'is_regex'   => 'boolean',
        'priority'   => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** Разрешённые коды редиректов */
    protected const ALLOWED_CODES = ['301', '302', '410'];

    /* ===================== Scopes ===================== */

    /**
     * Точный (не regex) матч по исходному пути.
     * Нормализуем вход, чтобы совпадало с тем, как мы сохраняем.
     */
    public function scopeExact($q, string $from)
    {
        return $q->where('is_regex', false)
                 ->where('from', static::normalizePath($from));
    }

    /** Регулярные правила по возрастанию приоритета (стабильная сортировка) */
    public function scopeRegexOrdered($q)
    {
        return $q->where('is_regex', true)->orderBy('priority')->orderBy('id');
    }

    /** Удобный общий порядок: сначала не-regex (по id), затем regex по приоритету */
    public function scopeOrdered($q)
    {
        return $q->orderBy('is_regex')      // false(0) → true(1)
                 ->orderBy('priority')
                 ->orderBy('id');
    }

    /* ===================== Mutators ===================== */

    /**
     * from:
     *  - для НЕ-regex нормализуем в вид: "/path" без хвостового "/", плюс query если был.
     *  - для regex — оставляем как есть (только trim).
     */
    public function setFromAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : '';
        $isRegex = (bool) ($this->attributes['is_regex'] ?? false);

        // если флаг is_regex ещё не успел установиться, попробуем «угадать» по наличию спец.символов
        if (!$isRegex) {
            $looksLikeRegex = (bool) preg_match('~[\\[\\]().?+*^$|\\\\]~', $v);
            if ($looksLikeRegex && !preg_match('~^https?://~i', $v) && str_starts_with($v, '/')) {
                // похоже на regex — не трогаем
                $this->attributes['from'] = $v;
                return;
            }
            $this->attributes['from'] = static::normalizePath($v);
            return;
        }

        // regex
        $this->attributes['from'] = $v;
    }

    /**
     * to:
     *  - пустая строка → NULL
     *  - для кода 410 «to» игнорируется (всё равно NULL)
     */
    public function setToAttribute($value): void
    {
        $code = (string) ($this->attributes['code'] ?? '301');
        if ($code === '410') {
            $this->attributes['to'] = null;
            return;
        }

        $v = is_string($value) ? trim($value) : '';
        $this->attributes['to'] = ($v === '') ? null : $v;
    }

    /** Валидируем/нормализуем код редиректа */
    public function setCodeAttribute($value): void
    {
        $v = (string) $value;
        if (!in_array($v, self::ALLOWED_CODES, true)) {
            $v = '301';
        }
        $this->attributes['code'] = $v;

        // если 410 — to не требуется
        if ($v === '410') {
            $this->attributes['to'] = null;
        }
    }

    /** Клампинг приоритета в диапазон 0..1000 */
    public function setPriorityAttribute($value): void
    {
        $n = (int) $value;
        if ($n < 0)   $n = 0;
        if ($n > 1000) $n = 1000;
        $this->attributes['priority'] = $n;
    }

    /* ===================== Helpers ===================== */

    /** Правило «удаляет» страницу (410 Gone)? */
    public function isGone(): bool
    {
        return (string) $this->code === '410';
    }

    /**
     * Проверяет, применимо ли правило к переданному пути/URL.
     * @param string $path Может быть абсолютным URL или относительным путём.
     */
    public function appliesTo(string $path): bool
    {
        $path = static::normalizePath($path);
        if ($this->is_regex) {
            $pattern = $this->compileRegex($this->from);
            return @preg_match($pattern, $path) === 1;
        }
        return $this->from === $path;
    }

    /**
     * Возвращает целевой URL/путь для данного входного пути,
     * с учётом подстановок $1..$9 для regex.
     * Для 410 вернёт null.
     */
    public function targetFor(string $path): ?string
    {
        if ($this->isGone()) return null;

        $path = static::normalizePath($path);

        if ($this->is_regex) {
            $pattern = $this->compileRegex($this->from);
            // preg_replace вернёт исходную строку, если нет совпадения; это нам не подходит → проверим ещё раз.
            if (@preg_match($pattern, $path) !== 1) {
                return null;
            }
            $to = (string) ($this->to ?? '');
            $res = @preg_replace($pattern, $to, $path, 1);
            return is_string($res) ? $res : null;
        }

        // обычный редирект (без regex)
        return $this->to ?: null;
    }

    /**
     * Нормализация относительного пути (для НЕ-regex):
     * - абсолютный URL → берём path + query
     * - добавляем ведущий слэш
     * - убираем хвостовой слэш (кроме случая '/')
     */
    public static function normalizePath(string $value): string
    {
        $v = trim($value);
        if ($v === '') return '/';

        // абсолютный URL → path + query
        if (filter_var($v, FILTER_VALIDATE_URL)) {
            $parts = parse_url($v);
            $path  = $parts['path']  ?? '/';
            $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
            $v = $path . $query;
        }

        // гарантируем ведущий слэш
        $v = '/' . ltrim($v, '/');

        // убираем хвостовой слэш (кроме корня)
        if (strlen($v) > 1) {
            // сохраняем "?query" если он есть
            $qpos = strpos($v, '?');
            $path = $qpos === false ? $v : substr($v, 0, $qpos);
            $qs   = $qpos === false ? '' : substr($v, $qpos);
            if (strlen($path) > 1) {
                $path = rtrim($path, '/');
            }
            $v = $path . $qs;
        }

        return $v;
    }

    /**
     * Компиляция пользовательского regex.
     * Если пользователь не указал разделители — оборачиваем в ~...~u.
     */
    protected function compileRegex(string $raw): string
    {
        $raw = trim($raw);
        // Если выглядит как уже «разделённый» (/, #, ~), оставим как есть.
        if (preg_match('~^([/#~]).+\\1[imsxuADSUXJ]*$~', $raw)) {
            return $raw;
        }
        // Иначе экранируем тильду и добавим флаг u
        return '~' . str_replace('~', '\~', $raw) . '~u';
    }
}
