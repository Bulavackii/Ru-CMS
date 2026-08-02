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
    // Конструктора с $this->middleware('auth') здесь быть не должно:
    // в Laravel 11+ метод удалён и вызов падает с «Call to undefined
    // method». Доступ закрывает группа маршрутов в routes/web.php.

    /**
     * Показать историю входов пользователя
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $loginHistory = LoginHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('frontend.dashboard.login-history', [
            'loginHistory' => $loginHistory,
        ]);
    }
}




