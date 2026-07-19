<?php

namespace Modules\Seo\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Seo\Models\RedirectRule;

class RedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Только безопасные методы
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        // Не вмешиваемся в админку и в сам мастер установки (там ещё может
        // не быть таблицы redirect_rules — миграции модулей запускаются
        // только на шаге /install/admin).
        $path = '/' . ltrim($request->getPathInfo(), '/');
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/install')) {
            return $next($request);
        }

        // Защита на случай, если middleware отработает раньше, чем
        // применятся миграции (свежая установка, откат и т.п.).
        if (!Schema::hasTable('redirect_rules')) {
            return $next($request);
        }

        $qs   = $request->getQueryString();
        $full = $path . ($qs ? ('?' . $qs) : '');

        // Кэширование правил на 5 минут
        $cacheKey = 'redirect_rule_' . md5($full);
        $rule = Cache::remember($cacheKey, 300, function () use ($full, $path) {
            $foundRule = null;
            
            // --- 1) Точный матч: СНАЧАЛА "path?query", ПОТОМ "path" ---
            $foundRule = RedirectRule::query()
                ->where('is_regex', false)
                ->where('from', $full)
                ->orderBy('priority')
                ->first();

            if (!$foundRule) {
                $foundRule = RedirectRule::query()
                    ->where('is_regex', false)
                    ->where('from', $path)
                    ->orderBy('priority')
                    ->first();
            }

            // --- 2) Регулярные правила — по приоритету (стабильно) ---
            if (!$foundRule) {
                // Кэшируем все regex правила
                $rules = Cache::remember('redirect_rules_regex', 300, function () {
                    return RedirectRule::query()
                        ->where('is_regex', true)
                        ->orderBy('priority')
                        ->orderBy('id')
                        ->get(['from', 'to', 'code', 'is_regex', 'priority']);
                });

                foreach ($rules as $r) {
                    $pattern = $this->wrapRegex($r->from);
                    // матчим по полному "path?query"
                    if (@preg_match($pattern, $full) === 1) {
                        $to = $r->to ? @preg_replace($pattern, (string)$r->to, $full, 1) : null;
                        $foundRule = (object) [
                            'from'      => $r->from,
                            'to'        => $to,
                            'code'      => (string)$r->code,
                            'is_regex'  => true,
                            'priority'  => (int)$r->priority,
                        ];
                        break;
                    }
                }
            }

            return $foundRule;
        });

        if (!$rule) {
            return $next($request);
        }

        // 410 — страница удалена
        if ((string)$rule->code === '410') {
            abort(410);
        }

        // Если целевой пуст/невалиден — не редиректим
        $rawTo = is_string($rule->to ?? null) ? trim($rule->to) : '';
        if ($rawTo === '') {
            return $next($request);
        }

        $to = $this->normalizeTarget($rawTo, $qs);

        // Предотвращаем саморедирект/петли
        if ($this->sameLocation($to, $full)) {
            return $next($request);
        }

        return redirect($to, (int)$rule->code)->header('X-Redirect-By', 'SEO Redirects');
    }

    /**
     * Оборачиваем шаблон регулярки в безопасные разделители + unicode.
     */
    protected function wrapRegex(string $pattern): string
    {
        $delims = ['#', '~', '%', '!', '/'];
        $delim = '#';
        foreach ($delims as $d) {
            if (!str_contains($pattern, $d)) {
                $delim = $d;
                break;
            }
        }
        // Флаги: i — без учета регистра, u — юникод
        return $delim . $pattern . $delim . 'iu';
    }

    /**
     * Нормализует целевой URL.
     * Если целевой относительный и у исходного был query — добавим его,
     * но только если в целевом уже нет собственного query.
     */
    protected function normalizeTarget(string $to, ?string $originalQuery): string
    {
        $to = trim($to);
        if ($to === '') {
            return '/';
        }

        // Если относительный путь
        if (!preg_match('~^https?://~i', $to)) {
            $to = '/' . ltrim($to, '/');
            if ($originalQuery && !str_contains($to, '?')) {
                $to .= '?' . $originalQuery;
            }
        }

        return $to;
    }

    /**
     * Сравнение целевого и текущего местоположения без учёта домена/схемы.
     */
    protected function sameLocation(string $to, string $currentFull): bool
    {
        // Приводим $to к виду path?query для сравнения
        if (preg_match('~^https?://~i', $to)) {
            $parts = parse_url($to);
            $toCmp = ($parts['path'] ?? '/') . (isset($parts['query']) ? ('?' . $parts['query']) : '');
        } else {
            $toCmp = $to;
        }

        // уберём хвостовые слэши и лишний вопрос без query
        $toCmp      = rtrim($toCmp, '/');
        $currentCmp = rtrim($currentFull, '/');

        return $toCmp === $currentCmp;
    }
}
