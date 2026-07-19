<?php

namespace Modules\Slideshow\Controllers;

use App\Http\Controllers\Controller;
use Modules\Slideshow\Models\Slideshow;

class PublicController extends Controller
{
    /**
     * 🌐 Отображение слайдшоу по slug
     *
     * @param string $slug Уникальный идентификатор слайдшоу
     * @return \Illuminate\View\View
     */
    public function show(string $slug)
    {
        // 🔎 Поиск слайдшоу по slug с загрузкой всех слайдов
        $slideshow = Slideshow::with(['items' => function ($q) {
            $q->orderBy('order');
        }])
        ->where('slug', $slug)
        ->where('published', true)
        ->firstOrFail();

        // 📺 Отображение публичного шаблона
        return view('Slideshow::public.slideshow', compact('slideshow'));
    }
}
