<?php

namespace Modules\Search\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Categories\Models\Category;
use Modules\Menu\Models\Page;
use Modules\Messages\Models\Message;
use Modules\News\Models\News;
use Modules\System\Models\Module;

/**
 * 🔍 Поиск по административной части.
 *
 * Разделы описаны одним массивом (см. definitions): для каждого известно,
 * как искать, как сортировать и как показать найденное. Вьюха получает уже
 * готовые элементы вида [title, desc, badge, url] и ничего не знает о моделях.
 */
class SearchController extends Controller
{
    /** Сколько записей показываем в одном разделе */
    private const PER_SECTION = 20;

    /** Шаблоны News, под которые есть отдельные разделы выдачи */
    private const SPECIAL_TEMPLATES = ['products', 'faq', 'reviews'];

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'filter' => 'nullable|string|in:modules,users,pages,categories,products,news,faq,reviews,contacts,custom',
            'sort' => 'nullable|string|in:relevance,name_asc,name_desc,date_asc,date_desc',
        ]);

        $query  = trim((string) $request->input('q', ''));
        $filter = (string) $request->input('filter', '');
        $sort   = (string) $request->input('sort', 'relevance');

        $sections = [];
        $total = 0;

        foreach ($this->definitions($query) as $key => $definition) {
            $visible = $filter === '' || $filter === $key;
            $count = 0;
            $items = collect();

            if ($query !== '') {
                // Счётчик считаем всегда, даже для скрытых фильтром разделов:
                // иначе чипы показывали бы нули везде, кроме выбранного раздела.
                $count = $definition['query']()->count();

                if ($visible && $count > 0) {
                    $items = $this->fetch($definition, $sort, $query);
                }
            }

            $sections[$key] = [
                'label'   => $definition['label'],
                'icon'    => $definition['icon'],
                'count'   => $count,
                'items'   => $items,
                'visible' => $visible,
            ];

            $total += $count;
        }

        // 🧬 Расширения: модуль может отдавать свои результаты через SearchProvider
        $customResults = ($query !== '' && ($filter === '' || $filter === 'custom'))
            ? $this->customResults($query)
            : [];

        return view('Search::admin.index', [
            'query'         => $query,
            'filter'        => $filter,
            'sort'          => $sort,
            'sections'      => $sections,
            'total'         => $total,
            'customResults' => $customResults,
            'perSection'    => self::PER_SECTION,
        ]);
    }

    /**
     * 📚 Описание разделов поиска.
     *
     * query — построитель запроса (без сортировки и лимита),
     * name  — колонка для сортировки по алфавиту,
     * map   — как показать найденную запись.
     */
    private function definitions(string $query): array
    {
        // search_like(): ILIKE на Postgres, иначе LIKE — иначе поиск был бы
        // регистрозависимым (см. app/helpers.php)
        $like = search_like();
        $mask = '%' . $query . '%';

        // Общий кусок поиска по записям News: заголовок, текст и мета-поля
        $newsSearch = fn () => News::query()->where(fn ($q) => $q
            ->where('title', $like, $mask)
            ->orWhere('content', $like, $mask)
            ->orWhere('meta_title', $like, $mask)
            ->orWhere('meta_description', $like, $mask)
            ->orWhere('meta_keywords', $like, $mask));

        // Выдержка вокруг совпадения, а не первые 120 символов: если слово нашлось
        // в середине текста, показать надо именно этот фрагмент — иначе непонятно,
        // почему запись вообще попала в выдачу.
        $excerpt = function ($text) use ($query) {
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

            if ($text === '' || $query === '') {
                return Str::limit($text, 120);
            }

            $pos = mb_stripos($text, $query);

            if ($pos === false || $pos < 60) {
                return Str::limit($text, 120);
            }

            return '…' . Str::limit(mb_substr($text, $pos - 40), 120);
        };

        return [
            'modules' => [
                'label' => 'Модули',
                'icon'  => 'fa-puzzle-piece',
                'name'  => 'name',
                'query' => fn () => Module::query()->where(fn ($q) => $q
                    ->where('name', $like, $mask)
                    ->orWhere('title', $like, $mask)
                    ->orWhere('version', $like, $mask)),
                'map'   => fn ($m) => [
                    'title' => $m->title ?: $m->name,
                    'desc'  => $m->name . ' · версия ' . ($m->version ?: '—'),
                    'badge' => $m->active ? null : 'выключен',
                    'url'   => route('admin.modules.index'),
                ],
            ],

            'users' => [
                'label' => 'Пользователи',
                'icon'  => 'fa-user',
                'name'  => 'name',
                'query' => fn () => User::query()->where(fn ($q) => $q
                    ->where('name', $like, $mask)
                    ->orWhere('email', $like, $mask)
                    ->orWhere('phone', $like, $mask)),
                'map'   => fn ($u) => [
                    'title' => $u->name,
                    'desc'  => $u->email . ($u->phone ? ' · ' . $u->phone : ''),
                    'badge' => $u->is_admin ? 'администратор' : null,
                    'url'   => route('admin.users.edit', $u),
                ],
            ],

            'pages' => [
                'label' => 'Страницы',
                'icon'  => 'fa-file-lines',
                'name'  => 'title',
                'query' => fn () => Page::query()->where(fn ($q) => $q
                    ->where('title', $like, $mask)
                    ->orWhere('slug', $like, $mask)
                    ->orWhere('content', $like, $mask)
                    ->orWhere('meta_description', $like, $mask)),
                'map'   => fn ($p) => [
                    'title' => $p->title,
                    'desc'  => $excerpt($p->content) ?: '/' . $p->slug,
                    'badge' => $p->published ? null : 'черновик',
                    'url'   => route('admin.pages.edit', $p),
                ],
            ],

            'categories' => [
                'label' => 'Категории',
                'icon'  => 'fa-tag',
                'name'  => 'title',
                'query' => fn () => Category::query()->where(fn ($q) => $q
                    ->where('title', $like, $mask)
                    ->orWhere('slug', $like, $mask)
                    ->orWhere('description', $like, $mask)),
                'map'   => fn ($c) => [
                    'title' => $c->title,
                    'desc'  => $excerpt($c->description) ?: 'Тип: ' . ($c->type ?: '—'),
                    'badge' => $c->is_active ? null : 'скрыта',
                    'url'   => route('admin.categories.edit', $c->id),
                ],
            ],

            'news' => [
                'label' => 'Новости',
                'icon'  => 'fa-newspaper',
                'name'  => 'title',
                // Записи без шаблона и с любым «обычным» шаблоном.
                // whereNotIn в одиночку отбросил бы записи с template = NULL.
                'query' => fn () => $newsSearch()->where(fn ($q) => $q
                    ->whereNull('template')
                    ->orWhereNotIn('template', self::SPECIAL_TEMPLATES)),
                'map'   => fn ($n) => [
                    'title' => $n->title,
                    'desc'  => $excerpt($n->content),
                    'badge' => optional($n->created_at)->format('d.m.Y'),
                    'url'   => route('admin.news.edit', $n),
                ],
            ],

            'products' => [
                'label' => 'Товары',
                'icon'  => 'fa-box-open',
                'name'  => 'title',
                'query' => fn () => $newsSearch()->where('template', 'products'),
                'map'   => fn ($n) => [
                    'title' => $n->title,
                    'desc'  => $excerpt($n->content),
                    'badge' => optional($n->created_at)->format('d.m.Y'),
                    'url'   => route('admin.news.edit', $n),
                ],
            ],

            'faq' => [
                'label' => 'Вопросы',
                'icon'  => 'fa-circle-question',
                'name'  => 'title',
                'query' => fn () => $newsSearch()->where('template', 'faq'),
                'map'   => fn ($n) => [
                    'title' => $n->title,
                    'desc'  => $excerpt($n->content),
                    'badge' => optional($n->created_at)->format('d.m.Y'),
                    'url'   => route('admin.news.edit', $n),
                ],
            ],

            'reviews' => [
                'label' => 'Отзывы',
                'icon'  => 'fa-comment',
                'name'  => 'title',
                'query' => fn () => $newsSearch()->where('template', 'reviews'),
                'map'   => fn ($n) => [
                    'title' => $n->title,
                    'desc'  => $excerpt($n->content),
                    'badge' => optional($n->created_at)->format('d.m.Y'),
                    'url'   => route('admin.news.edit', $n),
                ],
            ],

            'contacts' => [
                'label' => 'Обращения',
                'icon'  => 'fa-envelope',
                'name'  => 'subject',
                'query' => fn () => Message::query()->where(fn ($q) => $q
                    ->where('subject', $like, $mask)
                    ->orWhere('body', $like, $mask)),
                'map'   => fn ($m) => [
                    'title' => $m->subject ?: 'Без темы',
                    'desc'  => $excerpt($m->body),
                    'badge' => optional($m->created_at)->format('d.m.Y'),
                    'url'   => route('admin.messages.show', $m),
                ],
            ],
        ];
    }

    /**
     * 📥 Выборка одного раздела: сортировка, лимит и приведение к виду для вьюхи.
     */
    private function fetch(array $definition, string $sort, string $query): Collection
    {
        $builder = $definition['query']();

        switch ($sort) {
            case 'name_asc':
                $builder->orderBy($definition['name']);
                break;
            case 'name_desc':
                $builder->orderByDesc($definition['name']);
                break;
            case 'date_asc':
                $builder->orderBy('created_at');
                break;
            default: // date_desc и relevance
                $builder->orderByDesc('created_at');
        }

        $items = $builder->limit(self::PER_SECTION)->get()
            ->map(fn ($item) => $definition['map']($item));

        // «По релевантности»: совпадение ближе к началу названия важнее.
        // Сортируем уже выбранную порцию — в SQL это потребовало бы
        // драйвер-зависимых выражений, а разница видна в пределах страницы выдачи.
        if ($sort === 'relevance' && $query !== '') {
            $items = $items->sortBy(function (array $item) use ($query) {
                $pos = mb_stripos((string) $item['title'], $query);
                return $pos === false ? PHP_INT_MAX : $pos;
            });
        }

        return $items->values();
    }

    /**
     * 🧬 Результаты от модулей, объявивших собственный SearchProvider.
     */
    private function customResults(string $query): array
    {
        $results = [];

        foreach (Module::where('active', true)->get() as $module) {
            $provider = "Modules\\{$module->name}\\SearchProvider";

            if (class_exists($provider) && method_exists($provider, 'search')) {
                $found = call_user_func([$provider, 'search'], $query);
                if (!empty($found)) {
                    $results[$module->title ?: $module->name] = $found;
                }
            }
        }

        return $results;
    }

}
