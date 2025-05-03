<?php

namespace Modules\Users\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Models\User;

class AuthController extends Controller
{
    // Форма авторизации
    public function showLoginForm()
    {
        return view('Users::frontend.login');
    }

    // Авторизация пользователя
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Переадресация: если админ — на админку
            if (Auth::user()->is_admin) {
                return redirect('/admin/modules');
            }

            // Иначе — на клиентскую часть
            return redirect('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Ошибка входа, проверьте данные',
        ])->onlyInput('email');
    }

    // Выход из аккаунта
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
