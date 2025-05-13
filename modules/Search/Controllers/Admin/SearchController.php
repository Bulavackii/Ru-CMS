<?php

namespace Modules\Search\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Models\Module;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Modules\Messages\Models\Message;
use Modules\News\Models\News;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query  = $request->input('q');
        $filter = $request->input('filter');
        $sort   = $request->input('sort', 'relevance');

        $showModules    = !$filter || $filter === 'modules';
        $showUsers      = !$filter || $filter === 'users';
        $showCategories = !$filter || $filter === 'categories';
        $showProducts   = !$filter || $filter === 'products';
        $showCustom     = !$filter || $filter === 'custom';
        $showNews       = !$filter || $filter === 'news';
        $showFaq        = !$filter || $filter === 'faq';
        $showReviews    = !$filter || $filter === 'reviews';
        $showContacts   = !$filter || $filter === 'contacts';

        $modules = $showModules ? Module::query()
            ->where('name', 'like', "%$query%")
            ->orWhere('version', 'like', "%$query%")
            ->when($sort === 'name_asc', fn($q) => $q->orderBy('name'))
            ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('name'))
            ->when($sort === 'date_asc', fn($q) => $q->orderBy('created_at'))
            ->when($sort === 'date_desc', fn($q) => $q->orderByDesc('created_at'))
            ->get() : collect();

        $users = $showUsers ? User::query()
            ->where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->when($sort === 'name_asc', fn($q) => $q->orderBy('name'))
            ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('name'))
            ->when($sort === 'date_asc', fn($q) => $q->orderBy('created_at'))
            ->when($sort === 'date_desc', fn($q) => $q->orderByDesc('created_at'))
            ->get() : collect();

        $categories = $showCategories ? Category::query()
            ->where('title', 'like', "%$query%")
            ->when($sort === 'name_asc', fn($q) => $q->orderBy('title'))
            ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('title'))
            ->get() : collect();

        $products = $showProducts ? Product::query()
            ->where('name', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->when($sort === 'name_asc', fn($q) => $q->orderBy('name'))
            ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('name'))
            ->when($sort === 'date_asc', fn($q) => $q->orderBy('created_at'))
            ->when($sort === 'date_desc', fn($q) => $q->orderByDesc('created_at'))
            ->get() : collect();

        $productsFromNews = $showProducts ? News::query()
            ->where('template', 'products')
            ->where(fn($q) => $q->where('title', 'like', "%$query%")
                                ->orWhere('content', 'like', "%$query%")
                                ->orWhere('meta_description', 'like', "%$query%"))
            ->when($sort === 'name_asc', fn($q) => $q->orderBy('title'))
            ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('title'))
            ->when($sort === 'date_asc', fn($q) => $q->orderBy('created_at'))
            ->when($sort === 'date_desc', fn($q) => $q->orderByDesc('created_at'))
            ->get() : collect();

        $news = $showNews ? News::query()
            ->where('template', 'news')
            ->where(fn($q) => $q->where('title', 'like', "%$query%")
                                ->orWhere('content', 'like', "%$query%")
                                ->orWhere('meta_description', 'like', "%$query%")
                                ->orWhere('meta_title', 'like', "%$query%")
                                ->orWhere('meta_keywords', 'like', "%$query%")
                                ->orWhere('meta_header', 'like', "%$query%"))
            ->get() : collect();

        $faq = $showFaq ? News::query()
            ->where('template', 'faq')
            ->where(fn($q) => $q->where('title', 'like', "%$query%")
                                ->orWhere('content', 'like', "%$query%"))
            ->get() : collect();

        $reviews = $showReviews ? News::query()
            ->where('template', 'reviews')
            ->where(fn($q) => $q->where('title', 'like', "%$query%")
                                ->orWhere('content', 'like', "%$query%"))
            ->get() : collect();

        $contacts = $showContacts ? Message::query()
            ->where('subject', 'like', "%$query%")
            ->orWhere('body', 'like', "%$query%")
            ->get() : collect();

        $customResults = [];
        if ($showCustom) {
            foreach (Module::where('active', true)->get() as $mod) {
                $provider = "Modules\\{$mod->name}\\SearchProvider";

                if (class_exists($provider) && method_exists($provider, 'search')) {
                    $results = call_user_func([$provider, 'search'], $query);
                    if (!empty($results)) {
                        $customResults[$mod->name] = $results;
                    }
                }
            }
        }

        return view('Search::admin.index', compact(
            'query', 'filter', 'sort',
            'modules', 'users', 'categories', 'products', 'productsFromNews',
            'news', 'faq', 'reviews', 'contacts', 'customResults',
            'showModules', 'showUsers', 'showCategories', 'showProducts',
            'showNews', 'showFaq', 'showReviews', 'showContacts', 'showCustom'
        ));
    }
}
