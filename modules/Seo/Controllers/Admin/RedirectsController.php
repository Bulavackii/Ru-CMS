<?php

namespace Modules\Seo\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Http\Requests\RedirectRuleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Events\RedirectRuleCreated;
use App\Events\RedirectRuleUpdated;
use App\Events\RedirectRuleDeleted;
use Modules\Seo\Models\RedirectRule;

class RedirectsController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));

        $query = RedirectRule::query();

        // Поиск
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('from', 'like', "%{$q}%")
                   ->orWhere('to', 'like', "%{$q}%");
            });
        }

        // Фильтр по коду
        if ($r->filled('code')) {
            $query->where('code', $r->input('code'));
        }

        // Фильтр по типу (regex)
        if ($r->filled('is_regex')) {
            $query->where('is_regex', $r->boolean('is_regex'));
        }

        // Сортировка
        $sortBy = $r->input('sort_by', 'priority');
        $sortOrder = $r->input('sort_order', 'asc');
        
        $allowedSortFields = ['id', 'from', 'to', 'code', 'priority', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->ordered();
        }

        $items = $query->paginate(20)->withQueryString();

        return view('seo::admin.redirects.index', compact('items', 'q'));
    }

    public function create()
    {
        return view('seo::admin.redirects.form');
    }

    public function store(RedirectRuleRequest $r)
    {
        $data = $this->prepareData($r->validated());
        $rule = RedirectRule::create($data);
        
        // Очистка кэша
        Cache::forget('redirect_rules_active');
        
        // Событие
        RedirectRuleCreated::dispatch($rule);
        
        return redirect()->route('seo.redirects.index')->with('status', 'Добавлено');
    }

    public function edit($id)
    {
        $item = RedirectRule::findOrFail($id);
        return view('seo::admin.redirects.form', compact('item'));
    }

    public function update(RedirectRuleRequest $r, $id)
    {
        $item = RedirectRule::findOrFail($id);
        $data = $this->prepareData($r->validated());
        $item->update($data);
        
        // Очистка кэша
        Cache::forget('redirect_rules_active');
        
        // Событие
        RedirectRuleUpdated::dispatch($item);
        
        return redirect()->route('seo.redirects.index')->with('status', 'Сохранено');
    }

    public function destroy($id)
    {
        $rule = RedirectRule::findOrFail($id);
        RedirectRuleDeleted::dispatch($rule);
        $rule->delete();
        
        // Очистка кэша
        Cache::forget('redirect_rules_active');
        
        return back()->with('status', 'Удалено');
    }

    /**
     * 📦 Массовые действия
     */
    public function bulkAction(Request $r)
    {
        $ids = $r->input('selected', []);

        if (empty($ids)) {
            return back()->with('status', 'Выберите правила для действия.');
        }

        if ($r->action === 'delete') {
            $rules = RedirectRule::whereIn('id', $ids)->get();
            foreach ($rules as $rule) {
                RedirectRuleDeleted::dispatch($rule);
            }
            RedirectRule::whereIn('id', $ids)->delete();
            Cache::forget('redirect_rules_active');
            return back()->with('status', 'Выбранные правила удалены.');
        }

        return back()->with('status', 'Выберите действие.');
    }

    /**
     * Подготовка данных для сохранения
     */
    protected function prepareData(array $validated): array
    {
        $isRegex = (bool)($validated['is_regex'] ?? false);
        $priority = isset($validated['priority']) ? (int)$validated['priority'] : 100;

        if ($isRegex) {
            // Для regex оставляем как есть
            $from = trim($validated['from']);
        } else {
            // Нормализация путей
            $from = RedirectRule::normalizePath($validated['from']);
        }

        $to = null;
        if (!empty($validated['to'])) {
            if (filter_var($validated['to'], FILTER_VALIDATE_URL)) {
                $to = $validated['to'];
            } else {
                $to = RedirectRule::normalizePath($validated['to']);
            }
        }

        // Для 410 целевой URL не нужен
        if ($validated['code'] === '410') {
            $to = null;
        }

        return [
            'from' => $from,
            'to' => $to,
            'code' => $validated['code'],
            'is_regex' => $isRegex,
            'priority' => $priority,
        ];
    }

    /**
     * Валидация + нормализация полей.
     */
    protected function validated(Request $r, ?int $ignoreId = null): array
    {
        $v = $r->validate([
            'from'     => 'required|string|max:1024',
            'to'       => 'nullable|string|max:1024',
            'code'     => 'required|in:301,302,410',
            'is_regex' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:0|max:1000',
        ]);

        // trim строк
        foreach (['from','to'] as $k) {
            if (isset($v[$k]) && is_string($v[$k])) {
                $v[$k] = trim($v[$k]);
            }
        }

        $isRegex  = $r->boolean('is_regex');                // надёжнее, чем полагаться на cast в валидаторе
        $priority = isset($v['priority']) ? (int)$v['priority'] : 100;

        if ($isRegex) {
            $this->assertValidRegex($v['from']);
        } else {
            // нормализация путей
            $v['from'] = $this->normalizePath($v['from']);
            if (!empty($v['to'])) {
                $v['to'] = $this->normalizeTarget($v['to']);
            }
        }

        // Для 410 целевой URL не нужен
        if ($v['code'] === '410') {
            $v['to'] = null;
        } else {
            // Для 301/302 — обязателен целевой адрес
            if (empty($v['to'])) {
                throw ValidationException::withMessages(['to' => 'Для кода ' . $v['code'] . ' требуется целевой адрес.']);
            }
        }

        // Запрет саморедиректа (для нерегулярных правил), учитывая, что to может быть абсолютным
        if (!$isRegex && isset($v['to']) && $this->sameLocation($v['from'], $v['to'])) {
            throw ValidationException::withMessages(['to' => 'Нельзя редиректить на тот же путь.']);
        }

        // Защита от дублей (from + is_regex)
        $dup = RedirectRule::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('from', $v['from'])
            ->where('is_regex', $isRegex)
            ->first();

        if ($dup) {
            throw ValidationException::withMessages(['from' => 'Правило с таким "from" уже существует.']);
        }

        return [
            'from'     => $v['from'],
            'to'       => $v['to'] ?? null,
            'code'     => $v['code'],
            'is_regex' => $isRegex,
            'priority' => $priority,
        ];
    }

    /**
     * Оставляем путь + query, убеждаемся в ведущем слэше.
     */
    protected function normalizePath(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parts = parse_url($value);
            $path  = $parts['path'] ?? '/';
            $value = $path . (!empty($parts['query']) ? '?' . $parts['query'] : '');
        }
        $value = '/' . ltrim($value, '/');
        if (strlen($value) > 1) {
            $value = rtrim($value, '/');
        }
        return $value ?: '/';
    }

    /**
     * Цель может быть абсолютной или относительной — нормализуем относительную как и from.
     */
    protected function normalizeTarget(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        return $this->normalizePath($value);
    }

    /**
     * Быстрая проверка, что регулярка валидна как шаблон PCRE.
     */
    protected function assertValidRegex(string $pattern): void
    {
        $delims = ['#', '~', '%', '!', '/'];
        $delim = null;
        foreach ($delims as $d) {
            if (strpos($pattern, $d) === false) { $delim = $d; break; }
        }
        if (!$delim) {
            $delim = '#';
            $pattern = str_replace('#', '\#', $pattern);
        }
        $wrapped = $delim . $pattern . $delim . 'i';
        set_error_handler(function () {}, E_WARNING);
        $ok = @preg_match($wrapped, '') !== false;
        restore_error_handler();

        if (!$ok) {
            throw ValidationException::withMessages(['from' => 'Некорректное регулярное выражение.']);
        }
    }

    /**
     * true, если $to указывает на тот же path?query, что и $from (даже если $to абсолютный).
     */
    protected function sameLocation(string $from, string $to): bool
    {
        $toCmp = $to;
        if (filter_var($to, FILTER_VALIDATE_URL)) {
            $parts = parse_url($to);
            $toCmp = ($parts['path'] ?? '/') . (isset($parts['query']) ? ('?' . $parts['query']) : '');
        } else {
            $toCmp = $this->normalizePath($to);
        }
        return rtrim($from, '/') === rtrim($toCmp, '/');
    }
}
