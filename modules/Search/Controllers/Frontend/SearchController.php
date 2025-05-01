<?php

namespace Modules\Search\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Product;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $posts = Post::where('title', 'like', "%$query%")
                     ->orWhere('content', 'like', "%$query%")
                     ->get();

        $products = Product::where('name', 'like', "%$query%")
                           ->orWhere('description', 'like', "%$query%")
                           ->get();

        return view('Search::frontend.index', compact('query', 'posts', 'products'));
    }
}
