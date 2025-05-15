<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;

class FrontendSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $results = collect();

        if ($query) {
            $results = News::where('published', true)
                ->where(function ($qB) use ($query) {
                    $qB->where('title', 'like', '%' . $query . '%')
                        ->orWhere('content', 'like', '%' . $query . '%');
                })
                ->orderByDesc('created_at')
                ->paginate(12);
        }

        return view('frontend.search.results', compact('results', 'query'));
    }
}
