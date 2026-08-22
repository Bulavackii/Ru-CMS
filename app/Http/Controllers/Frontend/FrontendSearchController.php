<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;

/**
 * 🔍 FrontendSearchController
 *
 * Поиск по публичной части сайта: маршрут /search (frontend.search),
 * форма из шапки сайта шлёт запросы сюда.
 *
 * Ищет по опубликованным материалам и страницам. Страницы показываются
 * отдельным компактным блоком (их обычно единицы), материалы — основным
 * списком с постраничным выводом.
 *
 * Два правила, ради которых страница переписана:
 *
 *  1. Порядок выдачи — по СОВПАДЕНИЮ, а не по дате. Совпадение в заголовке
 *     важнее совпадения в тексте: по запросу «ра» база честно находит семь
 *     десятков материалов (буквы «ра» есть в «сРАвнивать» и «администРАтоРА»),
 *     и без ранжирования нужное лежит на пятой странице.
 *  2. Если точных совпадений нет — ищем по ОСНОВАМ слов. В русском окончания
 *     меняются, и «модульностью» не находило «модульность». Раньше в этом
 *     случае страница показывала тупик с кнопкой «Все новости».
 */
class FrontendSearchController extends Controller
{
    /** Сколько страниц сайта показываем над списком материалов */
    private const PAGES_LIMIT = 6;

    /** Материалов на странице выдачи */
    private const PER_PAGE = 12;

    /** Короче этого искать бессмысленно — совпадёт половина сайта */
    private const MIN_LENGTH = 2;

    /** Сколько свежих материалов предлагаем, когда показывать нечего */
    private const LATEST_LIMIT = 3;

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $запрос = trim((string) $request->input('q', ''));
        $длина = mb_strlen($запрос);
        $короткий = $длина > 0 && $длина < self::MIN_LENGTH;

        // Пустой или слишком короткий запрос — в базу за выдачей не ходим вовсе.
        // Раньше тут выполнялся заведомо пустой запрос с постраничным выводом.
        if ($запрос === '' || $короткий) {
            return $this->отдать($запрос, $короткий, collect(), 0, $this->пустаяВыдача());
        }

        [$страницы, $всегоСтраниц, $материалы] = $this->искать([$запрос], $запрос);

        $приблизительно = false;
        $основы = [];

        // Точных совпадений нет — пробуем по основам слов и говорим об этом прямо.
        if ($всегоСтраниц === 0 && $материалы->total() === 0) {
            $основы = $this->основы($запрос);

            if ($основы !== []) {
                [$страницы, $всегоСтраниц, $материалы] = $this->искать($основы, $основы[0]);
                $приблизительно = $всегоСтраниц > 0 || $материалы->total() > 0;
            }
        }

        return $this->отдать(
            $запрос,
            false,
            $страницы,
            $всегоСтраниц,
            $материалы,
            $приблизительно,
            $приблизительно ? $основы : [],
            $приблизительно ? [] : $this->подсказки($запрос),
        );
    }

    /**
     * Выдача по набору слов: страницы сайта отдельно, материалы списком.
     *
     * @param  array<int, string>  $термины  что ищем (условие ИЛИ между словами)
     * @param  string  $главное  по чему считать «совпадение в заголовке»
     * @return array{0: Collection, 1: int, 2: LengthAwarePaginator}
     */
    private function искать(array $термины, string $главное): array
    {
        // search_like(): ILIKE на Postgres, иначе LIKE. Без этого поиск был
        // регистрозависимым — «МОДУЛЬ» не находил «Модульная архитектура».
        $like = search_like();

        $поля = function (Builder $запрос, array $колонки) use ($термины, $like) {
            $запрос->where(function (Builder $q) use ($термины, $колонки, $like) {
                foreach ($термины as $слово) {
                    foreach ($колонки as $колонка) {
                        $q->orWhere($колонка, $like, '%' . $слово . '%');
                    }
                }
            });
        };

        // Страницы сайта. Показываем первые PAGES_LIMIT, но СЧИТАЕМ все:
        // раньше в шапке блока стояло число показанных, и при семи совпадениях
        // страница уверенно сообщала «5».
        $страницыЗапрос = Page::where('published', true)
            ->where(fn (Builder $q) => $поля($q, ['title', 'content', 'meta_description']));

        $всегоСтраниц = (clone $страницыЗапрос)->count();

        $страницы = $страницыЗапрос
            ->orderByRaw($this->ранг('title', $like), $this->привязки($главное))
            ->orderBy('title')
            ->limit(self::PAGES_LIMIT)
            ->get();

        $материалы = News::with('categories')
            ->where('published', true)
            ->where(fn (Builder $q) => $поля($q, ['title', 'content', 'meta_description', 'meta_keywords']))
            ->orderByRaw($this->ранг('title', $like), $this->привязки($главное))
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->appends(request()->only('q'));

        return [$страницы, $всегоСтраниц, $материалы];
    }

    /**
     * Ранг совпадения: заголовок начинается с запроса → содержит его → всё остальное.
     *
     * Выражение драйвер-нейтральное (CASE + LIKE/ILIKE): тесты гоняются на SQLite,
     * бой — на PostgreSQL.
     */
    private function ранг(string $колонка, string $like): string
    {
        return "CASE WHEN {$колонка} {$like} ? THEN 0 WHEN {$колонка} {$like} ? THEN 1 ELSE 2 END";
    }

    /** @return array<int, string> */
    private function привязки(string $слово): array
    {
        return [$слово . '%', '%' . $слово . '%'];
    }

    /**
     * Основы слов запроса — на них ищем, когда точных совпадений нет.
     *
     * Отрезаем до трёх последних букв, но не короче четырёх: «модульностью»
     * превращается в «модульн» и находит «модульность». Слова короче пяти букв
     * резать нечего — от них останется мусор, совпадающий с половиной сайта.
     *
     * @return array<int, string>
     */
    private function основы(string $запрос): array
    {
        $слова = preg_split('/\s+/u', $запрос, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $основы = [];

        // Фраза не нашлась целиком — каждое слово по отдельности уже полезно.
        if (count($слова) > 1) {
            foreach ($слова as $слово) {
                if (mb_strlen($слово) >= 3) {
                    $основы[] = $слово;
                }
            }
        }

        foreach ($слова as $слово) {
            $длина = mb_strlen($слово);

            if ($длина >= 5) {
                $основы[] = mb_substr($слово, 0, max(4, $длина - 3));
            }
        }

        // Основа, дословно повторяющая запрос, дала бы тот же пустой результат.
        return array_values(array_filter(array_unique($основы), fn ($с) => $с !== $запрос));
    }

    /**
     * Что предложить нажать, когда не нашлось совсем ничего.
     *
     * Раньше на этом месте была одна кнопка «Все новости» — она уводила из
     * поиска, а не помогала его уточнить. Слова запроса ссылками полезнее:
     * из «модульная архитектура» получаются два готовых поиска.
     *
     * @return array<int, string>
     */
    private function подсказки(string $запрос): array
    {
        $слова = preg_split('/\s+/u', $запрос, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($слова) < 2) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $слова,
            fn ($с) => mb_strlen($с) >= self::MIN_LENGTH,
        )));
    }

    /** Свежие материалы — чтобы пустая выдача не была тупиком */
    private function свежие(): Collection
    {
        return News::where('published', true)
            ->whereNotNull('slug')
            ->orderByDesc('created_at')
            ->limit(self::LATEST_LIMIT)
            ->get();
    }

    private function пустаяВыдача(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, self::PER_PAGE, 1, [
            'path' => request()->url(),
            'query' => request()->only('q'),
        ]);
    }

    /**
     * @param  array<int, string>  $основы
     * @param  array<int, string>  $подсказки
     */
    private function отдать(
        string $запрос,
        bool $короткий,
        Collection $страницы,
        int $всегоСтраниц,
        LengthAwarePaginator $материалы,
        bool $приблизительно = false,
        array $основы = [],
        array $подсказки = [],
    ) {
        $всего = $всегоСтраниц + $материалы->total();

        return view('frontend.search.results', [
            'query' => $запрос,
            'short' => $короткий,
            'pages' => $страницы,
            'pagesTotal' => $всегоСтраниц,
            'results' => $материалы,
            'total' => $всего,
            // Подсвечиваем то, по чему реально искали: при мягком поиске
            // подсветка исходного запроса не совпала бы ни с одной буквой.
            'highlightTerms' => $приблизительно ? $основы : [$запрос],
            'approximate' => $приблизительно,
            'stems' => $основы,
            'hints' => $подсказки,
            'latest' => $всего === 0 ? $this->свежие() : collect(),
        ]);
    }
}
