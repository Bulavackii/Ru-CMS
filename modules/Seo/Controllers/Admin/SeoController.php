<?php

namespace Modules\Seo\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SeoController extends Controller
{
    /**
     * Точка входа модуля SEO в админке.
     * Перенаправляет на основной список SEO-страниц,
     * передавая параметры q/per_page, если они есть.
     */
    public function index(Request $request): RedirectResponse
    {
        $params = $request->only(['q', 'per_page']);
        return redirect()->route('seo.pages.index', $params);
    }
}
