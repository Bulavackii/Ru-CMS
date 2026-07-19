<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (File::exists(storage_path('install.lock'))) {
            return redirect('/')->with('error', 'Система уже установлена.');
        }

        return $next($request);
    }
}
