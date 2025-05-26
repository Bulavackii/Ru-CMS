<?php

namespace Modules\Accessibility\Controllers\Frontend;

use App\Http\Controllers\Controller;

class AccessibilityController extends Controller
{
    public function script()
    {
        return response()->view('Accessibility::frontend.script')->header('Content-Type', 'application/javascript');
    }
}
