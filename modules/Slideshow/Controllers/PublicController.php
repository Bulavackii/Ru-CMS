<?php

namespace Modules\Slideshow\Controllers;

use App\Http\Controllers\Controller;
use Modules\Slideshow\Models\Slideshow;

class PublicController extends Controller
{
    public function show(string $slug)
    {
        $slideshow = Slideshow::with('items')->where('slug', $slug)->firstOrFail();

        return view('Slideshow::public.slideshow', compact('slideshow'));
    }
}
