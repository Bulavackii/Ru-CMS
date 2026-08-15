<?php

namespace Modules\News\Controllers\Frontend;

use App\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\News\Controllers\Admin\NewsController as AdminNewsController;
use Modules\News\Models\News;

class NewsController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /** Сколько групп-шаблонов показывать на одной странице списка. */
    private const GROUPS_PER_PAGE = 3;

    public function index()
    {
        // Страницы набираются ЦЕЛЫМИ группами, а не по 12 материалов подряд.
        //
        // Раньше постранично резался плоский список, а группировка по шаблонам
        // делалась уже во вьюхе — и группа спокойно попадала на стык страниц:
        // два материала оставались в хвосте первой, три уезжали на вторую.
        // Читателю это выглядело как случайно разорванный раздел.
        //
        // Поэтому единица разбиения тут — группа, а не материал. Внутри
        // страницы группа всегда целая, а число карточек от страницы к
        // странице немного плавает.
        $cacheKey = 'news_list_v' . News::contentVersion()
            . '_page_' . request()->get('page', 1);

        $newsList = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function () {
            $groups = $this->groupKeys();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $onPage = array_slice($groups, ($page - 1) * self::GROUPS_PER_PAGE, self::GROUPS_PER_PAGE);

            $items = $onPage === [] ? collect() : News::with(['categories' => function ($q) {
                    // У категорий колонка называется title, а не name — из-за
                    // 'categories.name' весь список новостей отдавал 500
                    $q->select('categories.id', 'categories.title', 'categories.slug');
                }])
                // Поля шаблонов перечисляем поимённо: без rating плашка оценки
                // не появлялась у игровых карточек, без price/stock — цена у
                // товаров. Тот же промах уже был на главной.
                ->select('id', 'title', 'slug', 'content', 'template',
                    'price', 'stock', 'is_promo', 'rating', 'created_at', 'updated_at')
                ->published()
                ->where(fn ($q) => $this->whereGroups($q, $onPage))
                ->orderByDesc('id')
                ->get();

            // Считаем в группах, а не в материалах: иначе номер последней
            // страницы вычислялся бы из общего числа карточек и не совпал бы
            // с реальным количеством страниц.
            return new LengthAwarePaginator(
                $items,
                count($groups),
                self::GROUPS_PER_PAGE,
                $page,
                ['query' => request()->query()]
            );
        });

        // Адрес для ссылок страниц ставим ПОСЛЕ чтения из кеша.
        //
        // paginate() запоминает адрес того запроса, который наполнил кеш,
        // и дальше объект отдаётся всем как есть. Один заход с другого
        // хоста или порта — и ссылки страниц пятнадцать минут ведут не
        // туда: у владельца они вели на 127.0.0.1 без :8000, и страница
        // не открывалась вовсе.
        //
        // Версия содержимого в ключе — чтобы правка материала была видна
        // сразу, а не через четверть часа.
        $newsList = $newsList->withPath(request()->url());

        return view('frontend.news.index', compact('newsList'));
    }

    /**
     * Ключи групп в том порядке, в каком их выводит вьюха.
     *
     * Запрос лёгкий: тянем только сами шаблоны, без содержимого материалов.
     */
    private function groupKeys(): array
    {
        $present = News::published()
            ->select('template')
            ->distinct()
            ->pluck('template')
            ->map(fn ($t) => filled($t) ? $t : 'default')
            ->unique()
            ->all();

        $known = array_keys(AdminNewsController::TEMPLATES);

        // Сначала известные шаблоны в каноническом порядке — тогда порядок
        // разделов не прыгает от страницы к странице.
        $ordered = array_values(array_filter($known, fn ($k) => in_array($k, $present, true)));

        // Затем всё остальное: в базе может оказаться шаблон, которого нет в
        // списке (переименовали, удалили). Молча терять такие материалы
        // нельзя — они уедут в конец и отрисуются обычной лентой.
        return array_merge($ordered, array_values(array_diff($present, $known)));
    }

    /**
     * Условие «материал принадлежит одной из этих групп».
     *
     * Группа 'default' — три состояния сразу: шаблон записан буквально
     * «default» (так его пишет форма материала, он есть в списке шаблонов),
     * либо не задан вовсе, либо пустая строка. Сначала здесь учитывались
     * только два последних — и пять материалов с обычным шаблоном пропадали
     * со страницы, хотя группа для них исправно создавалась.
     *
     * Без сырого SQL с COALESCE: БД у проекта не одна — тесты идут на SQLite,
     * боевая база на PostgreSQL.
     */
    private function whereGroups($query, array $groups): void
    {
        $query->whereIn('template', $groups);

        if (in_array('default', $groups, true)) {
            $query->orWhereNull('template')->orWhere('template', '');
        }
    }

    public function show($slug)
    {
        $news = News::with('categories')
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Отслеживание просмотра
        $this->analytics->trackView($news, auth()->id());

        // Шаблон материала нужен и на самой странице новости, а не только в
        // списках. Раньше он читался лишь при группировке на главной: карточки
        // выглядели по-разному, а открытая новость — одинаково у всех.
        // Пустое значение и NULL — это тот же «default» (три состояния одной
        // группы, как в постраничном выводе ленты).
        $template = trim((string) $news->template) ?: 'default';

        return view('frontend.news.show', [
            'news' => $news,
            'template' => $template,
            'meta_title' => $news->meta_title ?? $news->title,
            'meta_description' => $news->meta_description,
            'meta_keywords' => $news->meta_keywords,
            'title' => $news->title, // для <title> в Blade
        ]);
    }
}
