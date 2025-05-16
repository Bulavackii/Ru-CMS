<?php

namespace Modules\Menu\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\Menu\Models\Page;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)->where('published', true)->firstOrFail();

        return view('Menu::frontend.page', [
            'page' => $page,
            'title' => $page->meta_title ?? $page->title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
        ]);
    }
}
