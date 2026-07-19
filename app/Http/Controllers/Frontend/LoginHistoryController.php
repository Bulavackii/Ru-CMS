<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 📊 LoginHistoryController
 *
 * Контроллер для просмотра истории входов пользователя
 */
class LoginHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать историю входов пользователя
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $loginHistory = LoginHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('frontend.dashboard.login-history', [
            'loginHistory' => $loginHistory,
        ]);
    }
}




