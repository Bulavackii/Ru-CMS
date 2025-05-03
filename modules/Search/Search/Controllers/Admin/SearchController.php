<?php

namespace Modules\Search\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Models\Module;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $modules = Module::where('name', 'like', "%$query%")
                         ->orWhere('version', 'like', "%$query%")
                         ->get();

        $users = User::where('name', 'like', "%$query%")
                     ->orWhere('email', 'like', "%$query%")
                     ->get();

        $categories = Category::where('title', 'like', "%$query%")->get();

        $products = Product::where('name', 'like', "%$query%")
                           ->orWhere('description', 'like', "%$query%")
                           ->get();

        return view('Search::admin.index', compact('query', 'modules', 'users', 'categories', 'products'));
    }
}
